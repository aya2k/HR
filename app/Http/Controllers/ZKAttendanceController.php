<?php

// app/Http/Controllers/AttendanceController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceLog;
use App\Models\Attendance;
use App\Models\AttendanceDay;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class  ZKAttendanceController extends Controller
{
  public function import(Request $request)
{
    $data = $request->input('data', []);

    if (empty($data)) {
        return response()->json([
            'status' => false,
            'message' => 'No attendance data provided.'
        ], 400);
    }

    // ============== 1️⃣ إزالة التكرارات حسب (uid + logged_at) ==============
    $filteredData = collect($data)
        ->unique(fn($i) => ($i['uid'] ?? '') . ($i['logged_at'] ?? ''))
        ->values()
        ->all();

    DB::beginTransaction();
    try {
        foreach ($filteredData as $item) {

            // ============== 2️⃣ حفظ سجل البصمة ==============
            AttendanceLog::updateOrCreate(
                [
                    'device_sn' => $item['device_sn'] ?? null,
                    'uid'       => $item['uid'] ?? null,
                    'log_id'    => $item['log_id'] ?? null,
                ],
                [
                    'logged_at'   => $item['logged_at'] ?? now(),
                    'state'       => $item['state'] ?? null,
                    'type'        => $item['type'] ?? null,
                    'raw_payload' => json_encode($item),
                ]
            );

            // ============== 3️⃣ تحديث حضور الموظف ==============
            $employee = Employee::where('code', $item['uid'])->first();
            if (!$employee) continue;

            $timestamp = Carbon::parse($item['logged_at']);
            $workDate  = $timestamp->toDateString();

            $isCheckIn  = $item['state'] == 0;
            $isCheckOut = $item['state'] == 1;

            // الحصول على السجل الحالي
            $attendance = Attendance::firstOrNew([
                'employee_id' => $employee->id,
                'date'        => $workDate,
            ]);

            $existingCheckIn  = $attendance->check_in;
            $existingCheckOut = $attendance->check_out;

            // check-in
            if ($isCheckIn && !$existingCheckIn) {
                $attendance->check_in = $timestamp->format('H:i:s');
            }

            // check-out
            if ($isCheckOut) {
                $attendance->check_out = $timestamp->format('H:i:s');
            }

            // حساب ساعات العمل
            $checkInTime  = $attendance->check_in ? Carbon::parse($attendance->check_in) : null;
            $checkOutTime = $attendance->check_out ? Carbon::parse($attendance->check_out) : null;

            $workedMinutes = 0;
            if ($checkInTime && $checkOutTime) {
                $workedMinutes = $checkInTime->diffInMinutes($checkOutTime);
            }

            $attendance->total_hours = $workedMinutes / 60;
            $attendance->overtime_minutes = 0;
            $attendance->late_minutes     = 0;
            $attendance->fingerprint_verified = true;
            $attendance->status = 'present';
            $attendance->save();


            // ============== 4️⃣ تحديث ملخص اليوم ==============
            $day = AttendanceDay::firstOrNew([
                'employee_id' => $employee->id,
                'work_date'   => $workDate
            ]);

            $day->branch_id      = $employee->branch_id;
            $day->first_in_at    = $checkInTime;
            $day->last_out_at    = $checkOutTime;
            $day->worked_minutes = $workedMinutes;

            $day->required_minutes = 0;
            $day->break_minutes    = 0;
            $day->overtime_minutes = 0;
            $day->deficit_minutes  = 0;
            $day->late_minutes     = 0;
            $day->early_leave_minutes = 0;

            $day->punches_count = ($checkInTime ? 1 : 0) + ($checkOutTime ? 1 : 0);
            $day->status = ($checkInTime && $checkOutTime) ? 'complete' : 'partial';
            $day->day_type = 'workday';

            $day->save();
        }

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'Attendance imported successfully.'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}


}


    





