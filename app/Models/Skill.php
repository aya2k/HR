<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasLocalization;

class Skill extends Model
{
     use HasFactory, HasLocalization;

    protected $guarded = [];

    public function applicant()
{
    return $this->belongsTo(Applicant::class);
}


public function employee()
{
    return $this->belongsTo(Employee::class);
}

}
