<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Loan extends Model
{
     use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'amount',
        'status',
        'reason',
        'monthly_amount',
        'months'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
