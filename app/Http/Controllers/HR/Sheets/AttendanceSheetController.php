<?php

namespace App\Http\Controllers\HR\Sheets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Attendance;
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

        // نجيب الموظف مع الشيفت فقط
        $employee = Employee::find($employeeId);
        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        $shift = $employee->shift; // فيه end_time جاهز

        // نجيب الايام اللي فيها اوفر تايم من جدول attendances مباشرة
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

        // جلب بيانات الموظف مع الشيفت
        $employee = Employee::find($employeeId);
        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        $shift = $employee->shift;

        // جلب الحضور للشهر
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

        // توليد PDF
        $pdf = Pdf::loadView('sheets.attendance_sheet', [
            'employee' => $employee,
            'month'    => $month,
            'sheet'    => $records
        ]);

        return $pdf->download("Attendance_Sheet_{$employeeId}_{$month}.pdf");
    }
}
