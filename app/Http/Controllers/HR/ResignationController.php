<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Resignation;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class ResignationController extends Controller
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

        $query = Resignation::with([
            'employee:id,applicant_id,position_id,department_id,code',
            'employee.applicant:id,first_name,middle_name,last_name'
        ]);

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
            fn($q) =>
            $q->whereHas(
                'employee',
                fn($e) =>
                $e->where('department_id', $department)
            )
        );

        // ================= Keyword Filter =================
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


        // ================= Manual Pagination =================
        $total = (clone $query)->count();
        $data  = $query->latest('date')
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
            'date'        => 'required|date',
            'status'      => 'required|in:pending,approved,rejected',
            'reason'      => 'nullable|string',
            'pdf'         => 'nullable|file|mimes:pdf|max:5120',
        ]);
        if ($request->hasFile('pdf')) {

            $file = $request->file('pdf');

            $fileName = time() . '_' . uniqid() . '.' . $file->extension();
            $destinationPath = public_path('assets/resignations/pdf/');

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }

            $file->move($destinationPath, $fileName);

            $validated['pdf'] = asset('public/assets/resignations/pdf/' . $fileName);
        }


        $resignation = Resignation::create($validated);

        return response()->json([
            'message' => 'Resignation created successfully',
            'data'    => $resignation
        ], 201);
    }

    public function show($id)
    {
        $resignation = Resignation::with('employee')->findOrFail($id);
        return response()->json($resignation);
    }

    public function update(Request $request, $id)
    {
        $resignation = Resignation::findOrFail($id);

        $validated = $request->validate([
            'date'   => 'nullable|date',
            'status' => 'nullable|in:pending,approved,rejected',
            'reason' => 'nullable|string',
            'pdf'    => 'nullable|file|mimes:pdf|max:5120',
        ]);

        if ($request->hasFile('pdf')) {
            $validated['pdf'] = $request->file('pdf')
                ->store('resignations', 'public');
        }

        $resignation->update($validated);
        $resignation->refresh();

        return response()->json([
            'message' => 'Resignation updated successfully',
            'data'    => $resignation
        ]);
    }

    public function destroy($id)
    {
        $resignation = Resignation::findOrFail($id);
        $resignation->delete();

        return response()->json(['message' => 'Resignation deleted successfully']);
    }

    public function resignationsSummary(Request $request)
    {
        $from       = $request->query('from');
        $to         = $request->query('to');
        $department = $request->query('department');
        $keyword    = $request->query('keyword');
        $month      = $request->query('month');

        $query = Resignation::query();

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
            fn($q) =>
            $q->whereHas(
                'employee',
                fn($e) =>
                $e->where('department_id', $department)
            )
        );

        // ================= Keyword Filter =================
        $query->when(
            $keyword,
            fn($q) =>
            $q->whereHas(
                'employee.applicant',
                fn($a) =>
                $a->where(function ($x) use ($keyword) {
                    $x->where('first_name', 'like', "%{$keyword}%")
                        ->orWhere('middle_name', 'like', "%{$keyword}%")
                        ->orWhere('last_name', 'like', "%{$keyword}%");
                })
            )
        );

        return response()->json([
            'total_resignations'    => (clone $query)->count(),
            'approved_resignations' => (clone $query)->where('status', 'approved')->count(),
            'rejected_resignations' => (clone $query)->where('status', 'rejected')->count(),
            'pending_resignations'  => (clone $query)->where('status', 'pending')->count(),
        ]);
    }
}
