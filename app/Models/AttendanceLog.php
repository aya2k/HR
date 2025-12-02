<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
   protected $guarded = [];
   


protected $casts = [
    'logged_at' => 'datetime',
    'raw_payload' => 'array',
];
}
