<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
     protected $fillable = ['name_ar', 'name_en'];

     public function governorates()
{
    return $this->hasMany(Governorate::class, 'country_id');
}
  
}
