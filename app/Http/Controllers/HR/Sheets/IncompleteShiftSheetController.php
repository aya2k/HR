<?php

namespace App\Http\Controllers\HR\Sheets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;


class IncompleteShiftSheetController extends Controller
{
    public function index(Request $request)
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
        ->where('late_minutes', '>', 0)
        ->get()
        ->map(function ($r) use ($shift) {

            return [
                 'id'               => $r->id,  
                'date'           => $r->date,
                'start_shift_time' => $shift?->start_time,   
                'check_in'      => $r->check_in,       
                'difference'       => $r->late_minutes,
                
            ];
        });

    return response()->json([
        'employee_id' => $employeeId,
        'month'       => $month,
        'late'    => $overtime->values(),
    ]);
}


public function update(Request $request, $id)
{
    $attendance = Attendance::find($id);

    if (!$attendance) {
        return response()->json(['status' => false, 'message' => 'Attendance not found'], 404);
    }

   
    $attendance->update($request->only([
        'check_in',
        'check_out',
        'late_minutes',
        'early_leave_minutes',
        'overtime_minutes',
        'status',
        'day_type'
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
        return response()->json(['status' => false, 'message' => 'Attendance not found'], 404);
    }

    // Soft delete
    $attendance->delete();

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
        ->where('late_minutes', '>', 0)
        ->get()
        ->map(function ($r) use ($shift) {
            return [
                'date'            => $r->date,
                'start_shift_time' => $shift?->start_time,
                'check_in'        => $r->check_in,
                'difference'      => $r->late_minutes,
            ];
        });

    
    $pdf = Pdf::loadView('sheets.late_sheet', [
        'employee' => $employee,
        'month'    => $month,
        'sheet'    => $records
    ]);

    return $pdf->download("Late_Sheet_{$employeeId}_{$month}.pdf");
}
}
