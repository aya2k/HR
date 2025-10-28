<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasLocalization;

class Shift extends Model
{
    use HasFactory,HasLocalization;

    protected $fillable = [
        'name_ar',
        'name_en',
        'start_time',
        'end_time',
        'break_minutes',
    ];

   
    public function getWorkingHoursAttribute()
    {
        $start = strtotime($this->start_time);
        $end = strtotime($this->end_time);
        $hours = ($end - $start) / 3600;
        return max($hours - ($this->break_minutes / 60), 0);
    }
}
