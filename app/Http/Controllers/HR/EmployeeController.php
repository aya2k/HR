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
use App\Http\Resources\Employee\PrivateEmployeeResource;
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
            /** ============================
             *  1️⃣ إنشاء Applicant
             *  ============================ */
            $data = collect($request->validated())
                ->except(['educations', 'experiences', 'skills', 'languages', 'employee'])
                ->toArray();

            $applicant = new Applicant();
            $applicant->fill($data);

            /** ============================
             *  رفع الملفات الأساسية (cv - image - certification_attatchment)
             *  ============================ */

            foreach (['cv', 'image', 'certification_attatchment'] as $fileField) {

                if ($request->hasFile($fileField)) {

                    $file = $request->file($fileField);
                    $originalName = $file->getClientOriginalName();
                    $fileName = time() . '_' . str_replace(' ', '_', $originalName);
                    $folder = "assets/library/{$fileField}s/";
                    $destinationPath = public_path($folder);

                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0777, true);
                    }

                    $file->move($destinationPath, $fileName);

                    // حفظ بنفس طريقة الكتب
                    $applicant->$fileField = "public/" . $folder . $fileName;
                }
            }




            $applicant->save();

            /** ============================
             *  2️⃣ رفع ملفات التعليم
             *  ============================ */
            if ($request->has('educations')) {
                foreach ($request->educations as $edu) {

                    if (isset($edu['attachment']) && $edu['attachment'] instanceof \Illuminate\Http\UploadedFile) {

                        $file       = $edu['attachment'];
                        $fileName   = time() . '_' . $file->getClientOriginalName();
                        $folder     = "assets/library/education_attachments/";
                        $path       = public_path($folder);

                        if (!file_exists($path)) {
                            mkdir($path, 0777, true);
                        }

                        $file->move($path, $fileName);

                        // نفس طريقة الكتب
                        $edu['attachment'] = "public/" . $folder . $fileName;
                    }

                    $applicant->educations()->create($edu);
                }
            }

            /** ============================
             *  3️⃣ رفع العلاقات الأخرى
             *  ============================ */
            foreach (['experiences', 'skills', 'languages'] as $relation) {
                if ($request->has($relation)) {
                    foreach ($request->$relation as $item) {
                        $applicant->$relation()->create($item);
                    }
                }
            }

            /** ============================
             *  4️⃣ إنشاء الموظف Employee
             *  ============================ */
            if ($request->has('employee')) {

                $employeeData = $request->employee;
                $employeeData['applicant_id'] = $applicant->id;

                $branches = $employeeData['branch_id'] ?? [];
                unset($employeeData['branch_id']);




                $employeeData['contracts'] = $employeeData['contracts'] ?? [];

                if (isset($employeeData['salary_details']) && is_array($employeeData['salary_details'])) {
                    foreach ($employeeData['salary_details'] as $detail) {
                        echo $detail['department_name'] . ': ' . $detail['amount'];
                    }
                }

                $employeeData['salary_type'] = $employeeData['salary_type'] ?? 'single';

                if (!empty($employeeData['join_date']) && !empty($employeeData['end_date'])) {
                    $diff = Carbon::parse($employeeData['join_date'])
                        ->diff(Carbon::parse($employeeData['end_date']));
                    $employeeData['contract_duration'] = "{$diff->y} years, {$diff->m} months";
                }

                $employeeData['shift_id'] = $employeeData['shift_id'] ?? Shift::first()?->id;
                $employeeData['position_id'] = $employeeData['position_id'] ?? Position::first()?->id;

                if (!($employeeData['is_sales'] ?? false)) {
                    $employeeData['commission_percentage'] = 0;
                }

                /** رفع العقود */
                if (!empty($employeeData['contracts'])) {
                    $uploadedContracts = [];
                    foreach ($employeeData['contracts'] as $contract) {
                        if ($contract instanceof \Illuminate\Http\UploadedFile) {

                            $fileName = time() . '_' . str_replace(' ', '_', $contract->getClientOriginalName());
                            $folder   = "assets/library/contracts/"; // بدون public
                            $path     = public_path($folder);

                            if (!file_exists($path)) {
                                mkdir($path, 0777, true);
                            }

                            $contract->move($path, $fileName);

                            // حفظ المسار نسبيًا بالنسبة للـ public folder
                            $uploadedContracts[] ="public/" . $folder . $fileName;
                        }
                    }

                    // حفظ الـ array في عمود JSON
                    $employeeData['contracts'] = $uploadedContracts;
                }



                $employeeData['status'] = 'accepted';

                $employee = Employee::create($employeeData);

                if (
                    ($employeeData['employment_type'] ?? null) === 'part_time'
                    && ($employeeData['part_time_type'] ?? null) === 'days'
                    && isset($employeeData['days'])
                ) {
                    foreach ($employeeData['days'] as $day) {
                        $employee->workDays()->create([
                            'day'        => $day['day'],
                            'start_time' => $day['start'] ?? null,
                            'end_time'   => $day['end'] ?? null,
                        ]);
                    }
                }

                if (
                    ($employeeData['employment_type'] ?? null) === 'part_time'
                    && ($employeeData['part_time_type'] ?? null) === 'hours'
                ) {
                    $employee->update([
                        'total_hours' => $employeeData['total_hours'] ?? 0
                    ]);
                }

                if ($branches) {
                    if (!is_array($branches)) $branches = [$branches];
                    $employee->branches()->sync($branches);
                }
            }

            DB::commit();

            $applicant->load(['educations', 'experiences', 'skills', 'languages', 'employee.branches', 'employee.workDays']);

            return response()->json([
                'status' => true,
                'message' => 'Employee Added successfully 🎉',
                'data' => new EmployeeResource($applicant),
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
        $query = Employee::with(['applicant', 'department', 'branches', 'company', 'manager', 'position']);

        /*
    |--------------------------------------------------------------------------
    | 1) فلتر الـ keyword (name + phone + code)
    |--------------------------------------------------------------------------
    */
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;

            $query->where(function ($q) use ($keyword) {
                $q->where('code', 'LIKE', "%{$keyword}%")
                    ->orWhereHas('applicant', function ($qq) use ($keyword) {
                        $qq->where('first_name', 'LIKE', "%{$keyword}%")
                            ->orWhere('last_name', 'LIKE', "%{$keyword}%")
                            ->orWhere('phone', 'LIKE', "%{$keyword}%");
                    });
            });
        }

        /*
    |--------------------------------------------------------------------------
    | 2) فلتر department_id
    |--------------------------------------------------------------------------
    */
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        /*
    |--------------------------------------------------------------------------
    | 3) فلتر shift_id
    |--------------------------------------------------------------------------
    */
        if ($request->filled('shift_id')) {
            $query->where('shift_id', $request->shift_id);
        }

        /*
    |--------------------------------------------------------------------------
    | 4) فلتر position_id
    |--------------------------------------------------------------------------
    */
        if ($request->filled('position_id')) {
            $query->where('position_id', $request->position_id);
        }

        /*
    |--------------------------------------------------------------------------
    | Pagination (limit)
    |--------------------------------------------------------------------------
    */
        $limit = $request->integer('limit', 10);
        $employees = $query->paginate($limit);

        return response()->json([
            'status' => true,
            'data' => PrivateEmployeeResource::collection($employees->items()),
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


    public function header()
    {
        // إجمالي عدد الموظفين
        $total_employees = Employee::count();

        // عدد الذكور
        $male_count = Employee::whereHas('applicant', function ($q) {
            $q->where('gender', 'male');
        })->count();

        $female_count = Employee::whereHas('applicant', function ($q) {
            $q->where('gender', 'female');
        })->count();


        // عدد الموظفين الجدد خلال آخر شهر
        $new_employees = Employee::where('join_date', '>=', now()->subMonth())->count();

        return response()->json([
            'total_employees' => $total_employees,
            'male_count' => $male_count,
            'female_count' => $female_count,
            'new_employees' => $new_employees,
        ]);
    }
}
