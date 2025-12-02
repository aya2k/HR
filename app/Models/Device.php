<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Device extends Model
{
     use HasFactory;

    protected $fillable = [
        'ip',
        'port',
        'comm_password',
        'branch_name',
    ];
}
