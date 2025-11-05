<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasLocalization;

class City extends Model
{
    use HasFactory,HasLocalization;

    protected $fillable = ['governorate_id', 'name_ar', 'name_en'];

    public function governorate()
    {
        return $this->belongsTo(Governorate::class , 'governorate_id');
    }
}
