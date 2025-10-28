<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasLocalization;

class Applicant extends Model
{
   use HasFactory ,HasLocalization;

    protected $guarded = [];

    protected $casts = [
        'skills' => 'array',
        'courses' => 'array',
        'previous_jobs' => 'array',
    ];

    public function employee()
{
    return $this->hasOne(Employee::class);
}

}
