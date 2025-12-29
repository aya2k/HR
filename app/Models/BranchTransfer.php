<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchTransfer extends Model
{
    protected $fillable = [
        'employee_id',
        'current_branch_id',
        'requested_branch_id',
        'date',
        'status',
        'reason',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function currentBranch()
    {
        return $this->belongsTo(Branch::class, 'current_branch_id');
    }

    public function requestedBranch()
    {
        return $this->belongsTo(Branch::class, 'requested_branch_id');
    }
}
