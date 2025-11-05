<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasLocalization;

class Governorate extends Model
{
    use HasFactory, HasLocalization;

    protected $fillable = ['country_id', 'name_ar', 'name_en'];

    public function cities()
    {
        return $this->hasMany(City::class ,'governorate_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
}
