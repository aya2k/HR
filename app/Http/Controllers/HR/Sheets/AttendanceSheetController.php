<?php

namespace App\Http\Controllers\HR\Sheets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\AttendanceDay;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;


class AttendanceSheetController extends Controller
{
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

        $shift = $employee->shift; 

       
        $overtime = Attendance::where('employee_id', $employeeId)
            ->whereMonth('date', Carbon::parse($month)->month)
            ->whereNot('status', 'absent')
            ->get()
            ->map(function ($r) use ($shift) {

                return [
                    'id'        => $r->id,
                    'date'           => $r->date,
                    'check_in'      => $r->check_in,
                    'check_out'      => $r->check_out,


                ];
            });

        return response()->json([
            'employee_id' => $employeeId,
            'month'       => $month,
            'sheet'    => $overtime->values(),
        ]);
    }


    public function update(Request $request, $id)
    {
        $attendance = Attendance::find($id);
        if (!$attendance) {
            return response()->json(['error' => 'Attendance not found'], 404);
        }

        $attendance->update($request->only(['check_in', 'check_out', 'status']));


      
        if ($attendance->date) {
            $day = AttendanceDay::where('employee_id', $attendance->employee_id)
                ->where('work_date', $attendance->date)
                ->first();

            if ($day) {
               
                $checkIn  = $attendance->check_in ? Carbon::parse($attendance->check_in) : null;
                $checkOut = $attendance->check_out ? Carbon::parse($attendance->check_out) : null;

                $worked = ($checkIn && $checkOut) ? $checkIn->diffInMinutes($checkOut) : 0;

                $day->update([
                    'first_in_at' => $checkIn,
                    'last_out_at' => $checkOut,
                    'worked_minutes' => $worked,
                    'status' => ($checkIn && $checkOut) ? 'complete' : 'incomplete',
                ]);
            }
        }


        return response()->json([
            'message'    => 'Attendance updated',
            'attendance' => $attendance
        ]);
    }

    public function delete($id)
    {
        $attendance = Attendance::find($id);
        if (!$attendance) {
            return response()->json(['error' => 'Attendance not found'], 404);
        }

         AttendanceDay::where('employee_id', $attendance->employee_id)
                 ->where('work_date', $attendance->date)
                 ->delete();

        $attendance->delete();

        return response()->json([
            'message' => 'Attendance deleted'
        ]);
    }


    public function exportPdf(Request $request)
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

        $shift = $employee->shift;

       
        $records = Attendance::where('employee_id', $employeeId)
            ->whereMonth('date', Carbon::parse($month)->month)
            ->whereNot('status', 'absent')
            ->get()
            ->map(function ($r) use ($shift) {
                return [
                    'date'      => $r->date,
                    'check_in'  => $r->check_in,
                    'check_out' => $r->check_out,
                ];
            });

        
        $pdf = Pdf::loadView('sheets.attendance_sheet', [
            'employee' => $employee,
            'month'    => $month,
            'sheet'    => $records
        ]);

        return $pdf->download("Attendance_Sheet_{$employeeId}_{$month}.pdf");
    }
}
