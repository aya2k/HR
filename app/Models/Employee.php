<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasLocalization;

class Employee extends Model
{
    use HasFactory, HasLocalization;

    protected $guarded = [];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function experiences()
    {
        return $this->hasManyThrough(Experience::class, Applicant::class, 'id', 'applicant_id', 'applicant_id', 'id');
    }
}
