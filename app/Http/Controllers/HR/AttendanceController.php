<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\AttendanceRequest;
use App\Http\Resources\Attendance\AttendanceResource;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Shift;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Leave;
use App\Models\AttendanceDay;
use App\Models\AttendancePolicy;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    public function index()
    {
        return AttendanceResource::collection(
            Attendance::with(['employee','employee.position'])->latest()->paginate()
        );
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'check_in'    => 'required|date_format:H:i',
            'check_out'   => 'required|date_format:H:i',
            'date'=>'nullable|date'
        ]);

        $tz = 'Africa/Cairo';
        $workDate =$request->date ?? now($tz)->toDateString(); // ✅ التاريخ الحالي

        $employee = Employee::with('shift')->findOrFail($data['employee_id']);
        $shift = $employee->shift;

        if (!$shift || !$shift->start_time || !$shift->end_time) {
            return response()->json(['error' => 'Shift times missing.'], 422);
        }

        // ✅ إنشاء تواريخ البصمة
        $checkIn = Carbon::createFromFormat('Y-m-d H:i', "{$workDate} {$data['check_in']}", $tz);
        $checkOut = Carbon::createFromFormat('Y-m-d H:i', "{$workDate} {$data['check_out']}", $tz);

        if ($checkOut->lt($checkIn)) {
            $checkOut->addDay(); // في حالة العمل بعد منتصف الليل
        }

        // ✅ أوقات الشيفت
        $shiftStart = Carbon::createFromFormat('Y-m-d H:i:s', "{$workDate} {$shift->start_time}", $tz);
        $shiftEnd = Carbon::createFromFormat('Y-m-d H:i:s', "{$workDate} {$shift->end_time}", $tz);

        if ($shiftEnd->lte($shiftStart)) {
            $shiftEnd->addDay(); // شيفت عابر لليوم التالي
        }
        $shiftDuration = $shift->duration;
        // ✅ حسابات الدقائق
        $requiredHours = $shiftDuration;
        $workedMinutes   = $checkIn->diffInMinutes($checkOut);
        $lateMinutes     = $checkIn->gt($shiftStart) ? $shiftStart->diffInMinutes($checkIn) : 0;
        $earlyLeave = 0;
        $overtimeMinutes = 0;

        $workedHours = $workedMinutes / 60;
        if ($workedHours > $requiredHours) {
            $overtimeMinutes = $workedHours - $requiredHours ?? 0;
        }

        if ($workedHours < $requiredHours) {
            $earlyLeave  = ( $requiredHours - $workedHours)*60 ?? 0;
        }



        // ✅ سياسة السماح في التأخير
        $policy = AttendancePolicy::first();
        $grace = (int)($policy->late_grace_minutes ?? 0);
        if ($lateMinutes > 0 && $lateMinutes <= $grace) {
            $lateMinutes = 0;
        }

        // ✅ العجز
        $deficitMinutes = max(0, $requiredHours - $workedMinutes);

        // ✅ حفظ البيانات داخل Transaction
        DB::transaction(function () use (
            $employee,
            $checkIn,
            $checkOut,
            $workedMinutes,
            $lateMinutes,
            $earlyLeave,
            $overtimeMinutes,
            $deficitMinutes,
            $requiredHours,
            $shift,
            $policy,
            $workDate
        ) {
            Attendance::updateOrCreate(
                ['employee_id' => $employee->id, 'date' => $workDate],
                [
                    'check_in'             => $checkIn->format('H:i:s'),
                    'check_out'            => $checkOut->format('H:i:s'),
                    'total_hours'          => round($workedMinutes / 60, 2),
                    'late_minutes'         => $lateMinutes,
                    'overtime_minutes'     => $overtimeMinutes,
                    'status'               => 'present',
                    'fingerprint_verified' => true,
                ]
            );

            AttendanceDay::updateOrCreate(
                ['employee_id' => $employee->id, 'work_date' => $workDate],
                [
                    'branch_id'            => $employee->branch_id,
                    'required_minutes'     => (int)$requiredHours,
                    'break_minutes'        => (int)($shift->break_minutes ?? 0),
                    'first_in_at'          => $checkIn,
                    'last_out_at'          => $checkOut,
                    'worked_minutes'       => (int)$workedMinutes,
                    'overtime_minutes'     => (int)$overtimeMinutes,
                    'deficit_minutes'      => (int)$deficitMinutes,
                    'late_minutes'         => (int)$lateMinutes,
                    'early_leave_minutes'  => (int)$earlyLeave,
                    'punches_count'        => 2,
                    'day_type'             => 'workday',
                    'status'               => 'complete',
                    'components'           => [
                        'shift'  => $shift->name_en ?? $shift->name_ar,
                        'policy' => $policy->name ?? 'N/A',
                    ],
                ]
            );
        });

        // ✅ الرد بعد نجاح العملية
        return response()->json([
            'message'             => '✅ Attendance calculated & saved successfully',
            'worked_minutes'      => $workedMinutes,
            'overtime_minutes'    => $overtimeMinutes*60,
            'late_minutes'        => $lateMinutes,
            'early_leave_minutes' => $earlyLeave,
            //  'deficit_minutes'     => $deficitMinutes,
            'debug'               => [
                'workDate'   => $workDate,
                'checkIn'    => $checkIn->toDateTimeString(),
                'checkOut'   => $checkOut->toDateTimeString(),
                'shiftStart' => $shiftStart->toDateTimeString(),
                'shiftEnd'   => $shiftEnd->toDateTimeString(),
                'required'   => $requiredHours,
            ],
        ]);
    }





   public function getMonthlyReport($employeeId, $month)
{
    // تحويل الشهر إلى تاريخ أول اليوم وآخر يوم
    $startOfMonth = Carbon::parse($month)->startOfMonth();
    $endOfMonth = Carbon::parse($month)->endOfMonth();

    // جلب سجلات الحضور للموظف خلال الشهر
    $records = AttendanceDay::where('employee_id', $employeeId)
        ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
        ->get();

    // ملخص الشهر
    $summary = [
        'employee_id' => $employeeId,
        'month' => $startOfMonth->format('Y-m'),
        'total_worked_hours' => round($records->sum('worked_minutes') / 60, 2),
        'total_overtime_hours' => round($records->sum('overtime_minutes') / 60, 2),
        'total_late_minutes' => $records->sum('late_minutes'),
        'total_early_leave_minutes' => $records->sum('early_leave_minutes'),
        'total_absent_days' => $records->where('status', 'absent')->count(),
        'days_count' => $records->count(),
        'daily_records' => $records->map(function($record){
            return [
                'date' => $record->work_date->format('Y-m-d'),
                'worked_hours' => round($record->worked_minutes / 60, 2),
                'overtime_hours' => round($record->overtime_minutes / 60, 2),
                'late_minutes' => $record->late_minutes,
                'early_leave_minutes' => $record->early_leave_minutes,
                'status' => $record->status,
            ];
        }),
    ];

    return $summary;
}



public function header($day)
{
    $today = Carbon::parse($day)->toDateString();
    $yesterday = Carbon::parse($day)->subDay()->toDateString();

    // إحصائيات اليوم
    $todayStats = self::calculateDailyStats($today);

    // إحصائيات أمس
    $yesterdayStats = self::calculateDailyStats($yesterday);

    return [
        'present_summary' => [
            'on_time' => [
                'count' => $todayStats['on_time'],
                'diff'  => $todayStats['on_time'] - $yesterdayStats['on_time'],
            ],
            'late_clock_in' => [
                'count' => $todayStats['late'],
                'diff'  => $todayStats['late'] - $yesterdayStats['late'],
            ],
            'early_clock_in' => [
                'count' => $todayStats['early'],
                'diff'  => $todayStats['early'] - $yesterdayStats['early'],
            ],
        ],

        'away_summary' => [
            'day_off' => [
                'count' => $todayStats['day_off'],
                'diff'  => $todayStats['day_off'] - $yesterdayStats['day_off'],
            ],
            'present_employee' => [
                'count' => $todayStats['present'],
                'diff'  => $todayStats['present'] - $yesterdayStats['present'],
            ],
        ],

        'absent_summary' => [
            'absent' => [
                'count' => $todayStats['absent'],
                'diff'  => $todayStats['absent'] - $yesterdayStats['absent'],
            ],
        ]
    ];
}


private static function calculateDailyStats($date)
{
    $records = AttendanceDay::where('work_date', $date)->get();

    return [
        'present' => $records->where('day_type', 'workday')->count(),
        'absent'  => $records->where('day_type', 'absent')->count(),
        'day_off' => $records->where('day_type', 'leave')->count(),

        // الحضور على الوقت (لا يوجد late_minutes)
        'on_time' => $records->where('late_minutes', 0)->count(),

        // المتأخرين عن بداية الشيفت
        'late' => $records->where('late_minutes', '>', 0)->count(),

        // اللي خرج بدري أو لم يكمل الشيفت
        'early' => $records->where('early_leave_minutes', '>', 0)->count(),
    ];
}




public function update(Request $request, $employeeId)
{
    $data = $request->validate([
        'check_in'  => 'required|date_format:H:i',
        'check_out' => 'required|date_format:H:i',
        'date'      => 'nullable|date'
    ]);

    $tz = 'Africa/Cairo';
    $workDate = $request->date ?? now($tz)->toDateString();

    // البحث عن حضور الموظف في اليوم المحدد
    $attendance = Attendance::where('employee_id', $employeeId)
        ->where('date', $workDate)
        ->first();

    if (!$attendance) {
        return response()->json(['error' => 'Attendance not found for this date.'], 404);
    }

    $employee = Employee::with('shift')->findOrFail($employeeId);
    $shift = $employee->shift;

    if (!$shift || !$shift->start_time || !$shift->end_time) {
        return response()->json(['error' => 'Shift times missing.'], 422);
    }

    // إعداد check-in و check-out
    $checkIn = Carbon::createFromFormat('Y-m-d H:i', "{$workDate} {$data['check_in']}", $tz);
    $checkOut = Carbon::createFromFormat('Y-m-d H:i', "{$workDate} {$data['check_out']}", $tz);
    if ($checkOut->lt($checkIn)) $checkOut->addDay();

    $shiftStart = Carbon::createFromFormat('Y-m-d H:i:s', "{$workDate} {$shift->start_time}", $tz);
    $shiftEnd   = Carbon::createFromFormat('Y-m-d H:i:s', "{$workDate} {$shift->end_time}", $tz);
    if ($shiftEnd->lte($shiftStart)) $shiftEnd->addDay();

    $workedMinutes   = $checkIn->diffInMinutes($checkOut);
    $lateMinutes     = $checkIn->gt($shiftStart) ? $shiftStart->diffInMinutes($checkIn) : 0;
    $earlyLeave      = max(0, ($shift->duration*60) - $workedMinutes);
    $overtimeMinutes = max(0, $workedMinutes - ($shift->duration*60));

    $policy = AttendancePolicy::first();
    $grace = (int)($policy->late_grace_minutes ?? 0);
    if ($lateMinutes > 0 && $lateMinutes <= $grace) $lateMinutes = 0;

    $deficitMinutes = max(0, ($shift->duration*60) - $workedMinutes);

    DB::transaction(function () use ($attendance, $employee, $checkIn, $checkOut, $workedMinutes, $lateMinutes, $earlyLeave, $overtimeMinutes, $deficitMinutes) {
        $attendance->update([
            'check_in'         => $checkIn->format('H:i:s'),
            'check_out'        => $checkOut->format('H:i:s'),
            'total_hours'      => round($workedMinutes / 60, 2),
            'late_minutes'     => $lateMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'status'           => 'present',
        ]);

        AttendanceDay::where('employee_id', $employee->id)
            ->where('work_date', $attendance->date)
            ->update([
                'first_in_at'         => $checkIn,
                'last_out_at'         => $checkOut,
                'worked_minutes'      => $workedMinutes,
                'overtime_minutes'    => $overtimeMinutes,
                'deficit_minutes'     => $deficitMinutes,
                'late_minutes'        => $lateMinutes,
                'early_leave_minutes' => $earlyLeave,
            ]);
    });

    return response()->json([
        'message'             => '✅ Attendance updated successfully',
        'worked_minutes'      => $workedMinutes,
        'overtime_minutes'    => $overtimeMinutes,
        'late_minutes'        => $lateMinutes,
        'early_leave_minutes' => $earlyLeave,
    ]);
}




}
