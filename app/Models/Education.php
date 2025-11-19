<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasLocalization;


class Education extends Model
{
   use HasFactory, HasLocalization;

   protected $table ='educations';

    protected $guarded = [];



    public function files()
{
    return $this->hasMany(EducationFile::class);
}
}
