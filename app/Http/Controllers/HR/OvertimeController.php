<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Overtime;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;


class OvertimeController extends Controller
{
    public function index(Request $request)
    {
        $from       = $request->query('from');
        $to         = $request->query('to');
        $status     = $request->query('status');
        $keyword    = $request->query('keyword');
        $limit      = $request->integer('limit', 10);
        $page       = $request->integer('page', 1);

        $query = Overtime::with([
            'employee:id,applicant_id,position_id,department_id,code',
            'employee.applicant:id,first_name,middle_name,last_name',
            'employee.position:id',
        ])->select(['id', 'employee_id', 'date', 'from', 'to', 'status', 'reason']);

        if ($from || $to) {
            $query->when($from, fn($q) => $q->whereDate('date', '>=', $from));
            $query->when($to, fn($q) => $q->whereDate('date', '<=', $to));
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {

                // 🔹 search by employee code
                $q->whereHas(
                    'employee',
                    fn($e) =>
                    $e->where('code', 'LIKE', "%{$keyword}%")
                )

                    // 🔹 search by applicant name / phone
                    ->orWhereHas(
                        'employee.applicant',
                        fn($a) =>
                        $a->where(function ($x) use ($keyword) {
                            $x->where('first_name', 'LIKE', "%{$keyword}%")
                                ->orWhere('middle_name', 'LIKE', "%{$keyword}%")
                                ->orWhere('last_name', 'LIKE', "%{$keyword}%")
                                ->orWhere('phone', 'LIKE', "%{$keyword}%");
                        })
                    );
            });
        }


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
            'date' => 'required|date',
            'from' => 'required|date_format:H:i',
            'to'   => 'required|date_format:H:i|after:from',
            'status' => 'required|in:pending,approved,rejected',
            'reason' => 'nullable|string',
        ]);

        $record = Overtime::create($validated);

        return response()->json([
            'message' => 'Overtime created successfully',
            'data' => $record
        ], 201);
    }

    public function show($id)
    {
        $record = Overtime::with('employee')->findOrFail($id);
        return response()->json($record);
    }

    public function update(Request $request, $id)
    {
        $record = Overtime::findOrFail($id);

        $validated = $request->validate([
            'date' => 'nullable|date',
            'from' => 'required|date_format:H:i',
            'to'   => 'required|date_format:H:i|after:from',
            'status' => 'sometimes|in:pending,approved,rejected',
            'reason' => 'nullable|string',
        ]);

        $record->update($validated);
        $record->refresh();

        return response()->json([
            'message' => 'Overtime updated successfully',
            'data' => $record
        ]);
    }

    public function destroy($id)
    {
        $record = Overtime::findOrFail($id);
        $record->delete();

        return response()->json([
            'message' => 'Overtime deleted successfully'
        ]);
    }

    public function overtimeSummary(Request $request)
    {
        $from       = $request->query('from');
        $to         = $request->query('to');
        $department = $request->query('department');
        $keyword    = $request->query('keyword');
        $month      = $request->query('month');

        $query = Overtime::query();

        if ($from || $to) {
            $query->when($from, fn($q) => $q->whereDate('date', '>=', $from));
            $query->when($to, fn($q) => $q->whereDate('date', '<=', $to));
        } elseif ($month) {
            $date = Carbon::createFromFormat('Y-m', $month);
            $query->whereYear('date', $date->year)
                ->whereMonth('date', $date->month);
        }

        $query->when(
            $department,
            fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $department))
        );

        $query->when(
            $keyword,
            fn($q) => $q->whereHas(
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
            'total_overtime_requests' => (clone $query)->count(),
            'approved_overtime'       => (clone $query)->where('status', 'approved')->count(),
            'rejected_overtime'       => (clone $query)->where('status', 'rejected')->count(),
            'pending_overtime'        => (clone $query)->where('status', 'pending')->count(),
        ]);
    }
}
