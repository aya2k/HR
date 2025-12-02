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

            // تجهيز الاسم الكامل
            $fullName = strtolower(
                ($employee->applicant->first_name ?? '') . ' ' .
                    ($employee->applicant->middle_name ?? '') . ' ' .
                    ($employee->applicant->last_name ?? '')
            );

            $code  = strtolower($employee->code ?? '');
            $phone = strtolower($employee->phone ?? '');

            // 🔍 فلتر keyword
            if ($keyword) {
                $kw = strtolower($keyword);

                if (
                    !str_contains($fullName, $kw) &&
                    !str_contains($code, $kw) &&
                    !str_contains($phone, $kw)
                ) {
                    return null; // استبعاد
                }
            }

            // جمع البيانات
            $presentDays = $employeeRecords->whereNull('deleted_at')->whereNotIn('day_type', ['absent', 'leave'])->count();
            $absentDays  = $employeeRecords->where('day_type', 'absent')->count();

            $totalLate = $employeeRecords->sum(function ($record) {
                return $record->late_minutes + $record->early_leave_minutes;
            });

            $totalOvertime = $employeeRecords->sum('overtime_minutes');

            $daysWithOvertime = $employeeRecords
                ->whereNull('deleted_at')      // استبعاد المحذوفين
                ->where('overtime_minutes', '>', 0)
                ->count();


            // $daysWithIncompleteShift = $employeeRecords->filter(function ($record) {
            //     return ($record->late_minutes + $record->early_leave_minutes) > 0;
            // })->count();


            $daysWithIncompleteShift = $employeeRecords
                ->filter(function ($record) {
                    if (!empty($record->deleted_at)) {
                        return false; // ❌ استبعاد المحذوفين
                    }

                    return ($record->late_minutes + $record->early_leave_minutes) > 0;
                })
                ->count();


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
            ->filter()  // لحذف null
            ->values(); // إعادة ترتيب

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
}
