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
use App\Models\EmployeeWorkDay;


class AttendanceController extends Controller
{
    public function index()
    {
        return AttendanceResource::collection(
            Attendance::with(['employee', 'employee.position'])
                ->whereNull('deleted_at')
                ->latest()
                ->paginate()
        );
    }



    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'check_in'    => 'required|date_format:H:i',
            'check_out'   => 'required|date_format:H:i',
            'date'        => 'nullable|date|before_or_equal:today',
        ]);


        $tz = 'Africa/Cairo';
        $workDate = $request->date ?? now($tz)->toDateString();

        $employee = Employee::findOrFail($data['employee_id']);
        $shift = $employee->employment_type_id ? Shift::find($employee->employment_type_id) : null;
        $employmentType = $shift?->name_en ?? 'part_time';
        $partTimeType = $employee->part_time_type ?? null; // hours | days

        $isFullTime      = $employmentType === 'full_time';
        $isPartTimeHours = $employmentType === 'part_time' && $partTimeType === 'hours';
        $isPartTimeDays  = $employmentType === 'part_time' && $partTimeType === 'days';

        if ($data['check_in'] === $data['check_out']) {
            return response()->json(['error' => 'check_in and check_out cannot be equal.'], 422);
        }

        $checkIn  = Carbon::createFromFormat('Y-m-d H:i', "{$workDate} {$data['check_in']}", $tz);
        $checkOut = Carbon::createFromFormat('Y-m-d H:i', "{$workDate} {$data['check_out']}", $tz);
        if ($checkOut->lt($checkIn)) $checkOut->addDay();

        $shiftStart = null;
        $shiftEnd   = null;
        $requiredHours = 0;

        if ($isPartTimeHours) {
            if (empty($employee->monthly_hours_required) || $employee->monthly_hours_required <= 0) {
                return response()->json(['error' => 'Total monthly hours not configured for this part-time-hours employee.'], 422);
            }

            $existingAttendance = Attendance::where('employee_id', $employee->id)
                ->where('date', $workDate)
                ->first();

            if ($existingAttendance) {
                $checkIn  = min(Carbon::parse($existingAttendance->check_in), $checkIn);
                $checkOut = max(Carbon::parse($existingAttendance->check_out), $checkOut);
            }
            $requiredHours = 0;
        } elseif ($isPartTimeDays) {
            $dayName = Carbon::parse($workDate, $tz)->format('l');
            $days = json_decode($employee->weekly_work_days, true) ?? [];

        //   $workDay = collect($days)->firstWhere('day', $dayName);

             $workDay = collect($days)->first(function ($d) use ($dayName) {
    return strtolower($d['day']) === strtolower($dayName);
});


            if (!$workDay || empty($workDay['start_time']) || empty($workDay['end_time'])) {
                return response()->json(['error' => 'Work day times missing for this employee.'], 422);
            }

            $shiftStart = Carbon::createFromFormat('Y-m-d H:i', "{$workDate} {$workDay['start_time']}", $tz);
            $shiftEnd   = Carbon::createFromFormat('Y-m-d H:i', "{$workDate} {$workDay['end_time']}", $tz);
            if ($shiftEnd->lte($shiftStart)) $shiftEnd->addDay();
            $requiredHours = $shiftStart->diffInMinutes($shiftEnd) / 60;
        } else {
            // Full Time → ثابت من 08:00 إلى 17:00
            $shiftStart = Carbon::createFromFormat('Y-m-d H:i', "{$workDate} 08:00", $tz);
            $shiftEnd   = Carbon::createFromFormat('Y-m-d H:i', "{$workDate} 17:00", $tz);
            $requiredHours = $shiftStart->diffInMinutes($shiftEnd) / 60;
        }

        $workedMinutes = (int) $checkIn->diffInMinutes($checkOut);
        $workedHours   = $workedMinutes / 60;

        $lateMinutes = $shiftStart ? max(0, (int)$shiftStart->diffInMinutes($checkIn)) : 0;
        $earlyLeave  = $shiftEnd ? max(0, (int)$checkOut->diffInMinutes($shiftEnd)) : 0;
        $deficitMinutes = $earlyLeave;

        $overtimeMinutes = 0;
        if (!$isPartTimeHours && $requiredHours > 0 && $workedMinutes > ($requiredHours * 60)) {
            $overtimeMinutes = (int)($workedMinutes - ($requiredHours * 60));
        }

        $policy = AttendancePolicy::first();
        $grace  = (int)($policy->late_grace_minutes ?? 0);
        if ($lateMinutes > 0 && $lateMinutes <= $grace) $lateMinutes = 0;

        DB::transaction(function () use (
            $employee,
            $checkIn,
            $checkOut,
            $workedMinutes,
            $lateMinutes,
            $earlyLeave,
            $overtimeMinutes,
            $deficitMinutes,
            $shift,
            $policy,
            $workDate,
            $isPartTimeHours,
            $employmentType
        ) {
            Attendance::updateOrCreate(
                ['employee_id' => $employee->id, 'date' => $workDate],
                [
                    'check_in'             => $checkIn->format('H:i:s'),
                    'check_out'            => $checkOut->format('H:i:s'),
                    'total_hours'          => round($workedMinutes / 60, 2),
                    'late_minutes'         => $lateMinutes,
                    'overtime_minutes'     => $isPartTimeHours ? 0 : $overtimeMinutes,
                    'status'               => 'present',
                    'fingerprint_verified' => true,
                ]
            );

            AttendanceDay::updateOrCreate(
                ['employee_id' => $employee->id, 'work_date' => $workDate],
                [
                    'branch_id'            => $employee->branch_id,
                    'break_minutes'        => (int)($shift?->break_minutes ?? 0),
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
                        'shift'  => $shift?->name_en,
                        'policy' => $policy->name ?? 'N/A',
                        'employment_type' => $employmentType,
                        'part_time_type'   => $employee->part_time_type,
                    ],
                ]
            );
        });

        // ملخص شهري للـ Part Time Hours
        $monthlySummary = null;
        if ($isPartTimeHours) {
            $month = Carbon::parse($workDate)->format('m');
            $year  = Carbon::parse($workDate)->format('Y');

            $monthlyWorkedHours = (float) Attendance::where('employee_id', $employee->id)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->sum('total_hours');

            $requiredMonthlyHours = (float) ($employee->total_hours ?? 0);

            if ($monthlyWorkedHours > $requiredMonthlyHours) {
                $monthlyOvertimeHours = round($monthlyWorkedHours - $requiredMonthlyHours, 2);
                $monthlyDeficitHours = 0;
            } else {
                $monthlyOvertimeHours = 0;
                $monthlyDeficitHours = round($requiredMonthlyHours - $monthlyWorkedHours, 2);
            }

            $monthlySummary = [
                'monthly_worked_hours'  => $monthlyWorkedHours,
                'monthly_required_hours' => $requiredMonthlyHours,
                'monthly_overtime_hours' => $monthlyOvertimeHours,
                'monthly_deficit_hours'  => $monthlyDeficitHours,
            ];
        }

        return response()->json([
            'message'             => '✅ Attendance calculated & saved successfully',
            'worked_minutes'      => $workedMinutes,
            'total_hours'         => round($workedMinutes / 60, 2),
            'overtime_minutes'    => $overtimeMinutes,
            'late_minutes'        => $lateMinutes,
            'early_leave_minutes' => $earlyLeave,
            'monthly_summary'     => $monthlySummary,
        ]);
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

        $attendance = Attendance::where('employee_id', $employeeId)
            ->where('date', $workDate)
            ->first();

        if (!$attendance) {
            return response()->json(['error' => 'Attendance not found for this date.'], 404);
        }

        $employee = Employee::findOrFail($employeeId);

        // نفس منطق store()
        $shift = $employee->employment_type_id ? Shift::find($employee->employment_type_id) : null;
        $employmentType = $shift?->name_en ?? 'part_time';
        $partTimeType = $employee->part_time_type ?? null; // hours | days

        $isFullTime      = $employmentType === 'full_time';
        $isPartTimeHours = $employmentType === 'part_time' && $partTimeType === 'hours';
        $isPartTimeDays  = $employmentType === 'part_time' && $partTimeType === 'days';

        if ($data['check_in'] === $data['check_out']) {
            return response()->json(['error' => 'check_in and check_out cannot be equal.'], 422);
        }

        $checkIn  = Carbon::createFromFormat('Y-m-d H:i', "{$workDate} {$data['check_in']}", $tz);
        $checkOut = Carbon::createFromFormat('Y-m-d H:i', "{$workDate} {$data['check_out']}", $tz);
        if ($checkOut->lt($checkIn)) $checkOut->addDay();

        $shiftStart = null;
        $shiftEnd   = null;
        $requiredHours = 0;

        if ($isPartTimeHours) {

            if (empty($employee->total_hours)) {
                return response()->json(['error' => 'Total monthly hours not configured for this part-time-hours employee.'], 422);
            }

            $existingAttendance = Attendance::where('employee_id', $employee->id)
                ->where('date', $workDate)
                ->first();

            if ($existingAttendance) {
                $checkIn  = min(Carbon::parse($existingAttendance->check_in), $checkIn);
                $checkOut = max(Carbon::parse($existingAttendance->check_out), $checkOut);
            }

            $requiredHours = 0;
        } elseif ($isPartTimeDays) {

            $dayName = Carbon::parse($workDate, $tz)->format('l');
            $days = $employee->days ?? [];
            $workDay = collect($days)->firstWhere('day', $dayName);

            if (!$workDay || empty($workDay['start_time']) || empty($workDay['end_time'])) {
                return response()->json(['error' => 'Work day times missing for this part-time day employee.'], 422);
            }

            $shiftStart = Carbon::createFromFormat('Y-m-d H:i', "{$workDate} {$workDay['start_time']}", $tz);
            $shiftEnd   = Carbon::createFromFormat('Y-m-d H:i', "{$workDate} {$workDay['end_time']}", $tz);
            if ($shiftEnd->lte($shiftStart)) $shiftEnd->addDay();

            $requiredHours = $shiftStart->diffInMinutes($shiftEnd) / 60;
        } else {

            $shiftStart = Carbon::createFromFormat('Y-m-d H:i', "{$workDate} 08:00", $tz);
            $shiftEnd   = Carbon::createFromFormat('Y-m-d H:i', "{$workDate} 17:00", $tz);
            $requiredHours = $shiftStart->diffInMinutes($shiftEnd) / 60;
        }

        $workedMinutes = (int) $checkIn->diffInMinutes($checkOut);
        $workedHours   = $workedMinutes / 60;

        $lateMinutes = $shiftStart ? max(0, (int)$shiftStart->diffInMinutes($checkIn)) : 0;
        $earlyLeave  = $shiftEnd ? max(0, (int)$checkOut->diffInMinutes($shiftEnd)) : 0;
        $deficitMinutes = $earlyLeave;

        $overtimeMinutes = 0;
        if (!$isPartTimeHours && $requiredHours > 0 && $workedMinutes > ($requiredHours * 60)) {
            $overtimeMinutes = (int)($workedMinutes - ($requiredHours * 60));
        }

        $policy = AttendancePolicy::first();
        $grace  = (int)($policy->late_grace_minutes ?? 0);
        if ($lateMinutes > 0 && $lateMinutes <= $grace) $lateMinutes = 0;

        DB::transaction(function () use (
            $employee,
            $checkIn,
            $checkOut,
            $workedMinutes,
            $lateMinutes,
            $earlyLeave,
            $overtimeMinutes,
            $deficitMinutes,
            $shift,
            $policy,
            $workDate,
            $isPartTimeHours,
            $employmentType
        ) {

            Attendance::updateOrCreate(
                ['employee_id' => $employee->id, 'date' => $workDate],
                [
                    'check_in'             => $checkIn->format('H:i:s'),
                    'check_out'            => $checkOut->format('H:i:s'),
                    'total_hours'          => round($workedMinutes / 60, 2),
                    'late_minutes'         => $lateMinutes,
                    'overtime_minutes'     => $isPartTimeHours ? 0 : $overtimeMinutes,
                    'status'               => 'present',
                    'fingerprint_verified' => true,
                ]
            );

            AttendanceDay::updateOrCreate(
                ['employee_id' => $employee->id, 'work_date' => $workDate],
                [
                    'branch_id'            => $employee->branch_id,
                    'break_minutes'        => (int)($shift?->break_minutes ?? 0),
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
                        'shift'  => $shift?->name_en,
                        'policy' => $policy->name ?? 'N/A',
                        'employment_type' => $employmentType,
                        'part_time_type'   => $employee->part_time_type,
                    ],
                ]
            );
        });

        // Monthly summary بنفس المنطق
        $monthlySummary = null;
        $monthlyHoursRequired = 0;

        if ($isPartTimeHours) {

            $month = Carbon::parse($workDate)->format('m');
            $year  = Carbon::parse($workDate)->format('Y');

            $monthlyWorkedHours = (float) Attendance::where('employee_id', $employee->id)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->sum('total_hours');

            $monthlyHoursRequired = (float) ($employee->total_hours ?? 0);

            if ($monthlyWorkedHours > $monthlyHoursRequired) {
                $monthlyOvertimeHours = round($monthlyWorkedHours - $monthlyHoursRequired, 2);
                $monthlyDeficitHours = 0;
            } else {
                $monthlyOvertimeHours = 0;
                $monthlyDeficitHours = round($monthlyHoursRequired - $monthlyWorkedHours, 2);
            }

            $monthlySummary = [
                'monthly_worked_hours'   => $monthlyWorkedHours,
                'monthly_required_hours' => $monthlyHoursRequired,
                'monthly_overtime_hours' => $monthlyOvertimeHours,
                'monthly_deficit_hours'  => $monthlyDeficitHours,
            ];
        }

        return response()->json([
            'message'                => '✅ Attendance updated successfully',
            'worked_minutes'         => $workedMinutes,
            'total_hours'            => round($workedMinutes / 60, 2),
            'overtime_minutes'       => $overtimeMinutes,
            'late_minutes'           => $lateMinutes,
            'early_leave_minutes'    => $earlyLeave,
            'monthly_summary'        => $monthlySummary,
            'monthly_hours_required' => $monthlyHoursRequired, // ✔️ الإضافة المطلوبة
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
            'daily_records' => $records->map(function ($record) {
                return [
                    'date' => $record->work_date->format('Y-m-d'),
                    'worked_hours' => round($record->worked_minutes / 60, 2),
                    'overtime_hours' => round($record->overtime_minutes),
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







    public function destroy(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date'        => 'required|date'
        ]);

        $employeeId = $data['employee_id'];
        $date       = $data['date'];

        DB::transaction(function () use ($employeeId, $date) {

            // ❌ حذف سجل البصمة
            Attendance::where('employee_id', $employeeId)
                ->where('date', $date)
                ->delete();

            // ❌ حذف سجل AttendanceDay
            AttendanceDay::where('employee_id', $employeeId)
                ->where('work_date', $date)
                ->delete();
        });

        return response()->json([
            'status'  => true,
            'message' => "Attendance deleted successfully"
        ]);
    }
}
