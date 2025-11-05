<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Applicant;
use App\Models\Employee;
use Illuminate\Support\Str;
use App\Traits\ApiResponder;

use App\Http\Resources\Employee\EmployeeResource;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Http\Requests\Employee\StoreEmployeeRequest;

class EmployeeController extends Controller
{
    public function store(StoreEmployeeRequest $request)
    {


        $data = collect($request->validated())->except(['educations', 'experiences', 'skills', 'languages'])->toArray();

        $applicant = Applicant::create($data);

        foreach (['cv', 'image', 'certification_attatchment'] as $fileField) {
            if ($request->hasFile($fileField)) {
                $applicant->$fileField = $request->file($fileField)->store($fileField . 's', 'public');
            }
        }
        $applicant->save();

        foreach (['educations', 'experiences', 'skills', 'languages'] as $relation) {
            if ($request->has($relation)) {
                foreach ($request->$relation as $item) {
                    $applicant->$relation()->create($item);
                }
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Application submitted successfully 🎉',
            'data' => new EmployeeResource($applicant->load(['educations', 'experiences', 'skills', 'languages']))
        ], 201);
    }




    public function index(Request $request)
    {
        $employees = Employee::with(['applicant', 'department', 'position', 'branch', 'company'])
            ->latest()
            ->paginate(15);

        return response()->json($employees);
    }

    // 🟢 عرض موظف محدد
    public function show($id)
    {
        $employee = Employee::with(['applicant', 'department', 'position', 'branch', 'company'])->findOrFail($id);
        return response()->json($employee);
    }

    

    // 🟢 تعديل بيانات الموظف
    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $employee->update($request->only([
            'position_id',
            'department_id',
            'branch_id',
            'company_id',
            'shift_id',
            'employment_type',
            'work_mode',
            'join_date',
            'end_date',
            'status',
            'base_salary',
            'hourly_rate',
            'commission_percentage',
            'kpi',
            'salary_method',
            'work_hours',
            'holiday_balance',
            'has_fingerprint',
            'has_location_tracking',
            'weekly_work_days',
        ]));

        return response()->json([
            'message' => 'Employee updated successfully ✏️',
            'employee' => $employee
        ]);
    }

    // 🟢 حذف موظف
    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);
        $employee->delete();

        return response()->json(['message' => 'Employee deleted successfully 🗑️']);
    }
}
