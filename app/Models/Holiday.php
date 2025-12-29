<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = [
        'employee_id',
        'date',
        'from',
        'to',
        'type',
        'has_replacement',
        'replaced_employee_id',
        'status',
        'reason',
        'images',
        'pdf',
    ];

    protected $casts = [
        'has_replacement' => 'boolean',
        'images' => 'array',
        'pdf' => 'array',
    ];

    public function employee() {
        return $this->belongsTo(Employee::class);
    }

    public function replacedEmployee() {
        return $this->belongsTo(Employee::class, 'replaced_employee_id');
    }
}
