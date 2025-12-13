<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasLocalization;

class Employee extends Model
{
    use HasFactory, HasLocalization;

    protected $guarded = [];
    protected $casts = [
    'salary_details' => 'array',
    'contracts' => 'array',
];


    // Applicant
    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    // Department
    public function department()
    {
        return $this->belongsTo(Department::class);
    }


    public function workDays()
{
    return $this->hasMany(EmployeeWorkDay::class);
}

    

public function managedDepartment()
{
    return $this->belongsTo(Department::class, 'managed_department_id');
}

public function managedBranch()
{
    return $this->belongsTo(Branch::class, 'managed_branch_id');
}



    // Position
    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }


    // Company
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Manager (self-referencing)
    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function subordinates()
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    // Branches (many-to-many)
    public function branches()
    {
        return $this->belongsToMany(Branch::class)->withTimestamps();
    }

    // Shift
    public function shift()
    {
        return $this->belongsTo(Shift::class , 'shift_id');
    }

    // Experiences through applicant
    public function experiences()
    {
        return $this->hasManyThrough(
            Experience::class,
            Applicant::class,
            'id',           // applicant.id
            'applicant_id', // experience.applicant_id
            'applicant_id', // employee.applicant_id
            'id'            // applicant.id
        );
    }

    // Check if employee is manager for a branch
    public function managesBranch($branchId)
    {
        if ($this->manager_for_all_branches) return true;
        return $this->branches->contains('id', $branchId);
    }

    // Check if employee is manager of department
    public function isDepartmentManager()
    {
        return $this->is_manager && $this->is_department_manager;
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }

    public function attendancePolicy()
    {
        return AttendancePolicy::where('is_default', true)->first();
    }

    public function payrollPolicy()
    {
        return PayrollPolicy::where('is_default', true)->first();
    }

    public function attendanceDays()
{
    return $this->hasMany(AttendanceDay::class);
}

public function contracts()
    {
        return $this->hasMany(EmployeeContract::class);
    }

    public function salaryDetails()
    {
        return $this->hasMany(EmployeeSalaryDetail::class);
    }
}
