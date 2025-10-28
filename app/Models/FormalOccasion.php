<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasLocalization;

class FormalOccasion extends Model
{
     use HasFactory,HasLocalization;
     
     protected $fillable = [
        'title_ar',
        'title_en',
        'description_ar',
        'description_en',
        'date_from',
        'date_to',
        'duration',
    ];
}
