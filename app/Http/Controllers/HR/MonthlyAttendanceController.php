<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\Attendance\MonthlyAttendanceResource;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Shift;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Leave;
use App\Models\AttendanceDay;
use App\Models\AttendancePolicy;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;


class MonthlyAttendanceController extends Controller
{
   
   public function getMonthlyReportAll(Request $request)
{
    $from   = $request->query('from'); // تاريخ البداية
    $to     = $request->query('to');   // تاريخ النهاية
    $branch = $request->query('branch'); 
    $code   = $request->query('code'); 
    $name   = $request->query('name'); 
    $phone  = $request->query('phone'); 

    $month = $request->query('month', now()->format('Y-m')); 

    $summary = AttendanceDay::getMonthlySummaryAll($month, $from, $to, $branch, $code, $name, $phone);

    return response()->json([
        'status' => true,
        'data' => $summary,
    ]);
}



public function exportMonthlyReportAllPdf(Request $request)
{
    $from   = $request->query('from');   // تاريخ البداية اختياري
    $to     = $request->query('to');     // تاريخ النهاية اختياري
    $branch = $request->query('branch'); // فلتر فرع اختياري
    $code   = $request->query('code');   // فلتر كود اختياري
    $name   = $request->query('name');   // فلتر اسم اختياري
    $phone  = $request->query('phone');  // فلتر هاتف اختياري
    $month  = $request->query('month', now()->format('Y-m')); // الشهر المطلوب

    // جلب البيانات لجميع الموظفين باستخدام الفانكشن الموجود
    $summary = AttendanceDay::getMonthlySummaryAll($month, $from, $to, $branch, $code, $name, $phone);

    // توليد PDF
    $pdf = Pdf::loadView('Sheets.monthly_report', [
        'summary' => $summary,
        'month'   => $month,
        'from'    => $from,
        'to'      => $to,
    ]);

    return $pdf->download("Monthly_Report_All_{$month}.pdf");
}


public function getDailyReport(Request $request)
{
    $day = $request->query('day');
    if (!$day) {
        return response()->json([
            'status' => false,
            'message' => 'day query parameter is required, example: ?day=2025-11-17'
        ], 400);
    }

    $summary = AttendanceDay::getDailySummary($day);

    return response()->json([
        'status' => true,
        'date' => $day,
        'attendances' => $summary,
    ]);
}




    
}
