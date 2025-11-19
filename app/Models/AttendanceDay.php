<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class AttendanceDay extends Model
{
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

    public static function getMonthlySummaryAll($month, $from = null, $to = null, $branch = null, $code = null, $name = null, $phone = null)
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
        $summary = $records->groupBy('employee_id')->map(function ($employeeRecords, $employeeId) use ($startDate, $code, $name, $phone) {
            $employee = Employee::with('applicant', 'position')->find($employeeId);

            if (!$employee) return null;

            // فلترة حسب الكود
            if ($code && !str_contains($employee->code, $code)) return null;

            // فلترة حسب الاسم
            $fullName = ($employee->applicant->first_name ?? '') . ' ' .
                ($employee->applicant->middle_name ?? '') . ' ' .
                ($employee->applicant->last_name ?? '');
            if ($name && !str_contains(strtolower($fullName), strtolower($name))) return null;

            // فلترة حسب الهاتف
            if ($phone && !str_contains($employee->phone ?? '', $phone)) return null;


            $presentDays = $employeeRecords->whereNotIn('day_type', ['absent', 'leave'])->count();
            $absentDays  = $employeeRecords->where('day_type', 'absent')->count();
            $totalLate = $employeeRecords->sum(function ($record) {
                return $record->late_minutes + $record->early_leave_minutes;
            });

            $totalOvertime = $employeeRecords->sum('overtime_minutes');

            $daysWithOvertime = $employeeRecords->where('overtime_minutes', '>', 0)->count();
            $daysWithIncompleteShift = $employeeRecords->filter(function ($record) {
                return ($record->late_minutes + $record->early_leave_minutes) > 0;
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
        })->filter()->values(); // filter() لحذف null الناتج عن الفلترة

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
                'employee'=>[
                'code' => $employee->code ?? null,
                'name' => $fullName,
                'position' => $employee->position->title_en ?? null,
                'shift' => $employee->shift->name_en ?? null,
                ],
                'date' => $day,
                'check_in' => $employeeRecords->min('first_in_at'),
                'check_out' => $employeeRecords->max('last_out_at'),
                'worked_minutes' => $employeeRecords->sum('worked_minutes'),
                'total_overtime' => $totalOvertime,
                'status' => $status, 
            ];
        })->filter()->values();

        return $summary;
    }
}
