<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Overtime extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'from',
        'to',
        'status',
        'reason',
    ];

    // العلاقة بالموظف
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
