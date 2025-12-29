<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\EmployeeKpi;

class EmployeeKpiController extends Controller
{
    public function bulkUpsert(Request $request)
    {
        $data = $request->validate([
             'month' => 'required|date',
            'items' => 'required|array',
            'items.*.employee_id' => 'required|exists:employees,id',
           
            'items.*.kpi_percent' => 'required|numeric|min:0|max:100',
        ]);

        $month = Carbon::parse($data['month'])->startOfMonth()->toDateString();

        foreach ($data['items'] as $it) {
            EmployeeKpi::updateOrCreate(
                ['employee_id' => $it['employee_id'], 'month' => $month],
                ['kpi_percent' => $it['kpi_percent']]
            );
        }

        return response()->json(['message' => 'KPI saved for month', 'month' => $month]);
    }
}
/////اربط بالمرتب 