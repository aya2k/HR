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
use App\Models\AttendanceDay;
use App\Http\Resources\Employee\EmployeeProfileResource;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Models\Position;
use Carbon\Carbon;

class EmployeeProfileController extends Controller
{
    


public function PersonalData($id)
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

public function personalActivity($id)
{
    $employee = Employee::findOrFail($id);

    $tz = 'Africa/Cairo';
    $currentMonth = now($tz)->month;
    $currentYear  = now($tz)->year;

    // جلب الحضور للشهر الحالي للموظف
    $attendances = AttendanceDay::where('employee_id', $employee->id)
        ->whereYear('work_date', $currentYear)
        ->whereMonth('work_date', $currentMonth)
        ->orderBy('work_date', 'asc')
        ->get();

    // تجهيز بيانات كل يوم مع status منفصل
    $attendanceDetails = $attendances->map(function ($item) use ($tz) {

        // حساب حالة اليوم
        $dailyStatus = [];

        if ($item->late_minutes > 0) {
            $dailyStatus[] = 'lateArrival';
        }

        if ($item->early_leave_minutes > 0) {
            $dailyStatus[] = 'earlyLeave';
        }

        if ($item->overtime_minutes > 0) {
            $dailyStatus[] = 'overtime';
        }

        // لو مفيش أي مخالفة
        if (empty($dailyStatus)) {
            $dailyStatus[] = 'onTime';
        }

       return [
            'date'       => \Carbon\Carbon::parse($item->work_date)->format('Y-m-d'),
            'check_in'   => $item->first_in_at 
                            ? \Carbon\Carbon::parse($item->first_in_at)->setTimezone($tz)->format('H:i')
                            : null,
            'check_out'  => $item->last_out_at
                            ? \Carbon\Carbon::parse($item->last_out_at)->setTimezone($tz)->format('H:i')
                            : null,
            'status'     => $dailyStatus,
        ];
    });

    return response()->json([
        
       
        'attendances' => $attendanceDetails,
    ]);
}






}
