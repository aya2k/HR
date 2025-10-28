<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasLocalization;

class Position extends Model
{
    use HasFactory,HasLocalization;

    protected $fillable = [
        'department_id',
        'title_ar',
        'title_en',
        'description_ar',
        'description_en',
    ];

    
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
