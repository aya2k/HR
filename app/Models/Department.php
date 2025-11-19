<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasLocalization;

class Department extends Model
{
    use HasFactory,HasLocalization;

    protected $fillable = [
        'name_ar',
        'name_en',
        'description_ar',
        'description_en',
         'phones',
    ];

    protected $casts = [
    'phones' => 'array',
];


     public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // الموظفين التابعين للقسم
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function manager()
    {
        return $this->hasOne(Employee::class)->where('is_department_manager', true);
    }
}
