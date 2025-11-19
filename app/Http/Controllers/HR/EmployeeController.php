<?php

namespace App\Http\Controllers\HR;

use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Applicant;
use App\Models\Employee;
use Illuminate\Support\Str;
use App\Traits\ApiResponder;
use App\Models\Company;
use App\Models\Shift;
use App\Http\Resources\Employee\EmployeeResource;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Models\Position;
use Carbon\Carbon;


class EmployeeController extends Controller
{
    public function store(StoreEmployeeRequest $request)
    {
        DB::beginTransaction();

        try {
            // 1️⃣ إنشاء المتقدم (Applicant)
            $data = collect($request->validated())
                ->except(['educations', 'experiences', 'skills', 'languages', 'employee'])
                ->toArray();

            $applicant = Applicant::create($data);

            // رفع ملفات Applicant
            foreach (['cv', 'image', 'certification_attatchment'] as $fileField) {
                if ($request->hasFile($fileField)) {
                    $file = $request->file($fileField);
                    $fileName = time() . '_' . $file->getClientOriginalName();
                    $file->move(public_path("assets/library/{$fileField}s"), $fileName);
                    $applicant->$fileField = "assets/library/{$fileField}s/{$fileName}";
                }
            }
            $applicant->save();

            // إنشاء العلاقات المرتبطة بـ Applicant
            if ($request->has('educations')) {
                foreach ($request->educations as $edu) {
                    if (isset($edu['attachment']) && $edu['attachment'] instanceof \Illuminate\Http\UploadedFile) {
                        $file = $edu['attachment'];
                        $fileName = time() . '_' . $file->getClientOriginalName();
                        $file->move(public_path("assets/library/education_attachments"), $fileName);
                        $edu['attachment'] = "assets/library/education_attachments/{$fileName}";
                    }
                    $applicant->educations()->create($edu);
                }
            }

            foreach (['experiences', 'skills', 'languages'] as $relation) {
                if ($request->has($relation)) {
                    foreach ($request->$relation as $item) {
                        $applicant->$relation()->create($item);
                    }
                }
            }

            // 2️⃣ إنشاء الموظف (Employee)
            if ($request->has('employee')) {
                $employeeData = $request->employee;
                $employeeData['applicant_id'] = $applicant->id;

                // التعامل مع الفروع
                $branches = $employeeData['branch_id'] ?? [];
                unset($employeeData['branch_id']);

                // ✅ التصحيح هنا - استخدام array access
                $employeeData['contracts'] = $employeeData['contracts'] ?? [];

                // ✅ التصحيح - التحقق من وجود salary_details كمفتاح في المصفوفة
                if (isset($employeeData['salary_details']) && is_array($employeeData['salary_details'])) {
                    foreach ($employeeData['salary_details'] as $detail) {
                        // معالجة بيانات الراتب هنا
                        echo $detail['department_name'] . ': ' . $detail['amount'];
                    }
                }

                $employeeData['salary_type'] = $employeeData['salary_type'] ?? 'single';

                // حساب مدة العقد
                if (!empty($employeeData['join_date']) && !empty($employeeData['end_date'])) {
                    $diff = Carbon::parse($employeeData['join_date'])->diff(Carbon::parse($employeeData['end_date']));
                    $employeeData['contract_duration'] = "{$diff->y} years, {$diff->m} months";
                }

                $employeeData['shift_id'] = $employeeData['shift_id'] ?? Shift::first()?->id;
                $employeeData['position_id'] = $employeeData['position_id'] ?? Position::first()?->id;

                // إذا الموظف مش Sales → commission = 0
                if (!($employeeData['is_sales'] ?? false)) {
                    $employeeData['commission_percentage'] = 0;
                }

                // رفع ملفات العقود
                if (!empty($employeeData['contracts'])) {
                    $uploadedContracts = [];
                    foreach ($employeeData['contracts'] as $contract) {
                        if ($contract instanceof \Illuminate\Http\UploadedFile) {
                            $fileName = time() . '_' . $contract->getClientOriginalName();
                            $contract->move(public_path("assets/library/contracts"), $fileName);
                            $uploadedContracts[] = "assets/library/contracts/{$fileName}";
                        }
                    }
                    $employeeData['contracts'] = $uploadedContracts;
                }
                $employeeData['status']='accepted';

                $employee = Employee::create($employeeData);

                // ربط الموظف بالفروع
                if ($branches) {
                    if (!is_array($branches)) $branches = [$branches];
                    $employee->branches()->sync($branches);
                }
            }

            DB::commit();

            // تحميل كل العلاقات للرد
            $applicant->load(['educations', 'experiences', 'skills', 'languages', 'employee.branches']);

            return response()->json([
                'status' => true,
                'message' => 'Employee Added successfully 🎉',
                'data' => new EmployeeResource($applicant)
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function index(Request $request)
    {
        $query = Employee::with(['applicant', 'department', 'branches', 'company', 'manager']);

        // فلترة حسب الاسم (first/middle/last/preferred)
        if ($request->filled('name')) {
            $name = $request->query('name');
            $query->whereHas('applicant', function ($q) use ($name) {
                $q->where('first_name', 'like', "%$name%")
                    ->orWhere('middle_name', 'like', "%$name%")
                    ->orWhere('last_name', 'like', "%$name%")
                    ->orWhere('preferred_name', 'like', "%$name%");
            });
        }

        // فلترة حسب كود الموظف
        if ($request->filled('code')) {
            $query->where('code', 'like', "%{$request->query('code')}%");
        }

        // فلترة حسب رقم الهاتف
        if ($request->filled('phone')) {
            $query->whereHas('applicant', function ($q) use ($request) {
                $q->where('phone', 'like', "%{$request->query('phone')}%");
            });
        }

        // فلترة حسب الشركة
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->query('company_id'));
        }

        if ($request->filled('position_applied_for_id')) {
            $query->where('position_applied_for_id', $request->query('position_applied_for_id'));
        }

        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->query('employment_type'));
        }



        // Pagination
        $limit = $request->query('limit', 10);
        $employees = $query->paginate($limit);

        return response()->json([
            'status' => true,
            'data' => $employees->items(),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
            ]
        ]);
    }



    // 🟢 عرض موظف محدد
    public function show($id)
    {
        $employee = Employee::with(['applicant', 'department', 'position', 'branches', 'company'])->findOrFail($id);
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




    public function exportData(Request $request)
    {
        // نتأكد إنها مصفوفة IDs
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:employees,id'
        ]);

        $employees = Employee::with(['applicant', 'department', 'branches', 'company', 'manager'])
            ->whereIn('id', $validated['ids'])
            ->get();

        return response()->json([
            'status' => true,
            'data' => $employees
        ]);
    }



   public function simpleList()
{
    $employees = Employee::with('applicant')
       
        ->get()
        ->map(function ($employee) {
            return [
                'employee_id' => $employee->id,
                'employee_code' => $employee->code,
                'employee_name' => $employee->applicant ? $employee->applicant->first_name . ' ' . $employee->applicant->last_name : null,
            ];
        });

    return response()->json([
        'status' => true,
        'employees' => $employees
    ]);
}



 

}
