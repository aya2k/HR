<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;

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
}
