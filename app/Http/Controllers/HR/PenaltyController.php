<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Penalty;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class PenaltyController extends Controller
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

        $query = Penalty::select('id', 'employee_id', 'date', 'type', 'amount')
            ->with([
                'employee:id,applicant_id,position_id,department_id,code',
                'employee.applicant:id,first_name,middle_name,last_name'
            ]);

        /* ================= Date Filters ================= */

        if ($from || $to) {
            $query->when($from, fn($q) => $q->whereDate('date', '>=', $from));
            $query->when($to, fn($q) => $q->whereDate('date', '<=', $to));
        } elseif ($month) {
            $date = Carbon::createFromFormat('Y-m', $month);
            $query->whereYear('date', $date->year)
                ->whereMonth('date', $date->month);
        }

        /* ================= Department ================= */

        $query->when(
            $department,
            fn($q) =>
            $q->whereHas(
                'employee',
                fn($e) =>
                $e->where('department_id', $department)
            )
        );

        /* ================= Keyword ================= */

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
            })
        );

        /* ================= Manual Pagination ================= */

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

    // CREATE
    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_ids'   => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
            'date'           => 'required|date',
            'type'           => 'required|string',
            'amount'         => 'required|numeric|min:0',
        ]);

        $penalties = [];

        foreach ($data['employee_ids'] as $employeeId) {
            $penalties[] = Penalty::create([
                'employee_id' => $employeeId,
                'date'        => $data['date'],
                'type'        => $data['type'],
                'amount'      => $data['amount'],
            ]);
        }

        return response()->json([
            'message' => 'Penalties created successfully',
            'data'    => $penalties
        ], 201);
    }


    // READ single
    public function show($id)
    {
        return Penalty::select('id', 'employee_id', 'date', 'type', 'amount')
            ->with([
                'employee:id,applicant_id,position_id',
                'employee.applicant:id,first_name,middle_name,last_name'
            ])
            ->findOrFail($id);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $penalty = Penalty::findOrFail($id);

        $data = $request->validate([
            'employee_id' => 'sometimes|exists:employees,id',
            'date'        => 'sometimes|date',
            'type'        => 'sometimes|string',
            'amount'      => 'sometimes|numeric|min:0',
        ]);

        $penalty->update($data);

        return $penalty->fresh();
    }

    // DELETE
    public function destroy($id)
    {
        Penalty::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Penalty deleted successfully'
        ]);
    }



      public function penaltiesSummary(Request $request)
    {
        $from       = $request->query('from');
        $to         = $request->query('to');
        $department = $request->query('department');
        $keyword    = $request->query('keyword');
        $month      = $request->query('month');

        $query = Penalty::query()
            ->with([
                'employee:id,department_id,applicant_id',
                'employee.applicant:id,first_name,middle_name,last_name'
            ]);

        /* ================= Date Filters ================= */

        if ($from || $to) {
            $query->when($from, fn($q) => $q->whereDate('date', '>=', $from));
            $query->when($to, fn($q) => $q->whereDate('date', '<=', $to));
        } elseif ($month) {
            $date = Carbon::createFromFormat('Y-m', $month);
            $query->whereYear('date', $date->year)
                ->whereMonth('date', $date->month);
        }

        /* ================= Department ================= */

        $query->when(
            $department,
            fn($q) =>
            $q->whereHas(
                'employee',
                fn($e) =>
                $e->where('department_id', $department)
            )
        );

        /* ================= Keyword ================= */

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

        /* ================= Cards ================= */

        return response()->json([
            'status' => true,
            'data' => [
                'total_penalties'      => (clone $query)->count(),
                'penalties_employees' => (clone $query)->distinct('employee_id')->count('employee_id'),
                'total_amount'       => (clone $query)->sum('amount'),
            ]
        ]);
    }
}
