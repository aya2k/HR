<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{

    protected $guarded = [];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }


    protected $casts = [
        'total_hours'        => 'float',
        'overtime_minutes'   => 'float',
        'late_minutes'       => 'float',
    ];

   

}
