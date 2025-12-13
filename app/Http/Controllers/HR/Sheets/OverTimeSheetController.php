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

    // public function index(Request $request)
    // {
    //     $employeeId = $request->employee_id;
    //     $month      = $request->month;

    //     if (!$employeeId || !$month) {
    //         return response()->json(['error' => 'employee_id and month are required'], 422);
    //     }

    //     // نجيب الموظف مع الشيفت فقط
    //     $employee = Employee::with('shift')->find($employeeId);
    //     if (!$employee) {
    //         return response()->json(['error' => 'Employee not found'], 404);
    //     }

    //     $shift = $employee->shift; // فيه end_time جاهز

    //     // نجيب الايام اللي فيها اوفر تايم من جدول attendances مباشرة
    //     $overtime = Attendance::where('employee_id', $employeeId)
    //         ->whereMonth('date', Carbon::parse($month)->month)
    //         ->where('overtime_minutes', '>', 0)
    //         ->get()
    //         ->map(function ($r) use ($shift) {

    //             return [
    //                 'id'             => $r->id,
    //                 'date'           => $r->date,
    //                 'end_shift_time' => $shift?->end_time,
    //                 'check_in'      => $r->check_in, // ====> SELECT فقط من جدول الشيفت
    //                 'check_out'      => $r->check_out,       // ====> SELECT مباشرة من attendance
    //                 'overtime'       => $r->overtime_minutes,

    //             ];
    //         });

    //     return response()->json([
    //         'employee_id' => $employeeId,
    //         'month'       => $month,
    //         'overtime'    => $overtime->values(),
    //     ]);
    // }


    public function index(Request $request)
    {
        $employeeId = $request->employee_id;
        $month      = $request->month;

        if (!$employeeId || !$month) {
            return response()->json(['error' => 'employee_id and month are required'], 422);
        }

        $employee = Employee::find($employeeId);
        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        $employmentType = $employee->shift?->name_en;   // full_time | part_time
        $partTimeType   = $employee->part_time_type;    // hours | days

        $isFullTime     = $employmentType === 'full_time';
        $isPT_Hours     = $employmentType === 'part_time' && $partTimeType === 'hours';
        $isPT_Days      = $employmentType === 'part_time' && $partTimeType === 'days';

        $tz = 'Africa/Cairo';

        // نجيب الاوفر تايم من جدول attendance
        $records = Attendance::where('employee_id', $employeeId)
            ->whereMonth('date', Carbon::parse($month)->month)
            ->where('overtime_minutes', '>', 0)
            ->get();

        $overtime = $records->map(function ($r) use ($employee, $isFullTime, $isPT_Days, $isPT_Hours, $tz) {

            $date = Carbon::parse($r->date, $tz);
            $dayName = $date->format('l'); // Monday, Tuesday ...

            $endShiftTime = null;

            if ($isFullTime || $isPT_Days) {

                // 🟦 weekly work days موجودة جوة جدول الموظفين
                $days = $employee->days ?? [];

                $todayShift = collect($days)->firstWhere('day', $dayName);

                if ($todayShift) {
                    $endShiftTime = $todayShift['end_time'] ?? null;
                }
            } elseif ($isPT_Hours) {
                // Part time hours → مفيش اى end_time ثابت
                $endShiftTime = null;
            }

            return [
                'id'             => $r->id,
                'date'           => $r->date,
                'end_shift_time' => $endShiftTime,   // ← تم التعديل
                'check_in'       => $r->check_in,
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

        // جلب بيانات الموظف مع الشيفت
        $employee = Employee::with('shift')->find($employeeId);
        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        $shift = $employee->shift;

        // جلب أيام الأوفر تايم للشهر
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

        // توليد PDF
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

        // نجيب الموظف مع الشيفت فقط
        $employee = Employee::with('shift')->find($employeeId);
        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        $shift = $employee->shift; // فيه end_time جاهز

        // نجيب الايام اللي فيها اوفر تايم من جدول attendances مباشرة
        $overtime = Attendance::where('employee_id', $employeeId)
            ->whereMonth('date', Carbon::parse($month)->month)
            ->where('overtime_minutes', '>', 0)
            ->get()
            ->map(function ($r) use ($shift) {

                return [
                    'id'             => $r->id,
                    'date'           => $r->date,
                    'end_shift_time' => $shift?->end_time,
                    'check_in'      => $r->check_in, // ====> SELECT فقط من جدول الشيفت
                    'check_out'      => $r->check_out,       // ====> SELECT مباشرة من attendance
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
