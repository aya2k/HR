<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use Carbon\Carbon;
use App\Models\AttendanceDay;
use App\Models\Attendance;


class GeneralController extends Controller
{

    public function Home_header()
    {
        // إجمالي عدد الموظفين
        $total_employees = Employee::count();

        // Full Time
        $full_time = Employee::whereHas('shift', function ($q) {
            $q->where('name_en', 'full time');
        })->count();

        // Part Time
        $part_time = Employee::whereHas('shift', function ($q) {
            $q->where('name_en', 'part time');
        })->count();

        // Freelancer
        $freelance = Employee::whereHas('shift', function ($q) {
            $q->where('name_en', 'freelancer');
        })->count();

        return response()->json([
            'total_employees'   => $total_employees,
            'full_time_count'   => $full_time,
            'part_time_count'   => $part_time,
            'freelance_count'   => $freelance,
        ]);
    }







    public function getDailySummaryCards(Request $request)
    {
        $from    = $request->query('from');
        $to      = $request->query('to');
        $branch  = $request->query('branch');
        $keyword = $request->query('keyword');

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : now()->startOfMonth();
        $toDate   = $to   ? Carbon::parse($to)->endOfDay()   : now()->endOfMonth();

        // ================= BASE QUERY =================
        $baseQuery = AttendanceDay::query()
            ->whereBetween('work_date', [$fromDate, $toDate])
            ->whereNull('deleted_at');

        if ($branch) {
            $baseQuery->where('branch_id', $branch);
        }

        if ($keyword) {
            $baseQuery->whereHas('employee', function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('employee_code', 'like', "%{$keyword}%");
            });
        }

        // ================= PRESENT =================
        $presentQuery = (clone $baseQuery)
            ->where('day_type', 'workday')
            ->whereNotNull('first_in_at');

        $totalPresent = $presentQuery->distinct('employee_id')->count('employee_id');

        // ================= ON TIME =================
        $onTime = (clone $presentQuery)
            ->where(function ($q) {
                $q->whereNull('late_minutes')
                    ->orWhere('late_minutes', '<=', 15);
            })
            ->where(function ($q) {
                $q->whereNull('early_leave_minutes')
                    ->orWhere('early_leave_minutes', 0);
            })
            ->count();

        // ================= LATE =================
        $late = (clone $presentQuery)
            ->where('late_minutes', '>', 15)
            ->count();

        // ================= EARLY =================
        $early = (clone $presentQuery)
            ->where('early_leave_minutes', '>', 0)
            ->count();

        // ================= ABSENT =================
        $absent = (clone $baseQuery)
            ->where('day_type', 'absent')
            ->distinct('employee_id')
            ->count('employee_id');

        // ================= AWAY =================
        $away = [
            'leave'      => (clone $baseQuery)->where('day_type', 'leave')->count(),
            'permission' => (clone $baseQuery)->where('day_type', 'permission')->count(),
            'holiday'    => (clone $baseQuery)->where('day_type', 'holiday')->count(),
        ];

        return response()->json([
            'present_summary' => [
                'total_present' => $totalPresent,
                'on_time'       => $onTime,
                'late'          => $late,
                'early'         => 0,
            ],
            'away_summary' => $away,
            'absent_summary' => [
                'absent_employees' => $absent,
            ],
        ]);
    }



    public function getLastMonthAttendanceSummary()
    {
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth   = Carbon::now()->subMonth()->endOfMonth();

        $daysInMonth = $startOfLastMonth->daysInMonth;

        $chart = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $startOfLastMonth->copy()->day($day)->toDateString();

            $attendCount = Attendance::whereDate('date', $date)
                ->where('status', 'present') // assuming status column stores attendance
                ->count();

            $notAttendCount = Attendance::whereDate('date', $date)
                ->where('status', 'absent') // assuming status column stores absence
                ->count();

            $chart[] = [
                'day'       => $day,
                'attend'    => $attendCount,
                'notAttend' => $notAttendCount,
                
            ];
        }

        return response()->json($chart);
    }
}
