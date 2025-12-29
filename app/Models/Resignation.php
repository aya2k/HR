<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Resignation extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'status',
        'reason',
        'pdf',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
