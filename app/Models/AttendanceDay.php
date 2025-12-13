<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceDay extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $guarded = [];

    protected $casts = ['first_in_at' => 'datetime', 'last_out_at' => 'datetime', 'work_date' => 'date', 'components' => 'array'];



    public function attendancePolicy()
    {
        return $this->belongsTo(AttendancePolicy::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public static function getMonthlySummaryAll($month, $from = null, $to = null, $branch = null, $keyword = null)
    {
        $startDate = Carbon::parse($month)->startOfMonth();
        $endDate   = Carbon::parse($month)->endOfMonth();

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : $startDate;
        $toDate   = $to   ? Carbon::parse($to)->endOfDay()   : $endDate;

        $query = self::whereBetween('work_date', [$fromDate, $toDate]);

        if ($branch) {
            $query->where('branch_id', $branch);
        }

        $records = $query->get();

        $summary = $records->groupBy('employee_id')->map(function ($employeeRecords, $employeeId) use ($startDate, $keyword) {

            $employee = Employee::with('applicant', 'position')->find($employeeId);
            if (!$employee) return null;

            if ($employee->monthly_hours_required > 0) {
                return null;
            }

            $shift = Shift::find($employee->shift_id);
            $employmentType = strtolower(trim($shift?->name_en ?? 'part time'));

            if (!in_array($employmentType, ['full time', 'part time'])) return null;

            $fullName = strtolower(
                ($employee->applicant->first_name ?? '') . ' ' .
                    ($employee->applicant->middle_name ?? '') . ' ' .
                    ($employee->applicant->last_name ?? '')
            );

            $code  = strtolower($employee->code ?? '');
            $phone = strtolower($employee->phone ?? '');

            if ($keyword) {
                $kw = strtolower($keyword);
                if (
                    !str_contains($fullName, $kw) &&
                    !str_contains($code, $kw) &&
                    !str_contains($phone, $kw)
                ) return null;
            }

            $presentDays = $employeeRecords
                ->whereNull('deleted_at')
                ->whereNotIn('day_type', ['absent', 'leave'])
                ->where('status', '!=', 'absent')
                ->count();

            $absentDays = $employeeRecords->where('day_type', 'absent')->count();

            $totalOvertime = $employeeRecords->sum('overtime_minutes');

            $daysWithOvertime = $employeeRecords
                ->whereNull('deleted_at')
                ->where('overtime_minutes', '>', 0)
                ->count();

            // حساب الـ totalLate فقط لو الموظف ماكملش ساعات اليوم
            $totalLate = $employeeRecords->sum(function ($record) use ($employee, $employmentType) {

                if (!empty($record->deleted_at)) return 0;

                $dayName = Carbon::parse($record->work_date)->format('l');
                $weeklyDays = json_decode($employee->weekly_work_days, true);

                if (!is_array($weeklyDays) || empty($weeklyDays)) {
                    if ($employmentType === 'full time') {
                        $weeklyDays = [
                            ['day' => $dayName, 'start_time' => '08:00', 'end_time' => '17:00']
                        ];
                    } else return 0;
                }

                $dayConfig = collect($weeklyDays)->firstWhere('day', $dayName);
                if (!$dayConfig || empty($dayConfig['start_time']) || empty($dayConfig['end_time'])) return 0;

                $requiredHours = Carbon::parse($dayConfig['start_time'])
                    ->diffInMinutes(Carbon::parse($dayConfig['end_time'])) / 60;

                if ($requiredHours <= 0) return 0;

                if ($record->first_in_at && $record->last_out_at) {
                    $actualHours = Carbon::parse($record->first_in_at)
                        ->diffInMinutes(Carbon::parse($record->last_out_at)) / 60;
                } else {
                    $actualHours = 0;
                }

                // لو الموظف كمل ساعات اليوم → لا تحسب التأخير
                if ($actualHours >= $requiredHours) return 0;

                return ($record->late_minutes ?? 0) + ($record->early_leave_minutes ?? 0);
            });

            // حساب incomplete shifts
            $daysWithIncompleteShift = $employeeRecords->filter(function ($record) use ($employee, $employmentType) {

                if (!empty($record->deleted_at)) return false;

                $dayName = Carbon::parse($record->work_date)->format('l');
                $weeklyDays = json_decode($employee->weekly_work_days, true);

                if (!is_array($weeklyDays) || empty($weeklyDays)) {
                    if ($employmentType === 'full time') {
                        $weeklyDays = [
                            ['day' => $dayName, 'start_time' => '08:00', 'end_time' => '17:00']
                        ];
                    } else return false;
                }

                $dayConfig = collect($weeklyDays)->firstWhere('day', $dayName);
                if (!$dayConfig || empty($dayConfig['start_time']) || empty($dayConfig['end_time'])) return false;

                $requiredHours = Carbon::parse($dayConfig['start_time'])
                    ->diffInMinutes(Carbon::parse($dayConfig['end_time'])) / 60;

                if ($requiredHours <= 0) return false;

                if ($record->first_in_at && $record->last_out_at) {
                    $actualHours = Carbon::parse($record->first_in_at)
                        ->diffInMinutes(Carbon::parse($record->last_out_at)) / 60;
                } else {
                    $actualHours = 0;
                }

                return $actualHours < $requiredHours;
            })->count();

            return [
                'employee_id' => $employeeId,
                'employee_code' => $employee->code ?? null,
                'employee_name' => $fullName,
                'employee_position' => $employee->position->title_en ?? null,
                'month' => $startDate->format('Y-m'),
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                'days_with_incomplete_shifts' => $daysWithIncompleteShift,
                'total_incomplete_shifts' => $totalLate,
                'days_with_overtime' => $daysWithOvertime,
                'total_overtime_minutes' => $totalOvertime,
            ];
        })
            ->filter()
            ->values();

        return $summary;
    }










    public static function getDailySummary($day)
    {
        $records = self::with(['employee', 'employee.position', 'employee.shift'])
            ->where('work_date', $day)
            ->get();

        $summary = $records->groupBy('employee_id')->map(function ($employeeRecords, $employeeId) use ($day) {
            $employee = $employeeRecords->first()->employee;

            if (!$employee) return null;

            $fullName = ($employee->applicant->first_name ?? '') . ' ' .
                ($employee->applicant->middle_name ?? '') . ' ' .
                ($employee->applicant->last_name ?? '');

            // مجموع التأخيرات والانصراف المبكر
            $totalLateMinutes = $employeeRecords->sum('late_minutes');
            $totalEarlyLeaveMinutes = $employeeRecords->sum('early_leave_minutes');
            $totalOvertime = $employeeRecords->sum('overtime_minutes');

            // حساب الــ Status
            $status = [];

            if ($totalLateMinutes > 0) {
                $status[] = 'lateArrival';
            }

            if ($totalEarlyLeaveMinutes > 0) {
                $status[] = 'earlyLeave';
            }

            if ($totalOvertime > 0) {
                $status[] = 'overtime';
            }

            if (empty($status)) {
                $status[] = 'onTime';
            }


            return [
                'id' => $employeeId,
                'employee' => [
                    'code' => $employee->code ?? null,
                    'first_name' => $employee->applicant->first_name ?? '',
                    'middle_name' => $employee->applicant->middle_name ?? '',
                    'last_name' => $employee->applicant->last_name ?? '',
                    'name' => $fullName,
                    'phone' => $employee->phone ?? null,
                    'position' => $employee->position->title_en ?? null,
                    'department' => $employee->department->name_en ?? null, // مهم جداً
                    'shift' => $employee->shift->name_en ?? null,
                ],
                'date' => $day,
                'check_in' => $employeeRecords->min('first_in_at'),
                'check_out' => $employeeRecords->max('last_out_at'),
                'worked_minutes' => $employeeRecords->sum('worked_minutes'),
                'total_overtime' => $totalOvertime,
                'status' => $status,
                'department' => $employee->department->name_en ?? null,

            ];
        })->filter()->values();

        return $summary;
    }



    //==============================================part time


    public static function getMonthlySummaryPartTimeHours($month, $from = null, $to = null, $branch = null, $keyword = null)
    {
        $startDate = Carbon::parse($month)->startOfMonth();
        $endDate   = Carbon::parse($month)->endOfMonth();

        // ضبط التاريخ حسب from/to لو موجودين
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : $startDate;
        $toDate   = $to   ? Carbon::parse($to)->endOfDay()   : $endDate;

        // جلب السجلات مع الفلاتر
        $query = self::whereBetween('work_date', [$fromDate, $toDate]);

        if ($branch) {
            $query->where('branch_id', $branch);
        }

        $records = $query->get();

        // تجميع حسب الموظف
        $summary = $records->groupBy('employee_id')->map(function ($employeeRecords, $employeeId) use ($startDate, $keyword) {

            $employee = Employee::with('applicant', 'position')->find($employeeId);
            if (!$employee) return null;

            $shift = Shift::find($employee->shift_id);
            $employmentType = strtolower(trim($shift?->name_en ?? ''));

            // ✨ استبعاد أي حد مش Part Time Hours
            if ($employmentType !== 'part time' || $employee->part_time_type !== 'hours') {
                return null;
            }

            // تجهيز الاسم
            $fullName = strtolower(
                ($employee->applicant->first_name ?? '') . ' ' .
                    ($employee->applicant->middle_name ?? '') . ' ' .
                    ($employee->applicant->last_name ?? '')
            );

            $code  = strtolower($employee->code ?? '');
            $phone = strtolower($employee->phone ?? '');

            // فلتر keyword
            if ($keyword) {
                $kw = strtolower($keyword);
                if (
                    !str_contains($fullName, $kw) &&
                    !str_contains($code, $kw) &&
                    !str_contains($phone, $kw)
                ) {
                    return null;
                }
            }

            // ============================
            //   حساب ساعات العمل
            // ============================

            // 1) نجمع الدقايق
            $workedMinutes = (float) $employeeRecords->sum('worked_minutes');

            // 2) نحول الدقايق لساعات
            $workedHours = round($workedMinutes / 60, 2);


            // 3) required ساعات جاهزة
            $required = (float) ($employee->monthly_hours_required ?? 0);

            // 4) حساب الأوفر تايم (بدون علاقة بالشيفت)
            if ($workedHours > $required) {
                $overtime = round($workedHours - $required, 2);
                $deficit = 0;
            } else {
                $overtime = 0;
                $deficit = round($required - $workedHours, 2);
            }


            return [
                'employee_id'   => $employeeId,
                'employee_code' => $employee->code,
                'employee_name' => $fullName,
                'employee_position' => $employee->position->title_en ?? null,

                'month' => $startDate->format('Y-m'),

                'monthly_worked_hours'   => $workedHours*60,
                'monthly_required_hours' => $required,
                'monthly_overtime_hours' => $overtime,
                'monthly_deficit_hours'  => $deficit *60,
            ];
        })
            ->filter()
            ->values();

        return $summary;
    }
}
