<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Leave;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;


class LeaveController extends Controller
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

        $query = Leave::with([
            'employee:id,applicant_id,position_id,department_id,code',
            'employee.applicant:id,first_name,middle_name,last_name',
            'employee.position:id',
            'replacedEmployee:id,applicant_id,position_id,department_id',
            'replacedEmployee.applicant:id,first_name,middle_name,last_name',
            'replacedEmployee.position:id'
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
            fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $department))
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

        $data = $query->latest('date')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        $paginated = new LengthAwarePaginator(
            $data,
            $total,
            $limit,
            $page,
            [
                'path'  => url()->current(),
                'query' => $request->query(),
            ]
        );

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


    /**
     * Store a newly created leave
     */


    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'from' => 'required|date_format:H:i',
            'to'   => 'required|date_format:H:i|after:from',
            'date' => 'required|date',
            'type' => 'required|string',
            'has_replacement' => 'boolean',
            'replaced_employee_id' => 'nullable|exists:employees,id',
            'status' => 'required|in:pending,approved,rejected',
        ]);

        // ================= Prevent Overlapping Time Ranges =================
        $hasConflict = Leave::where('employee_id', $validated['employee_id'])
            ->where('date', $validated['date']) // نفس اليوم
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($validated) {
                $q->where('from', '<', $validated['to'])
                    ->where('to',   '>', $validated['from']);
            })
            ->exists();

        if ($hasConflict) {
            throw ValidationException::withMessages([
                'from' => 'This employee already has a leave during this time.',
            ]);
        }

        $leave = Leave::create($validated);

        return response()->json([
            'message' => 'Leave created successfully',
            'data' => $leave
        ], 201);
    }



    /**
     * Display the specified leave
     */
    public function show($id)
    {
        $leave = Leave::with(['employee', 'replacedEmployee'])
            ->findOrFail($id);

        return response()->json($leave);
    }

    /**
     * Update the specified leave
     */
    public function update(Request $request, $id)
    {
        $leave = Leave::findOrFail($id);

        $validated = $request->validate([
            'from'   => 'nullable|date_format:H:i',
            'to'     => 'nullable|date_format:H:i|after:from',
            'date'   => 'nullable|date',
            'type'   => 'sometimes|string',
            'status' => 'sometimes|in:pending,approved,rejected',
            'replaced_employee_id' => 'nullable|exists:employees,id',
            'has_replacement' => 'sometimes',
        ]);

        // ✅ القيم النهائية
        $finalDate = $validated['date'] ?? $leave->date;
        $finalFrom = $validated['from'] ?? $leave->from;
        $finalTo   = $validated['to']   ?? $leave->to;

        // 🔴 منع تداخل الوقت
        $hasConflict = Leave::where('employee_id', $leave->employee_id)
            ->where('id', '!=', $leave->id)
            ->where('date', $finalDate)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($finalFrom, $finalTo) {
                $q->where('from', '<', $finalTo)
                    ->where('to',   '>', $finalFrom);
            })
            ->exists();

        if ($hasConflict) {
            throw ValidationException::withMessages([
                'from' => 'This employee already has a leave during this time.',
            ]);
        }

        // ✅ payload
        $payload = [
            'from'   => $finalFrom,
            'to'     => $finalTo,
            'date'   => $finalDate,
            'type'   => $validated['type']   ?? $leave->type,
            'status' => $validated['status'] ?? $leave->status,
        ];

        // ✅ has_replacement logic (زي ما هو عندك)
        if ($request->has('has_replacement')) {
            $hasReplacement = $request->boolean('has_replacement');
            $payload['has_replacement'] = $hasReplacement ? 1 : 0;

            if ($hasReplacement) {
                if (!$request->filled('replaced_employee_id')) {
                    return response()->json([
                        'message' => 'replaced_employee_id is required when has_replacement is 1'
                    ], 422);
                }
                $payload['replaced_employee_id'] = (int) $request->input('replaced_employee_id');
            } else {
                $payload['replaced_employee_id'] = null;
            }
        } elseif ($request->has('replaced_employee_id')) {
            $payload['replaced_employee_id'] = $request->filled('replaced_employee_id')
                ? (int) $request->input('replaced_employee_id')
                : null;
        }

        $leave->forceFill($payload)->save();
        $leave->refresh();

        return response()->json([
            'message' => 'Leave updated successfully',
            'data' => $leave
        ]);
    }







    /**
     * Remove the specified leave
     */
    public function destroy($id)
    {
        $leave = Leave::findOrFail($id);
        $leave->delete();

        return response()->json([
            'message' => 'Leave deleted successfully'
        ]);
    }



    public function leavesSummary(Request $request)
    {
        $from       = $request->query('from');
        $to         = $request->query('to');
        $department = $request->query('department');
        $keyword    = $request->query('keyword');
        $month      = $request->query('month');

        $query = Leave::query();

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

        // ================= Summary Counts =================
        return response()->json([
            'total_leaves'    => (clone $query)->count(),
            'approved_leaves' => (clone $query)->where('status', 'approved')->count(),
            'rejected_leaves' => (clone $query)->where('status', 'rejected')->count(),
            'pending_leaves'  => (clone $query)->where('status', 'pending')->count(),
        ]);
    }
}
