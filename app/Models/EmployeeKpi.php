<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeKpi extends Model
{
    protected $fillable = ['employee_id', 'month', 'kpi_percent'];

    protected $casts = [
        'month' => 'date',
        'kpi_percent' => 'decimal:2',
    ];
}
