<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttendancePolicy extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'default_required',
        'default_break',
        'late_grace',
        'early_grace',
        'max_daily_deficit_compensate',
        'overtime_rules',
        'penalties',
    ];

    protected $casts = [
        'overtime_rules' => 'array',
        'penalties' => 'array',
    ];

    
}
