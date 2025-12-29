<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Holiday;
use Carbon\Carbon;
//use App\Models\Leave;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Http;


class HolidayController extends Controller
{

    public function index(Request $request)
    {
        $from       = $request->query('from');
        $to         = $request->query('to');
        $department = $request->query('department');
        $keyword    = $request->query('keyword');
        $month      = $request->query('month');

        $limit = $request->integer('limit', 10);
        $page  = $request->integer('page', 1);

        $query = Holiday::with([
            'employee:id,applicant_id,position_id,department_id,code',
            'employee.applicant:id,first_name,middle_name,last_name',
            'employee.position:id',
            'replacedEmployee:id,applicant_id,position_id,department_id',
            'replacedEmployee.applicant:id,first_name,middle_name,last_name',
            'replacedEmployee.position:id'
        ]);

        if ($from || $to) {
            $query->when($from, fn($q) => $q->whereDate('date', '>=', $from));
            $query->when($to, fn($q) => $q->whereDate('date', '<=', $to));
        } elseif ($month) {
            $date = Carbon::createFromFormat('Y-m', $month);
            $query->whereYear('date', $date->year)
                ->whereMonth('date', $date->month);
        }

        $query->when($department, fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $department)));

        $query->when(
            $keyword,
            fn($q) =>
            $q->where(function ($query) use ($keyword) {

                // 🔹 search by employee code
                $query->whereHas(
                    'employee',
                    fn($e) =>
                    $e->where('code', 'LIKE', "%{$keyword}%")
                )

                    // 🔹 search by applicant name
                    ->orWhereHas(
                        'employee.applicant',
                        fn($a) =>
                        $a->where(function ($x) use ($keyword) {
                            $x->where('first_name', 'LIKE', "%{$keyword}%")
                                ->orWhere('middle_name', 'LIKE', "%{$keyword}%")
                                ->orWhere('last_name', 'LIKE', "%{$keyword}%");
                        })
                    );
            })
        );


        $total = (clone $query)->count();

        $data = $query->latest('date')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        $paginated = new LengthAwarePaginator($data, $total, $limit, $page, [
            'path'  => url()->current(),
            'query' => $request->query(),
        ]);

        return response()->json([
            'status' => true,
            'data'   => $paginated->items(),
            'meta'   => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ]
        ]);
    }








    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
            'date' => 'required|date',
            'type' => 'required|string',
            'has_replacement' => 'boolean',
            'replaced_employee_id' => 'nullable|exists:employees,id',
            'status' => 'required|in:pending,approved,rejected',
            'reason' => 'nullable|string',
            'images.*' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
            'pdf.*'    => 'nullable|file|mimes:pdf|max:5120',
        ]);

        // ================= Prevent Overlapping Holidays =================
        $hasOverlap = Holiday::where('employee_id', $validated['employee_id'])
            ->where(function ($q) use ($validated) {
                $q->whereBetween('from', [$validated['from'], $validated['to']])
                    ->orWhereBetween('to', [$validated['from'], $validated['to']])
                    ->orWhere(function ($x) use ($validated) {
                        $x->where('from', '<=', $validated['from'])
                            ->where('to', '>=', $validated['to']);
                    });
            })
            ->exists();

        if ($hasOverlap) {
            throw ValidationException::withMessages([
                'from' => 'This employee already has a holiday during this period.',
            ]);
        }

        // ================= Upload Files =================
        if ($request->hasFile('images')) {
            $validated['images'] = [];

            foreach ($request->file('images') as $file) {
                $fileName = time() . '_' . uniqid() . '.' . $file->extension();
                $destinationPath = public_path('assets/holidays/images/');

                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }

                $file->move($destinationPath, $fileName);

                $validated['images'][] = asset('public/assets/holidays/images/' . $fileName);
            }
        }


        if ($request->hasFile('pdf')) {
            $validated['pdf'] = [];

            foreach ($request->file('pdf') as $file) {
                $fileName = time() . '_' . uniqid() . '.' . $file->extension();
                $destinationPath = public_path('assets/holidays/pdf/');

                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }

                $file->move($destinationPath, $fileName);

                $validated['pdf'][] = asset('public/assets/holidays/pdf/' . $fileName);
            }
        }


        $holiday = Holiday::create($validated);

        return response()->json([
            'message' => 'Holiday created successfully',
            'data' => $holiday
        ], 201);
    }


    public function show($id)
    {
        $holiday = Holiday::with(['employee', 'replacedEmployee'])->findOrFail($id);
        return response()->json($holiday);
    }



    public function update(Request $request, $id)
    {
        $holiday = Holiday::findOrFail($id);

        $validated = $request->validate([
            'from' => 'nullable|date',
            'to'   => 'nullable|date|after_or_equal:from',
            'date' => 'nullable|date',
            'type' => 'sometimes|string',
            'status' => 'sometimes|in:pending,approved,rejected',
            'has_replacement' => 'sometimes|boolean',
            'replaced_employee_id' => 'nullable|exists:employees,id',
            'reason' => 'nullable|string',
            'images.*' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
            'pdf.*'    => 'nullable|file|mimes:pdf|max:5120',
        ]);

        // ✅ القيم النهائية (لو مش مبعوتة نستخدم القديمة)
        $finalFrom = $validated['from'] ?? $holiday->from;
        $finalTo   = $validated['to']   ?? $holiday->to;
        $finalDate = $validated['date'] ?? $holiday->date;

        // ================= Prevent Overlapping Holidays =================
        $hasOverlap = Holiday::where('employee_id', $holiday->employee_id)
            ->where('id', '!=', $holiday->id) // استبعاد السجل الحالي
            ->where(function ($q) use ($finalFrom, $finalTo) {
                $q->whereBetween('from', [$finalFrom, $finalTo])
                    ->orWhereBetween('to', [$finalFrom, $finalTo])
                    ->orWhere(function ($x) use ($finalFrom, $finalTo) {
                        $x->where('from', '<=', $finalFrom)
                            ->where('to', '>=', $finalTo);
                    });
            })
            ->exists();

        if ($hasOverlap) {
            throw ValidationException::withMessages([
                'from' => 'This employee already has a holiday during this period.',
            ]);
        }

        // ================= Upload Images (same as store) =================
        if ($request->hasFile('images')) {
            $images = $holiday->images ?? [];

            foreach ($request->file('images') as $file) {
                $fileName = time() . '_' . uniqid() . '.' . $file->extension();
                $destinationPath = public_path('assets/holidays/images/');

                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }

                $file->move($destinationPath, $fileName);
                $images[] = asset('public/assets/holidays/images/' . $fileName);
            }

            $validated['images'] = $images;
        }

        // ================= Upload PDF (same as store) =================
        if ($request->hasFile('pdf')) {
            $pdfs = $holiday->pdf ?? [];

            foreach ($request->file('pdf') as $file) {
                $fileName = time() . '_' . uniqid() . '.' . $file->extension();
                $destinationPath = public_path('assets/holidays/pdf/');

                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }

                $file->move($destinationPath, $fileName);
                $pdfs[] = asset('public/assets/holidays/pdf/' . $fileName);
            }

            $validated['pdf'] = $pdfs;
        }

        // ================= Update =================
        $holiday->update($validated);
        $holiday->refresh();

        return response()->json([
            'message' => 'Holiday updated successfully',
            'data' => $holiday
        ]);
    }

    public function destroy($id)
    {
        $holiday = Holiday::findOrFail($id);
        $holiday->delete();

        return response()->json([
            'message' => 'Holiday deleted successfully'
        ]);
    }


    public function holidaysSummary(Request $request)
    {
        $from       = $request->query('from');
        $to         = $request->query('to');
        $department = $request->query('department');
        $keyword    = $request->query('keyword');
        $month      = $request->query('month');

        $query = Holiday::query();

        // ================= Date Filters =================
        if ($from || $to) {
            $query->when($from, fn($q) => $q->whereDate('date', '>=', $from));
            $query->when($to, fn($q) => $q->whereDate('date', '<=', $to));
        } elseif ($month) {
            $date = Carbon::createFromFormat('Y-m', $month);
            $query->whereYear('date', $date->year)
                ->whereMonth('date', $date->month);
        }

        // ================= Department Filter =================
        $query->when(
            $department,
            fn($q) => $q->whereHas(
                'employee',
                fn($e) => $e->where('department_id', $department)
            )
        );

        // ================= Keyword Filter =================
        $query->when(
            $keyword,
            fn($q) => $q->whereHas(
                'employee.applicant',
                fn($a) => $a->where(function ($x) use ($keyword) {
                    $x->where('first_name', 'like', "%{$keyword}%")
                        ->orWhere('middle_name', 'like', "%{$keyword}%")
                        ->orWhere('last_name', 'like', "%{$keyword}%");
                })
            )
        );

        return response()->json([
            'employees_on_holiday' => (clone $query)
                ->distinct('employee_id')
                ->count('employee_id'),
            'approved_holidays'   => (clone $query)->where('status', 'approved')->count(),
            'rejected_holidays'   => (clone $query)->where('status', 'rejected')->count(),
            'pending_holidays'    => (clone $query)->where('status', 'pending')->count(),



        ]);
    }



    public function egyptHolidays($year)
    {
        $response = Http::get("https://date.nager.at/api/v3/PublicHolidays/{$year}/EG");

        if ($response->failed()) {
            return response()->json([
                'message' => 'Failed to fetch holidays'
            ], 500);
        }

        return response()->json([
            'year' => $year,
            'country' => 'EG',
            'holidays' => $response->json()
        ]);
    }
}
