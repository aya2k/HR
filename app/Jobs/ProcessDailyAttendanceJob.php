<?php

namespace App\Jobs;

use App\Models\{
    AttendanceDay,
    AttendancePolicy,
    AttendancePunch,
    Employee,
    Holiday,
    Leave,
    Permission,
    Shift
};
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessDailyAttendanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $date;

    /**
     * Create a new job instance.
     */
    public function __construct($date = null)
    {
        // لو التاريخ مش متحدد نحلل اليوم السابق
        $this->date = $date ? Carbon::parse($date)->toDateString() : Carbon::yesterday()->toDateString();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("🔹 Starting attendance processing for {$this->date}");

        $policy = AttendancePolicy::where('is_default', true)->first();
        if (!$policy) {
            Log::error('❌ No default attendance policy found.');
            return;
        }

        $employees = Employee::with('shift', 'branch')->get();

        foreach ($employees as $employee) {
            DB::beginTransaction();

            try {
                $shift = $employee->shift ?? Shift::first();
                $shiftStart = Carbon::parse($shift->start_time);
                $shiftEnd   = Carbon::parse($shift->end_time);
                $requiredMinutes = $shift->duration * 60;

                // 🕓 جلب أول وأخر بصمة لليوم
                $firstIn = AttendancePunch::where('employee_id', $employee->id)
                    ->whereDate('timestamp', $this->date)
                    ->orderBy('timestamp', 'asc')
                    ->first();

                $lastOut = AttendancePunch::where('employee_id', $employee->id)
                    ->whereDate('timestamp', $this->date)
                    ->orderBy('timestamp', 'desc')
                    ->first();

                if (!$firstIn || !$lastOut) {
                    AttendanceDay::updateOrCreate(
                        ['employee_id' => $employee->id, 'work_date' => $this->date],
                        ['status' => 'absent', 'day_type' => 'workday']
                    );
                    DB::commit();
                    continue;
                }

                $checkIn  = Carbon::parse($firstIn->timestamp);
                $checkOut = Carbon::parse($lastOut->timestamp);
                $workedMinutes = max(0, $checkOut->diffInMinutes($checkIn));

                // ⏰ حساب التأخير
                $lateMinutes = $checkIn->gt($shiftStart)
                    ? $checkIn->diffInMinutes($shiftStart)
                    : 0;
                if ($lateMinutes <= $policy->late_grace_minutes) $lateMinutes = 0;

                // 🕕 الانصراف المبكر
                $earlyLeaveMinutes = $checkOut->lt($shiftEnd)
                    ? $shiftEnd->diffInMinutes($checkOut)
                    : 0;

                // ➕ الوقت الإضافي
                $overtimeMinutes = $checkOut->gt($shiftEnd)
                    ? $checkOut->diffInMinutes($shiftEnd)
                    : 0;

                // ✅ تحقق من العطلة والإجازة
                $isHoliday = Holiday::whereDate('date', $this->date)->exists();
                $isLeave = Leave::where('employee_id', $employee->id)
                    ->where('status', 'approved')
                    ->whereDate('start_date', '<=', $this->date)
                    ->whereDate('end_date', '>=', $this->date)
                    ->exists();

                $permissionMinutes = Permission::where('employee_id', $employee->id)
                    ->where('status', 'approved')
                    ->whereDate('date', $this->date)
                    ->sum('minutes');

                // 🟢 تحديد نوع اليوم
                $status = 'present';
                $dayType = 'workday';
                if ($isHoliday) {
                    $status = 'holiday';
                    $dayType = 'holiday';
                } elseif ($isLeave) {
                    $status = 'on_leave';
                    $dayType = 'leave';
                }

                AttendanceDay::updateOrCreate(
                    ['employee_id' => $employee->id, 'work_date' => $this->date],
                    [
                        'branch_id' => $employee->branch_id,
                        'required_minutes' => $requiredMinutes,
                        'break_minutes' => $shift->break_minutes ?? $policy->default_break_minutes,
                        'first_in_at' => $checkIn,
                        'last_out_at' => $checkOut,
                        'worked_minutes' => $workedMinutes,
                        'overtime_minutes' => $overtimeMinutes,
                        'deficit_minutes' => max(0, $requiredMinutes - $workedMinutes),
                        'late_minutes' => $lateMinutes,
                        'early_leave_minutes' => $earlyLeaveMinutes,
                        'permission_minutes' => $permissionMinutes,
                        'punches_count' => AttendancePunch::where('employee_id', $employee->id)
                            ->whereDate('timestamp', $this->date)
                            ->count(),
                        'day_type' => $dayType,
                        'status' => $status,
                        'components' => json_encode([
                            'shift' => $shift->name_en ?? $shift->name_ar,
                            'policy' => $policy->name,
                            'grace' => $policy->late_grace_minutes,
                        ]),
                    ]
                );

                DB::commit();

            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error("❌ Error processing attendance for employee {$employee->id}: " . $e->getMessage());
            }
        }

        Log::info("✅ Finished attendance processing for {$this->date}");
    }
}
