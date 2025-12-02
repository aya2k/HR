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
        // جلب بيانات الموظف
        $employee = Employee::with(['applicant', 'department', 'position', 'branches', 'company'])
            ->findOrFail($id);

        // الشهر والسنة الحاليين
        $currentMonth = now()->month;
        $currentYear  = now()->year;

        // جلب سجلات الحضور والانصراف للموظف خلال الشهر الحالي فقط
        $employeeRecords = Attendance::where('employee_id', $id)
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->get();

        // حساب الحضور
        $presentDays = $employeeRecords
            ->whereNotIn('day_type', ['absent', 'leave'])
            ->count();

        // حساب الغياب
        $absentDays = $employeeRecords
            ->where('day_type', 'absent')
            ->count();

        // إجمالي التأخير في الشهر الحالي (Late + Early Leave)
        $totalLate = $employeeRecords->sum(function ($record) {
            return ($record->late_minutes ?? 0) + ($record->early_leave_minutes ?? 0);
        });

        // إرجاع البيانات داخل Resource
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

        // جلب الحضور للشهر الحالي للموظف
        $attendances = AttendanceDay::where('employee_id', $employee->id)
            ->whereYear('work_date', $currentYear)
            ->whereMonth('work_date', $currentMonth)
            ->orderBy('work_date', 'asc')
            ->get();

        // تجهيز بيانات كل يوم مع status منفصل
        $attendanceDetails = $attendances->map(function ($item) use ($tz) {

            // حساب حالة اليوم
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

            // لو مفيش أي مخالفة
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

        $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'middle_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'preferred_name' => 'sometimes|string|max:255',
            'national_id' => [
                'sometimes',
                'string',
                Rule::unique('applicants')->ignore($employee->applicant->id ?? 0),
            ],
            'email' => [
                'sometimes',
                'email',
                Rule::unique('applicants')->ignore($employee->applicant->id ?? 0),
            ],
            'phone' => 'sometimes|string|max:20',
            'whatsapp_number' => 'sometimes|string|max:20',
            'birth_date' => 'sometimes|date',
            'work_setup' => 'sometimes|string',
            'available_start_date' => 'sometimes|date',
            'expected_salary' => 'sometimes|numeric',
            'cv' => 'sometimes|file|mimes:pdf,doc,docx|max:10240',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg|max:5120',
            'certification_attatchment' => 'sometimes|file|mimes:pdf,jpg,png|max:10240',

            'employee.code' => [
                'sometimes',
                'string',
                Rule::unique('employees')->ignore($employee->id ?? 0),
            ],
            'employee.position_id' => 'sometimes|exists:positions,id',
            'employee.branch_id' => 'sometimes|exists:branches,id',
            'employee.shift_id' => 'sometimes|exists:shifts,id',
            'employee.status' => 'sometimes|string',
            'employee.join_date' => 'sometimes|date',
            'employee.end_date' => 'nullable|date',
            'employee.base_salary' => 'sometimes|numeric',
            'employee.compensation_type' => 'sometimes|string',
            'employee.salary_method' => 'sometimes|string',
            'employee.is_manager' => 'sometimes|boolean',
            'employee.is_sales' => 'sometimes|boolean',
            'employee.salary_type' => 'sometimes|string',
        ]);

        if ($employee->applicant) {
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
                'work_setup',
                'available_start_date',
                'expected_salary'
            ]));

            if ($request->hasFile('cv')) {
                $employee->applicant->cv = $request->file('cv')->store('uploads/cv', 'public');
            }
            if ($request->hasFile('image')) {
                $employee->applicant->image = $request->file('image')->store('uploads/images', 'public');
            }
            if ($request->hasFile('certification_attatchment')) {
                $employee->applicant->certification_attatchment = $request->file('certification_attatchment')->store('uploads/certifications', 'public');
            }

            $employee->applicant->save();
        }

        if ($request->has('employee')) {
            $employee->update($request->input('employee'));
        }


        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully',
            'data' => $employee->load('applicant'),

        ]);
    }
}
