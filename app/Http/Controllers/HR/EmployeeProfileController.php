<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Applicant;
use App\Models\Employee;
use Illuminate\Support\Str;
use App\Traits\ApiResponder;
use App\Models\Company;
use App\Models\Shift;
use App\Models\Attendance;
use App\Models\AttendanceDay;
use App\Http\Resources\Employee\EmployeeProfileResource;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Models\Position;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Models\Governorate;
use App\Models\Country;

class EmployeeProfileController extends Controller
{



    public function PersonalData($id)
    {
        
        $employee = Employee::with(['applicant', 'department', 'position', 'branches', 'company'])
            ->findOrFail($id);

       
        $currentMonth = now()->month;
        $currentYear  = now()->year;

       
        $employeeRecords = Attendance::where('employee_id', $id)
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->get();

      
        $presentDays = $employeeRecords
            ->whereNotIn('day_type', ['absent', 'leave'])
            ->count();

        
        $absentDays = $employeeRecords
            ->where('day_type', 'absent')
            ->count();

      
        $totalLate = $employeeRecords->sum(function ($record) {
            return ($record->late_minutes ?? 0) + ($record->early_leave_minutes ?? 0);
        });

       
        return response()->json(
            new EmployeeProfileResource($employee, [
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                'total_late_minutes' => $totalLate,
            ])
        );
    }

    public function personalActivity($id)
    {
        $employee = Employee::findOrFail($id);

        $tz = 'Africa/Cairo';
        $currentMonth = now($tz)->month;
        $currentYear  = now($tz)->year;

        
        $attendances = AttendanceDay::where('employee_id', $employee->id)
            ->whereYear('work_date', $currentYear)
            ->whereMonth('work_date', $currentMonth)
            ->orderBy('work_date', 'asc')
            ->get();

      
        $attendanceDetails = $attendances->map(function ($item) use ($tz) {

           
            $dailyStatus = [];

            if ($item->late_minutes > 0) {
                $dailyStatus[] = 'lateArrival';
            }

            if ($item->early_leave_minutes > 0) {
                $dailyStatus[] = 'earlyLeave';
            }

            if ($item->overtime_minutes > 0) {
                $dailyStatus[] = 'overtime';
            }

           
            if (empty($dailyStatus)) {
                $dailyStatus[] = 'onTime';
            }

            return [
                'date'       => \Carbon\Carbon::parse($item->work_date)->format('Y-m-d'),
                'check_in'   => $item->first_in_at
                    ? \Carbon\Carbon::parse($item->first_in_at)->setTimezone($tz)->format('H:i')
                    : null,
                'check_out'  => $item->last_out_at
                    ? \Carbon\Carbon::parse($item->last_out_at)->setTimezone($tz)->format('H:i')
                    : null,
                'status'     => $dailyStatus,
            ];
        });

        return response()->json([


            'attendances' => $attendanceDetails,
        ]);
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::with('applicant')->find($id);

        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Employee not found'
            ], 404);
        }

        $applicantId = $employee->applicant->id ?? null;

        // ========= Split full_name ========= //
        if ($request->filled('full_name')) {
            $fullName = trim($request->input('full_name'));
            $parts = preg_split('/\s+/', $fullName);

            $first = $parts[0] ?? null;
            $middle = null;
            $last = null;

            if (count($parts) == 2) {
                $last = $parts[1];
            } elseif (count($parts) >= 3) {
                $middle = $parts[1];
                $last = implode(' ', array_slice($parts, 2));
            }

            $request->merge([
                'first_name' => $first,
                'middle_name' => $middle,
                'last_name'   => $last,
            ]);
        }

        // ========= Validation ========= //
        $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'middle_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'preferred_name' => 'sometimes|string|max:255',
            'marital_status' => 'sometimes|string|max:255',
            'national_id' => ['sometimes', 'string', Rule::unique('applicants')->ignore($applicantId)],
            'email' => ['sometimes', 'email', Rule::unique('applicants')->ignore($applicantId)],
            'phone' => 'sometimes|string|max:20',
            'whatsapp_number' => 'sometimes|string|max:20',
            'birth_date' => 'sometimes|date',
            'governorate_id' => 'sometimes|exists:governorates,id',
            'country_id' => 'sometimes|exists:countries,id',
            'city' => 'sometimes|string',
            'cv' => 'sometimes|file|mimes:pdf,doc,docx|max:10240',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg|max:5120',
            'certification_attatchment' => 'sometimes|file|mimes:pdf,jpg,png|max:10240',

            'employee.code' => 'sometimes|string',
            'employee.position_id' => 'sometimes|exists:positions,id',
            'employee.branch_id' => 'sometimes|exists:branches,id',
            'employee.shift_id' => 'sometimes|exists:shifts,id',
            //'employee.manager_id' => 'sometimes|exists:employees,id',
            'employee.join_date' => 'sometimes|date',
            'employee.end_date' => 'nullable|date',
            'employee.base_salary' => 'sometimes|numeric',
            'employee.compensation_type' => 'sometimes|string',
            'employee.commission_percentage' => 'sometimes|numeric',
            'employee.salary_method' => 'sometimes|string',
            'employee.is_manager' => 'sometimes|boolean',
            'employee.is_sales' => 'sometimes|boolean',
            'employee.salary_type' => 'sometimes|string',
            'employee.contract_type' => 'nullable|string',
            'employee.manager_id' => 'sometimes|nullable|exists:employees,id',

            'employee.weekly_work_days' => 'sometimes|array',
            'employee.weekly_work_days.*.day' => 'required|string',
            'employee.weekly_work_days.*.start_time' => 'required|date_format:H:i',
            'employee.weekly_work_days.*.end_time' => 'required|date_format:H:i',

            'employee.monthly_hours_required' => 'sometimes|numeric',
        ]);

        // ========= Update applicant ========= //
        $employee->applicant->update($request->only([
            'first_name',
            'middle_name',
            'last_name',
            'preferred_name',
            'national_id',
            'email',
            'phone',
            'whatsapp_number',
            'birth_date',
            'governorate_id',
            'country_id',
            'city',
            'marital_status'
        ]));

        // ========= Upload Files ========= //
        foreach (['cv' => 'cvs', 'image' => 'images', 'certification_attatchment' => 'certifications'] as $field => $folder) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $name = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path("assets/library/{$folder}/"), $name);
                $employee->applicant->$field = "assets/library/{$folder}/" . $name;
            }
        }

        $employee->applicant->save();

        // ========= Update employee ========= //
        if ($request->has('employee')) {
            $employeeData = $request->input('employee');

           
            if (!empty($employeeData['weekly_work_days'])) {
                $employeeData['monthly_hours_required'] = null;
            } elseif (!empty($employeeData['monthly_hours_required'])) {
                $employeeData['weekly_work_days'] = null;
            }

            // حساب مدة العقد
            $employeeData['contract_duration'] = $this->calculateContractDuration($employeeData);

            $employee->update($employeeData);

             $employee->refresh();
        }

        // ========= Build Full URLs ========= //
        $employee->applicant->image_url = $employee->applicant->image
            ? asset($employee->applicant->image)
            : null;

        $employee->applicant->cv_url = $employee->applicant->cv
            ? asset($employee->applicant->cv)
            : null;

        $employee->applicant->certification_url = $employee->applicant->certification_attatchment
            ? asset($employee->applicant->certification_attatchment)
            : null;

        $managerId = $employee->manager_id ?? null;

        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully',
            'data' => $employee->load('applicant'),
            'manager' => $managerId,
        ]);
    }

    /**

     * join_date و end_date
     */
    protected function calculateContractDuration(array $employeeData): ?string
    {
        if (!empty($employeeData['join_date']) && !empty($employeeData['end_date'])) {
            $join = Carbon::parse($employeeData['join_date']);
            $end = Carbon::parse($employeeData['end_date']);
            $diff = $join->diff($end);


            return "{$diff->y} years, {$diff->m} months";
        }

        return null;
    }
}
