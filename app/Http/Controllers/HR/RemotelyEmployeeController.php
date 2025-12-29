<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use App\Models\RemotelyEmployee;

class RemotelyEmployeeController extends Controller
{
    public function index(Request $request)
    {
        $from       = $request->query('from');
        $to         = $request->query('to');
        $status     = $request->query('status');
        $keyword    = $request->query('keyword');
        $limit      = $request->integer('limit', 10);
        $page       = $request->integer('page', 1);

        $query = RemotelyEmployee::with([
            'employee:id,applicant_id,position_id,department_id,code',
            'employee.applicant:id,first_name,middle_name,last_name',
            'employee.position:id',


        ])->select(['id', 'employee_id', 'from', 'to', 'status', 'reason']);

        // ================= Date Filter =================
        if ($from || $to) {
            $query->when($from, fn($q) => $q->whereDate('from', '>=', $from));
            $query->when($to, fn($q) => $q->whereDate('to', '<=', $to));
        }

        // ================= Status Filter =================
        if ($status) {
            $query->where('status', $status);
        }

        // ================= Keyword Filter =================
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {

                // 🔹 search by employee code
                $q->whereHas(
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
            });
        }


        $total = (clone $query)->count();

        $data = $query->latest('from')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        $paginated = new \Illuminate\Pagination\LengthAwarePaginator($data, $total, $limit, $page, [
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
            'date' => 'required|date',
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
            'status' => 'required|in:pending,approved,rejected',
            'reason' => 'nullable|string',
        ]);

        // ================= Prevent Overlap =================
        $hasOverlap = RemotelyEmployee::where('employee_id', $validated['employee_id'])
            ->where(function ($q) use ($validated) {
                $q->whereBetween('from', [$validated['from'], $validated['to']])
                    ->orWhereBetween('to', [$validated['from'], $validated['to']])
                    ->orWhere(
                        fn($x) =>
                        $x->where('from', '<=', $validated['from'])
                            ->where('to', '>=', $validated['to'])
                    );
            })->exists();

        if ($hasOverlap) {
            throw ValidationException::withMessages([
                'from' => 'This employee already has a remotely work record during this period.'
            ]);
        }

        $record = RemotelyEmployee::create($validated);

        return response()->json([
            'message' => 'Record created successfully',
            'data' => $record
        ], 201);
    }

    public function show($id)
    {
        $record = RemotelyEmployee::with('employee')->findOrFail($id);
        return response()->json($record);
    }

    public function update(Request $request, $id)
    {
        $record = RemotelyEmployee::findOrFail($id);

        $validated = $request->validate([
            'date' => 'nullable|date',
            'from' => 'nullable|date',
            'to'   => 'nullable|date|after_or_equal:from',
            'status' => 'sometimes|in:pending,approved,rejected',
            'reason' => 'nullable|string',
        ]);

        $finalFrom = $validated['from'] ?? $record->from;
        $finalTo   = $validated['to'] ?? $record->to;

        // ================= Prevent Overlap =================
        $hasOverlap = RemotelyEmployee::where('employee_id', $record->employee_id)
            ->where('id', '!=', $record->id)
            ->where(function ($q) use ($finalFrom, $finalTo) {
                $q->whereBetween('from', [$finalFrom, $finalTo])
                    ->orWhereBetween('to', [$finalFrom, $finalTo])
                    ->orWhere(
                        fn($x) =>
                        $x->where('from', '<=', $finalFrom)
                            ->where('to', '>=', $finalTo)
                    );
            })->exists();

        if ($hasOverlap) {
            throw ValidationException::withMessages([
                'from' => 'This employee already has a remotely work record during this period.'
            ]);
        }

        $record->update($validated);
        $record->refresh();

        return response()->json([
            'message' => 'Record updated successfully',
            'data' => $record
        ]);
    }

    public function destroy($id)
    {
        $record = RemotelyEmployee::findOrFail($id);
        $record->delete();

        return response()->json([
            'message' => 'Record deleted successfully'
        ]);
    }


    public function remotelySummary(Request $request)
    {
        $from       = $request->query('from');
        $to         = $request->query('to');
        $department = $request->query('department');
        $keyword    = $request->query('keyword');
        $month      = $request->query('month');

        $query = RemotelyEmployee::query();

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
            'total_remote_requests'    => (clone $query)->count(),
            'approved_remotely'   => (clone $query)->where('status', 'approved')->count(),
            'rejected_remotely'   => (clone $query)->where('status', 'rejected')->count(),
            'pending_remotely'    => (clone $query)->where('status', 'pending')->count(),



        ]);
    }
}
