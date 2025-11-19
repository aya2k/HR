<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasLocalization;

class Company extends Model
{
   use HasFactory,HasLocalization;

    protected $fillable = [
        'name_ar',
        'name_en',
        'address_ar',
        'address_en',
        'phones',
        'email',
        'attendance_policy_id',
        'payroll_policy_id'
    ];


    protected $casts = [
    'phones' => 'array',
];


    public function attendancePolicy()
{
    return $this->belongsTo(AttendancePolicy::class);
}

public function payrollPolicy()
{
    return $this->belongsTo(PayrollPolicy::class);
}

}
