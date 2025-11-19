<?php

namespace App\Http\Controllers\HR\Sheets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Shift;
use App\Models\Employee;
use Carbon\Carbon;
use App\Models\Leave;
use App\Models\AttendanceDay;
use Barryvdh\DomPDF\Facade\Pdf;

class AbsentSheetController extends Controller
{
  
    public function index(Request $request)
{
    $employeeId = $request->employee_id;
    $month      = $request->month;

    if (!$employeeId || !$month) {
        return response()->json(['error' => 'employee_id and month are required'], 422);
    }

    $start = Carbon::parse($month)->startOfMonth();
    $end   = Carbon::parse($month)->endOfMonth();

    // 1️⃣ أيام الغياب
    $absentDays = Attendance::where('employee_id', $employeeId)
        ->whereBetween('date', [$start, $end])
        ->where('status', 'absent')
        ->get()
        ->map(function ($r) {
            return [
                'date'   => $r->work_date,
                'reason' => 'Absent',
            ];
        })
        ->values(); 


    // 2️⃣ الإجازات من جدول leaves
    $leaves = Leave::where('employee_id', $employeeId)
        ->where('status', 'approved')
        ->where(function ($q) use ($start, $end) {
            $q->whereBetween('start_date', [$start, $end])
              ->orWhereBetween('end_date', [$start, $end]);
        })
        ->get()
        ->flatMap(function ($leave) {
            $dates = [];
            $from = Carbon::parse($leave->start_date);
            $to   = Carbon::parse($leave->end_date ?? $leave->start_date);

            while ($from->lte($to)) {
                $dates[] = [
                    'date'   => $from->toDateString(),
                    'reason' => ucfirst($leave->leave_type) . ' Leave',
                ];
                $from->addDay();
            }

            return $dates;
        })
        ->values(); // <- مهم جداً


    // 🟣 الدمج بدون أخطاء
    $merged = collect($absentDays)
        ->merge($leaves)
        ->sortBy('date')
        ->values();

    return response()->json([
        'employee_id'  => $employeeId,
        'absent_sheet' => $merged,
    ]);
}





public function exportPdf(Request $request)
{
    $employeeId = $request->employee_id;
    $month      = $request->month;

    if (!$employeeId || !$month) {
        return response()->json(['error' => 'employee_id and month are required'], 422);
    }

    $start = Carbon::parse($month)->startOfMonth();
    $end   = Carbon::parse($month)->endOfMonth();

    // إعادة استخدام الكود اللي عندك للغياب والإجازات
    $absentDays = Attendance::where('employee_id', $employeeId)
        ->whereBetween('date', [$start, $end])
        ->where('status', 'absent')
        ->get()
        ->map(fn($r) => ['date' => $r->work_date, 'reason' => 'Absent'])
        ->values();

    $leaves = Leave::where('employee_id', $employeeId)
        ->where('status', 'approved')
        ->where(function ($q) use ($start, $end) {
            $q->whereBetween('start_date', [$start, $end])
              ->orWhereBetween('end_date', [$start, $end]);
        })
        ->get()
        ->flatMap(function ($leave) {
            $dates = [];
            $from = Carbon::parse($leave->start_date);
            $to   = Carbon::parse($leave->end_date ?? $leave->start_date);
            while ($from->lte($to)) {
                $dates[] = ['date' => $from->toDateString(), 'reason' => ucfirst($leave->leave_type).' Leave'];
                $from->addDay();
            }
            return $dates;
        })
        ->values();

    $merged = collect($absentDays)
        ->merge($leaves)
        ->sortBy('date')
        ->values();

    $employee = Employee::with('applicant')->find($employeeId);    
    $name=$employee->applicant->first_name . ' ' . $employee->applicant->last_name;
    // توليد PDF
    $pdf = Pdf::loadView('sheets.absent_sheet', [
        'employeeName' => $name,
        'month'    => $month,
        'employeeId' => $employeeId,
        'sheet'      => $merged
    ]);

    // ممكن ترجعيه مباشرة للتحميل
  return $pdf->download("Absent_Sheet_{$employeeId}_{$month}.pdf");

    // أو ترجعيه ك base64 لو الفرونت بحاجة يعرضه مباشرة:
    // return response()->json(['pdf' => base64_encode($pdf->output())]);
}

}
