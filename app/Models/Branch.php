<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasLocalization;

class Branch extends Model
{
    use HasFactory, HasLocalization;

    protected $fillable = [
        'company_id',
        'name_ar',
        'name_en',
        'address_ar',
        'address_en',
        'city_ar',
        'city_en',
         'phones',
    ];

    protected $casts = [
    'phones' => 'array',
];


    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Employees in this branch
    public function employees()
    {
        return $this->belongsToMany(Employee::class)->withTimestamps();
    }

    // Branch manager(s)
    public function managers()
    {
        return $this->employees()->where('is_manager', true);
    }
}
