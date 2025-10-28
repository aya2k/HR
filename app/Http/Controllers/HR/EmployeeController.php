<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Applicant;
use App\Models\Employee;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
     public function hireApplicant(Request $request, $id)
    {
        $applicant = Applicant::findOrFail($id);

        // 🧩 تحديث بيانات الـ Applicant (اختياري)
        $applicant->update($request->only([
            'first_name',
            'second_name',
            'last_name',
            'email',
            'phone',
            'gender',
            'birth_date',
            'marital_status',
            'governorate_id',
            'city_id',
            'address_details',
            'expected_salary',
        ]));

        // ✅ إنشاء موظف جديد من المتقدم
        $employee = Employee::create([
            'applicant_id'       => $applicant->id,
            'code'               => 'EMP-' . Str::upper(Str::random(6)),
            'position_id'        => $request->position_id,
            'department_id'      => $request->department_id,
            'branch_id'          => $request->branch_id,
            'company_id'         => $request->company_id,
            'shift_id'           => $request->shift_id,
            'employment_type'    => $request->employment_type ?? 'full_time',
            'work_mode'          => $request->work_mode ?? 'on_site',
            'join_date'          => $request->join_date,
            'base_salary'        => $request->base_salary ?? $applicant->expected_salary ?? 0,
            'status'             => 'active',
        ]);

        // 🔄 تحديث حالة الـ Applicant إلى "accepted"
        $applicant->update(['status' => 'accepted']);

        return response()->json([
            'message' => 'Applicant hired successfully 🎉',
            'employee' => $employee,
            'applicant' => $applicant,
        ]);
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

    // 🟢 إنشاء موظف جديد
    public function store(Request $request)
    {
        $validated = $request->validate([
            'applicant_id' => 'nullable|exists:applicants,id',
            'position_id' => 'nullable|exists:positions,id',
            'department_id' => 'nullable|exists:departments,id',
            'branch_id' => 'nullable|exists:branches,id',
            'company_id' => 'nullable|exists:companies,id',
            'shift_id' => 'nullable|exists:shifts,id',
            'join_date' => 'nullable|date',
            'base_salary' => 'nullable|numeric',
        ]);

        $employee = Employee::create(array_merge($validated, [
            'code' => 'EMP-' . strtoupper(Str::random(6)),
            'status' => $request->status ?? 'active',
            'employment_type' => $request->employment_type ?? 'full_time',
            'work_mode' => $request->work_mode ?? 'on_site',
            'base_salary' => $request->base_salary ?? 0,
        ]));

        return response()->json([
            'message' => 'Employee created successfully ✅',
            'employee' => $employee
        ], 201);
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
