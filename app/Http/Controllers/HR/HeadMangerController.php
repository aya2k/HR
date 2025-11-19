<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;

class HeadMangerController extends Controller
{
    public function getManagers(Request $request)
    {
        $managers = Employee::with(['applicant', 'department', 'branches', 'company'])
            ->where(function ($q) {
                $q->where('is_manager', true)
                    ->orWhere('is_department_manager', true)
                    ->orWhere('manager_for_all_branches', true);
            })
            ->get();

        return response()->json([
            'status' => true,
            'data' => $managers
        ]);
    }


  public function addAsManager(Request $request, $id)
{
    $request->validate([
        'manager_type' => 'required|string',
        'department_id' => 'nullable|exists:departments,id',
        'branch_id' => 'nullable|exists:branches,id',
    ]);

    $employee = Employee::find($id);

    if (!$employee) {
        return response()->json([
            'status' => false,
            'message' => 'Employee not found',
        ], 404);
    }

    // نعمل reset لكل الخصائص أولًا
    $employee->update([
        'is_manager' => false,
        'is_department_manager' => false,
        'manager_for_all_branches' => false,
       // 'managed_department_id' => null,
       // 'managed_branch_id' => null,
    ]);

    // نحدث البيانات حسب نوع المدير
    $updateData = [
        $request->manager_type => true,
        'managed_department_id' => $request->department_id,
        'managed_branch_id' => $request->branch_id,
    ];

    $employee->update($updateData);

    return response()->json([
        'status' => true,
        'message' => 'Employee assigned as manager successfully',
        'data' => $employee->fresh(['department', 'branches']),
    ]);
}


}
