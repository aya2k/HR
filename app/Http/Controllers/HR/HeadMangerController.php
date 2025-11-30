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
        'manager_type' => 'required|in:department_manager,branch_manager,manager_for_all_branches',
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

    // 1) Reset لكل أنواع الإدارة
    $employee->update([
        'is_manager' => false,
        'is_department_manager' => false,
        'is_branch_manager' => false,
        'manager_for_all_branches' => false,
        'managed_department_id' => null,
        'managed_branch_id' => null,
    ]);

    // 2) تحديد نوع المدير
    $updateData = [
        'is_manager' => true, // أي مدير لازم يكون مدير عام
    ];

    if ($request->manager_type == 'department_manager') {
        $updateData['is_department_manager'] = true;
        $updateData['managed_department_id'] = $request->department_id;
    }

    if ($request->manager_type == 'branch_manager') {
        $updateData['is_branch_manager'] = true;
        $updateData['managed_branch_id'] = $request->branch_id;
    }

    if ($request->manager_type == 'manager_for_all_branches') {
        $updateData['manager_for_all_branches'] = true;
    }

    $employee->update($updateData);

    return response()->json([
        'status' => true,
        'message' => 'Employee assigned as manager successfully',
        'data' => $employee->fresh(),
    ]);
}



public function removeManager($id)
{
    $employee = Employee::find($id);

    if (!$employee) {
        return response()->json([
            'status' => false,
            'message' => 'Employee not found',
        ], 404);
    }

    // Reset كل أنواع الإدارة
    $employee->update([
        'is_manager' => false,
        'is_department_manager' => false,
        'is_branch_manager' => false,
        'manager_for_all_branches' => false,
        'managed_department_id' => null,
        'managed_branch_id' => null,
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Employee returned to normal employee successfully',
        'data' => $employee->fresh(),
    ]);
}


}
