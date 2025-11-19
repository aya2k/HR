<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasLocalization;

class Applicant extends Model
{
    use HasFactory, HasLocalization;

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

    public function certificates()
    {
        return $this->hasMany(ApplicantCertificate::class);
    }

    // Applicant.php
    public function experiences()
    {
        return $this->hasMany(Experience::class)->orderByDesc('is_current')->orderBy('start_date', 'desc');
    }

    public function skills()
    {
        return $this->hasMany(Skill::class, 'applicant_id');
    }
    public function languages()
    {
        return $this->hasMany(Language::class);
    }

    public function educations()
    {
        return $this->hasMany(Education::class);
    }

    
}
