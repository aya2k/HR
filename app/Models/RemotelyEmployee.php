<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RemotelyEmployee extends Model
{
   use HasFactory;

    protected $fillable = [
        'employee_id',
        'from',
        'to',
        'status',
        'reason',
        'date'
    ];

    // العلاقة بالموظف
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
