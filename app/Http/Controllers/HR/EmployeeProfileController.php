<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Applicant;
use App\Models\Employee;
use Illuminate\Support\Str;
use App\Traits\ApiResponder;
use App\Models\Company;
use App\Models\Shift;
use App\Models\Attendance;
use App\Http\Resources\Employee\EmployeeProfileResource;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Models\Position;
use Carbon\Carbon;

class EmployeeProfileController extends Controller
{
    


public function show($id)
{
    // جلب بيانات الموظف
    $employee = Employee::with(['applicant', 'department', 'position', 'branches', 'company'])
                        ->findOrFail($id);

    // الشهر والسنة الحاليين
    $currentMonth = now()->month;
    $currentYear  = now()->year;

    // جلب سجلات الحضور والانصراف للموظف خلال الشهر الحالي فقط
    $employeeRecords = Attendance::where('employee_id', $id)
        ->whereMonth('date', $currentMonth)
        ->whereYear('date', $currentYear)
        ->get();

    // حساب الحضور
    $presentDays = $employeeRecords
                    ->whereNotIn('day_type', ['absent', 'leave'])
                    ->count();

    // حساب الغياب
    $absentDays = $employeeRecords
                    ->where('day_type', 'absent')
                    ->count();

    // إجمالي التأخير في الشهر الحالي (Late + Early Leave)
    $totalLate = $employeeRecords->sum(function ($record) {
        return ($record->late_minutes ?? 0) + ($record->early_leave_minutes ?? 0);
    });

    // إرجاع البيانات داخل Resource
    return response()->json(
        new EmployeeProfileResource($employee, [
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'total_late_minutes' => $totalLate,
        ])
    );
}




}
