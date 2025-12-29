<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Overtime;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\Reward;
use App\Models\Penalty;
use App\Models\EmployeeKpi;
use App\Models\LoanInstallment;

class PayrollCalculatorService
{
  
    public function calculate(Employee $emp, string $month): array
    {
        $tz = 'Africa/Cairo';
        $start = Carbon::parse($month, $tz)->startOfMonth();
        $end   = Carbon::parse($month, $tz)->endOfMonth();

        // KPI percent (افتراضي 0%)
        $kpiPercent = (float) (EmployeeKpi::where('employee_id', $emp->id)
            ->whereDate('month', $start->toDateString())
            ->value('kpi_percent') ?? 0);

        $base = (float) $emp->base_salary;
        $kpiAmount = (float) ($emp->kpi ?? 0);

        // ✅ net = base + (kpi% * kpi_amount)
        $netSalary = $base + (($kpiPercent / 100) * $kpiAmount);

        $dayRate = $netSalary / 30;
        $hourRate = $dayRate / 8;
        $overtimeHourRate = $hourRate * 1.35;

        // insurance
        $insuranceDeduction = 2700 * 0.11;
        $netAfterInsurance = $netSalary - $insuranceDeduction;

        // Attendance الشهر
        $attendances = Attendance::where('employee_id', $emp->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['date','late_minutes','status']);

        $attendanceByDate = $attendances->keyBy('date');

        // Late deduction per-day
        $lateDeduction = 0.0;
        $lateDetails = [];
        foreach ($attendances as $a) {
            $late = (int) $a->late_minutes;
            if ($late <= 15) continue;

            $amount = 0.0;
            if ($late >= 16 && $late <= 30) {
                $deductMinutes = $late * 2;
                $amount = ($deductMinutes / 60) * $hourRate;
            } elseif ($late >= 31 && $late <= 60) {
                $deductMinutes = $late * 4;
                $amount = ($deductMinutes / 60) * $hourRate;
            } else {
                $amount = 1 * $hourRate;
            }

            $lateDeduction += $amount;
            $lateDetails[] = ['date' => $a->date, 'late_minutes' => $late, 'deduction' => round($amount, 2)];
        }

        // Overtime approved (جدول Overtime فقط)
        $overtimeMinutes = Overtime::where('employee_id', $emp->id)
            ->where('status', 'approved')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['from','to'])
            ->sum(function ($r) {
                $from = Carbon::createFromFormat('H:i', $r->from);
                $to   = Carbon::createFromFormat('H:i', $r->to);
                return $from->diffInMinutes($to);
            });

        $overtimePay = ($overtimeMinutes / 60) * $overtimeHourRate;

        // Leaves (الأذونات) approved - 4 ساعات شهريًا
        $leaveMinutes = Leave::where('employee_id', $emp->id)
            ->where('status', 'approved')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['from','to'])
            ->sum(function ($r) {
                $from = Carbon::createFromFormat('H:i', $r->from);
                $to   = Carbon::createFromFormat('H:i', $r->to);
                return $from->diffInMinutes($to);
            });

        $leaveHours = $leaveMinutes / 60;
        $excessLeaveHours = max(0, $leaveHours - 4);
        $leaveDeduction = $excessLeaveHours * $hourRate;

        // Rewards / Penalties
        $rewards = Reward::where('employee_id', $emp->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        $penalties = Penalty::where('employee_id', $emp->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        // Holidays (approved/rejected)
        $holidays = Holiday::where('employee_id', $emp->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['from','to','type','status']);

        $countDays = function($from, $to) use ($tz) {
            $f = Carbon::parse($from, $tz)->startOfDay();
            $t = Carbon::parse($to, $tz)->startOfDay();
            return $f->diffInDays($t) + 1;
        };

        $holidayDeduction = 0.0;
        $holidayBonus = 0.0;
        $holidayDetails = [];

        $hasHolidaysPolicy = $this->hasHolidaysPolicy($emp);

        foreach ($holidays as $h) {
            $days = $countDays($h->from, $h->to);

            if ($h->status === 'rejected') {
                $amount = $days * $dayRate * 2;
                $holidayDeduction += $amount;
                $holidayDetails[] = ['type'=>$h->type,'status'=>'rejected','days'=>$days,'effect'=>'deduction','amount'=>round($amount,2)];
                continue;
            }

            if ($h->status !== 'approved') continue;

            if (!$hasHolidaysPolicy) {
                // part time / remote: أي Holiday = خصم (اعتبرها غياب بإذن)
                $amount = $days * $dayRate;
                $holidayDeduction += $amount;
                $holidayDetails[] = ['type'=>$h->type,'status'=>'approved_no_policy','days'=>$days,'effect'=>'deduction','amount'=>round($amount,2)];
                continue;
            }

            // full_time onsite
            if ($h->type === 'sick') {
                $amount = $days * $dayRate * 0.75; // خصم 75%
                $holidayDeduction += $amount;
                $holidayDetails[] = ['type'=>'sick','status'=>'approved','days'=>$days,'effect'=>'deduction','amount'=>round($amount,2)];
            } elseif (in_array($h->type, ['official_holiday_work', 'mission'])) {
                $amount = $days * $dayRate * 2; // bonus
                $holidayBonus += $amount;
                $holidayDetails[] = ['type'=>$h->type,'status'=>'approved','days'=>$days,'effect'=>'bonus','amount'=>round($amount,2)];
            } else {
                // annual/casual: no effect
                $holidayDetails[] = ['type'=>$h->type,'status'=>'approved','days'=>$days,'effect'=>'none','amount'=>0];
            }
        }

        // Absence (نسخة أولى + Friday rule)
        $approvedHolidayDates = $this->expandApprovedHolidayDates($holidays, $start, $end, $tz);

        $absenceWithoutPermissionDays = 0;
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $date = $cursor->toDateString();

            // Friday weekly off for full_time unless attended
            if ($cursor->isFriday() && $this->isFullTime($emp) && !$attendanceByDate->has($date)) {
                $cursor->addDay();
                continue;
            }

            $hasAttendance = $attendanceByDate->has($date);
            $hasApprovedHoliday = isset($approvedHolidayDates[$date]);

            if (!$hasAttendance && !$hasApprovedHoliday) {
                $absenceWithoutPermissionDays++;
            }
            $cursor->addDay();
        }

        $absenceDeduction = ($absenceWithoutPermissionDays * $dayRate * 2);

        // Loan deduction (installments unpaid for this month)
        $dueMonth = $start->toDateString();
        $loanDeduction = (float) LoanInstallment::where('employee_id', $emp->id)
            ->whereDate('due_month', $dueMonth)
            ->where('status', 'unpaid')
            ->sum('amount');

        $finalNet = $netAfterInsurance
            + $overtimePay
            + $holidayBonus
            + $rewards
            - $lateDeduction
            - $leaveDeduction
            - $holidayDeduction
            - $absenceDeduction
            - $loanDeduction
            - $penalties;

        return [
            'month' => $dueMonth,
            'base_salary' => round($base, 2),
            'kpi_amount' => round($kpiAmount, 2),
            'kpi_percent' => round($kpiPercent, 2),
            'net_salary' => round($netSalary, 2),

            'insurance_deduction' => round($insuranceDeduction, 2),
            'late_deduction' => round($lateDeduction, 2),
            'leave_deduction' => round($leaveDeduction, 2),
            'holiday_deduction' => round($holidayDeduction, 2),
            'absence_deduction' => round($absenceDeduction, 2),
            'loan_deduction' => round($loanDeduction, 2),

            'overtime_pay' => round($overtimePay, 2),
            'holiday_bonus' => round($holidayBonus, 2),
            'rewards' => round($rewards, 2),
            'penalties' => round($penalties, 2),

            'final_net' => round($finalNet, 2),

            'details' => [
                'rates' => [
                    'day_rate' => round($dayRate, 4),
                    'hour_rate' => round($hourRate, 4),
                    'overtime_hour_rate' => round($overtimeHourRate, 4),
                ],
                'late' => $lateDetails,
                'holidays' => $holidayDetails,
                'leave_hours_total' => round($leaveHours, 2),
                'leave_excess_hours' => round($excessLeaveHours, 2),
                'absence_without_permission_days' => $absenceWithoutPermissionDays,
            ],
        ];
    }

    private function hasHolidaysPolicy(Employee $emp): bool
    {
        $shiftName = optional($emp->shift)->name_en;
        return ($shiftName === 'full_time') && ($emp->work_setup === 'onsite');
    }

    private function isFullTime(Employee $emp): bool
    {
        return optional($emp->shift)->name_en === 'full_time';
    }

    private function expandApprovedHolidayDates($holidays, $start, $end, $tz): array
    {
        $dates = [];
        foreach ($holidays as $h) {
            if ($h->status !== 'approved') continue;

            $from = Carbon::parse($h->from, $tz)->startOfDay();
            $to   = Carbon::parse($h->to, $tz)->startOfDay();

            $cursor = $from->copy();
            while ($cursor->lte($to)) {
                $d = $cursor->toDateString();
                if ($d >= $start->toDateString() && $d <= $end->toDateString()) {
                    $dates[$d] = true;
                }
                $cursor->addDay();
            }
        }
        return $dates;
    }
}


