<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penalty extends Model
{
      protected $fillable = ['employee_id', 'date', 'type', 'amount'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
