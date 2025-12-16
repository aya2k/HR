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
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;


class MonthlyAttendanceController extends Controller
{

    public function getMonthlyReportAll(Request $request)
    {
        $from    = $request->query('from');
        $to      = $request->query('to');
        $branch  = $request->query('branch');
        $keyword = $request->query('keyword');
        $month   = $request->query('month', now()->format('Y-m'));

        
        $limit = $request->integer('limit', 10);

       
        $summary = AttendanceDay::getMonthlySummaryAll($month, $from, $to, $branch, $keyword);

       
        $page = $request->get('page', 1);
        $collection = collect($summary);

        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $collection->forPage($page, $limit),
            $collection->count(),
            $limit,
            $page,
            ['path' => url()->current(), 'query' => $request->query()]
        );

        return response()->json([
            'status' => true,
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ]
        ]);
    }




    public function exportMonthlyReportAllPdf(Request $request)
    {
        $from   = $request->query('from');   
        $to     = $request->query('to');     
        $branch = $request->query('branch'); 
        $code   = $request->query('code');   
        $name   = $request->query('name');   
        $phone  = $request->query('phone'); 
        $month  = $request->query('month', now()->format('Y-m')); 

       
        $summary = AttendanceDay::getMonthlySummaryAll($month, $from, $to, $branch, $code, $name, $phone);

      
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

        $collection = collect($summary);

        /*
     |--------------------------------------
     |  keyword (name OR phone OR code)
     |--------------------------------------
    */
        if ($request->has('keyword') && !empty($request->keyword)) {

            $keyword = strtolower($request->keyword);

            $collection = $collection->filter(function ($item) use ($keyword) {

                $name  = strtolower($item['employee']['first_name'] ?? '');
                $code  = strtolower($item['employee']['code'] ?? '');
                $phone = strtolower($item['employee']['phone'] ?? '');

                return str_contains($name, $keyword)
                    || str_contains($code, $keyword)
                    || str_contains($phone, $keyword);
            });
        }

        /*
     |--------------------------------------
     |  department
     |--------------------------------------
    */
        if ($request->has('department') && !empty($request->department)) {

            $department = strtolower($request->department);

            $collection = $collection->filter(function ($item) use ($department) {

                $dept = strtolower($item['employee']['department'] ?? '');

                return $dept === $department;
            });
        }

        /*
     |--------------------------------------
     |  Pagination 
     |--------------------------------------
    */
        $page = $request->query('page', 1);
        $perPage = 10;

        $items = array_values($collection->forPage($page, $perPage)->toArray());

        $paginated = new LengthAwarePaginator(
            $items,
            $collection->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json([
            'status' => true,
            'date' => $day,
            'attendances' => $paginated,
        ]);
    }










    ////========================================================================== part time sheet

    public function partTimeHoursReport(Request $request)
    {
        $month   = $request->month ?? now()->format('Y-m');
        $from    = $request->from;
        $to      = $request->to;
        $branch  = $request->branch_id;
        $keyword = $request->keyword;

        $data = AttendanceDay::getMonthlySummaryPartTimeHours($month, $from, $to, $branch, $keyword);

        return response()->json($data);
    }
}
