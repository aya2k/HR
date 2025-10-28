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
    ];
}
