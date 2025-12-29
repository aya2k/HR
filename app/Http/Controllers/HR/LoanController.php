<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Loan;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class LoanController extends Controller
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

        $query = Loan::with(['employee:id,applicant_id,position_id,department_id,code', 'employee.applicant:id,first_name,middle_name,last_name']);

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
        $query->when($department, fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $department)));

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
        $data = $query->latest('date')->offset(($page - 1) * $limit)->limit($limit)->get();
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
            'amount'      => 'required|numeric|min:0',
            'status'      => 'required|in:pending,approved,rejected',
            'reason'      => 'nullable|string',
            'months'      => 'required|integer|min:1',
        ]);

        $monthlyAmount = $validated['amount'] / $validated['months'];


        $loan = Loan::create([
            'employee_id'    => $validated['employee_id'],
            'date'           => $validated['date'],
            'amount'         => $validated['amount'],
            'months'         => $validated['months'],
            'monthly_amount' => round($monthlyAmount, 2),
            'status'         => $validated['status'],
            'reason'         => $validated['reason'] ?? null,
        ]);


        return response()->json([
            'message' => 'Loan created successfully',
            'data'    => $loan
        ], 201);
    }

    public function show($id)
    {
        $loan = Loan::with('employee')->findOrFail($id);
        return response()->json($loan);
    }

    public function update(Request $request, $id)
    {
        $loan = Loan::findOrFail($id);

        $validated = $request->validate([
            'date'   => 'nullable|date',
            'amount' => 'nullable|numeric|min:0',
            'months' => 'nullable|integer|min:1',
            'status' => 'nullable|in:pending,approved,rejected',
            'reason' => 'nullable|string',
        ]);

        // القيم النهائية بعد التعديل أو القديمة
        $amount = $validated['amount'] ?? $loan->amount;
        $months = $validated['months'] ?? $loan->months;

        // إعادة حساب القسط الشهري
        $monthlyAmount = $amount / $months;

        $loan->update([
            'date'           => $validated['date']   ?? $loan->date,
            'amount'         => $amount,
            'months'         => $months,
            'monthly_amount' => round($monthlyAmount, 2),
            'status'         => $validated['status'] ?? $loan->status,
            'reason'         => $validated['reason'] ?? $loan->reason,
        ]);

        $loan->refresh();

        return response()->json([
            'message' => 'Loan updated successfully',
            'data'    => $loan
        ]);
    }


    public function destroy($id)
    {
        $loan = Loan::findOrFail($id);
        $loan->delete();

        return response()->json(['message' => 'Loan deleted successfully']);
    }

    public function loansSummary(Request $request)
    {
        $from       = $request->query('from');
        $to         = $request->query('to');
        $department = $request->query('department');
        $keyword    = $request->query('keyword');
        $month      = $request->query('month');

        $query = Loan::query();

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
        $query->when($department, fn($q) => $q->whereHas('employee', fn($e) => $e->where('department_id', $department)));

        // ================= Keyword Filter =================
        $query->when($keyword, fn($q) => $q->whereHas('employee.applicant', fn($a) => $a->where(function ($x) use ($keyword) {
            $x->where('first_name', 'like', "%{$keyword}%")
                ->orWhere('middle_name', 'like', "%{$keyword}%")
                ->orWhere('last_name', 'like', "%{$keyword}%");
        })));

        // ================= Summary Counts =================
        return response()->json([
            'employees_have_loans'     => (clone $query)->count(),
            'approved_loans'  => (clone $query)->where('status', 'approved')->count(),
            'rejected_loans'  => (clone $query)->where('status', 'rejected')->count(),
            'pending_loans'   => (clone $query)->where('status', 'pending')->count(),
            'total_amount'    => (clone $query)->sum('amount'),
        ]);
    }
}
