<?php

namespace App\Http\Controllers\HR\Sheets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\AttendanceDay;

class OverTimeSheetController extends Controller
{

   

   public function index(Request $request)
{
    $employeeId = $request->employee_id;
    $month      = $request->month;

    if (!$employeeId || !$month) {
        return response()->json(['error' => 'employee_id and month are required'], 422);
    }

    $employee = Employee::with(['shift', 'workDays'])->find($employeeId);
    if (!$employee) {
        return response()->json(['error' => 'Employee not found'], 404);
    }

    $tz = 'Africa/Cairo';

    // normalize shift name
    $employmentType = strtolower(trim($employee->shift?->name_en ?? ''));
    $partTimeType   = strtolower(trim($employee->part_time_type ?? ''));

    $isFullTime = in_array($employmentType, ['full time', 'full_time']);
    $isPT_Hours = in_array($employmentType, ['part time', 'part_time']) && $partTimeType === 'hours';
    $isPT_Days  = in_array($employmentType, ['part time', 'part_time']) && $partTimeType === 'days';

    $monthCarbon = Carbon::parse($month, $tz);
    $records = Attendance::where('employee_id', $employeeId)
        ->whereYear('date', $monthCarbon->year)
        ->whereMonth('date', $monthCarbon->month)
        ->orderBy('date')
        ->get();

    // ===== Part Time Hours (Monthly threshold, cumulative) =====
    if ($isPT_Hours) {
        $requiredMonthlyMinutes = (float)($employee->monthly_hours_required ?? 0) * 60;

        
        if ($requiredMonthlyMinutes <= 0) {
            return response()->json([
                'error' => 'monthly_hours_required not configured for this employee'
            ], 422);
        }

        $cumulative = 0.0;

        $overtime = $records->map(function ($r) use (&$cumulative, $requiredMonthlyMinutes, $tz) {
            $workedMinutes = (float)$r->total_hours * 60;

            $before = $cumulative;
            $after  = $cumulative + $workedMinutes;

            $overtimeBefore = max(0, $before - $requiredMonthlyMinutes);
            $overtimeAfter  = max(0, $after  - $requiredMonthlyMinutes);

            $overtimeToday = (int) round($overtimeAfter - $overtimeBefore);

            $cumulative = $after;

            return $overtimeToday > 0 ? [
                'id'       => $r->id,
                'date'     => $r->date,
                'check_in' => $r->check_in,
                'check_out'=> $r->check_out,
                'overtime' => $overtimeToday, // minutes
            ] : null;
        })->filter()->values();

        return response()->json([
            'employee_id' => $employeeId,
            'month'       => $monthCarbon->format('Y-m'),
            'overtime'    => $overtime,
        ]);
    }

    // ===== Full Time / Part Time Days (Daily required) =====
    $overtime = $records->map(function ($r) use ($employee, $isFullTime, $isPT_Days, $tz) {

        $date    = Carbon::parse($r->date, $tz);
        $dayName = strtolower($date->format('l'));

        $workedMinutes = (float)$r->total_hours * 60;

        $dailyRequiredMinutes = null;
        $endShiftTime = null;

      
        $wd = $employee->workDays
            ? $employee->workDays->first(fn($d) => strtolower($d->day ?? '') === $dayName)
            : null;

        if ($wd && $wd->start_time && $wd->end_time) {
            $start = Carbon::parse($date->toDateString().' '.$wd->start_time, $tz);
            $end   = Carbon::parse($date->toDateString().' '.$wd->end_time, $tz);
            if ($end->lte($start)) $end->addDay();

            $dailyRequiredMinutes = $start->diffInMinutes($end);
            $endShiftTime = $wd->end_time;
        } else {
            // fallback
            if ($isFullTime) {
                $start = Carbon::parse($date->toDateString().' 08:00', $tz);
                $end   = Carbon::parse($date->toDateString().' 17:00', $tz);
                $dailyRequiredMinutes = $start->diffInMinutes($end);
                $endShiftTime = '17:00';
            } elseif ($isPT_Days) {
               
                return null;
            }
        }

        if (!$dailyRequiredMinutes || $dailyRequiredMinutes <= 0) return null;

        $overtimeMinutes = (int) max(0, $workedMinutes - $dailyRequiredMinutes);

        return $overtimeMinutes > 0 ? [
            'id'             => $r->id,
            'date'           => $r->date,
            'end_shift_time' => $endShiftTime,
            'check_in'       => $r->check_in,
            'check_out'      => $r->check_out,
            'overtime'       => $overtimeMinutes,
        ] : null;

    })->filter()->values();

    return response()->json([
        'employee_id' => $employeeId,
        'month'       => $monthCarbon->format('Y-m'),
        'overtime'    => $overtime,
    ]);
}



    public function update(Request $request, $id)
    {
        $attendance = Attendance::find($id);

        if (!$attendance) {
            return response()->json([
                'status' => false,
                'message' => 'Attendance not found'
            ], 404);
        }

        $attendance->update($request->only([
            'check_out',
            'overtime_minutes'
        ]));

        return response()->json([
            'status' => true,
            'message' => 'Attendance updated successfully',
            'attendance' => $attendance
        ]);
    }


    public function delete($id)
    {
        $attendance = Attendance::find($id);

        if (!$attendance) {
            return response()->json([
                'status' => false,
                'message' => 'Attendance not found'
            ], 404);
        }

        $attendance->delete(); // Soft delete

        return response()->json([
            'status' => true,
            'message' => 'Attendance deleted successfully'
        ]);
    }



    public function exportPdf(Request $request)
    {
        $employeeId = $request->employee_id;
        $month      = $request->month;

        if (!$employeeId || !$month) {
            return response()->json(['error' => 'employee_id and month are required'], 422);
        }

      
        $employee = Employee::with('shift')->find($employeeId);
        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        $shift = $employee->shift;

       
        $records = Attendance::where('employee_id', $employeeId)
            ->whereMonth('date', Carbon::parse($month)->month)
            ->where('overtime_minutes', '>', 0)
            ->get()
            ->map(function ($r) use ($shift) {
                return [
                    'date'           => $r->date,
                    'end_shift_time' => $shift?->end_time,
                    'check_out'      => $r->check_out,
                    'overtime'       => $r->overtime_minutes,
                ];
            });

       
        $pdf = Pdf::loadView('sheets.overtime_sheet', [
            'employee' => $employee,
            'month'    => $month,
            'sheet'    => $records
        ]);

        return $pdf->download("Overtime_Sheet_{$employeeId}_{$month}.pdf");
    }




    public function part_time_index(Request $request)
    {
        $employeeId = $request->employee_id;
        $month      = $request->month;

        if (!$employeeId || !$month) {
            return response()->json(['error' => 'employee_id and month are required'], 422);
        }

        
        $employee = Employee::with('shift')->find($employeeId);
        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        $shift = $employee->shift;

        
        $overtime = Attendance::where('employee_id', $employeeId)
            ->whereMonth('date', Carbon::parse($month)->month)
            ->where('overtime_minutes', '>', 0)
            ->get()
            ->map(function ($r) use ($shift) {

                return [
                    'id'             => $r->id,
                    'date'           => $r->date,
                    'end_shift_time' => $shift?->end_time,
                    'check_in'      => $r->check_in, 
                    'check_out'      => $r->check_out,       
                    'overtime'       => $r->overtime_minutes,

                ];
            });

        return response()->json([
            'employee_id' => $employeeId,
            'month'       => $month,
            'overtime'    => $overtime->values(),
        ]);
    }
}
