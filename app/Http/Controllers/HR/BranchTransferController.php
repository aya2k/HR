<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use App\Models\BranchTransfer;


class BranchTransferController extends Controller
{
    public function index(Request $request)
    {
        $from     = $request->query('from');
        $to       = $request->query('to');
        $branch   = $request->query('branch');
        $keyword  = $request->query('keyword');
        $month    = $request->query('month');

        $limit = $request->integer('limit', 10);
        $page  = $request->integer('page', 1);

        $query = BranchTransfer::with([
            'employee:id,applicant_id,department_id,code',
            'employee.applicant:id,first_name,middle_name,last_name',
            'currentBranch:id,name_en',
            'requestedBranch:id,name_en',
        ]);

        // ===== Date Filters =====
        if ($from || $to) {
            $query->when($from, fn($q) => $q->whereDate('date', '>=', $from));
            $query->when($to, fn($q) => $q->whereDate('date', '<=', $to));
        } elseif ($month) {
            $date = Carbon::createFromFormat('Y-m', $month);
            $query->whereYear('date', $date->year)
                ->whereMonth('date', $date->month);
        }

        // ===== Branch Filter =====
        $query->when(
            $branch,
            fn($q) =>
            $q->where('current_branch_id', $branch)
                ->orWhere('requested_branch_id', $branch)
        );

        // ===== Keyword Filter =====
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
            'employee_id'          => 'required|exists:employees,id',
            'current_branch_id'    => 'required|exists:branches,id',
            'requested_branch_id'  => 'required|exists:branches,id|different:current_branch_id',
            'date'                 => 'required|date',
            'status'               => 'required|in:pending,approved,rejected',
            'reason'               => 'nullable|string',
        ]);

        $transfer = BranchTransfer::create($validated);

        return response()->json([
            'message' => 'Branch transfer created successfully',
            'data'    => $transfer
        ], 201);
    }


    public function update(Request $request, $id)
    {
        $transfer = BranchTransfer::findOrFail($id);

        $validated = $request->validate([
            'date'                => 'nullable|date',
            'status'              => 'nullable|in:pending,approved,rejected',
            'reason'              => 'nullable|string',
            'requested_branch_id' => 'nullable|exists:branches,id',
        ]);

        $transfer->update($validated);

        return response()->json([
            'message' => 'Branch transfer updated successfully',
            'data'    => $transfer
        ]);
    }


    public function destroy($id)
    {
        BranchTransfer::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Branch transfer deleted successfully'
        ]);
    }

    public function branchTransfersSummary(Request $request)
    {
        $from       = $request->query('from');
        $to         = $request->query('to');
        $department = $request->query('department');
        $keyword    = $request->query('keyword');
        $month      = $request->query('month');

        $query = BranchTransfer::query();

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
            'totalbranch-transfers_requests'    => (clone $query)->count(),
            'approved_branch-transfers'   => (clone $query)->where('status', 'approved')->count(),
            'rejected_branch-transfers'   => (clone $query)->where('status', 'rejected')->count(),
            'pending_branch-transfers'    => (clone $query)->where('status', 'pending')->count(),



        ]);
    }
}
