<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class Leave extends Model
{
    protected $fillable = [
        'employee_id',
        'date',
        'from',
        'to',
        'type',
        'status',
        'has_replacement',
        'replaced_employee_id',
    ];


    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function replacedEmployee()
    {
        return $this->belongsTo(Employee::class, 'replaced_employee_id');
    }

    protected $casts = [
        'has_replacement' => 'boolean',
        'date' => 'date',
    ];

    protected function date(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::parse($value)->format('Y-m-d'),
        );
    }
}
