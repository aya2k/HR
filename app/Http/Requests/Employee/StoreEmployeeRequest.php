<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $employeeData = $this->input('employee', []);
        $hasSalaryDetails = !empty($employeeData['salary_details']);
        return [

            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'preferred_name' => 'nullable|string|max:255',
            'national_id' => 'required|string|max:20|unique:applicants,national_id',
            'email' => 'required|email|unique:applicants,email',
            'phone' => 'required|string|max:11',
            'whatsapp_number' => 'nullable|string|max:11',
            'birth_date' => [
                'required',
                'date',
                'before_or_equal:' . now()->subYears(15)->format('Y-m-d'),
            ],

            'country_id' => 'nullable|integer',
            'governorate_id' => 'nullable|integer',
            'city' => 'nullable|string|max:255',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'gender' => 'nullable|in:male,female',
            'military_service' => 'nullable|in:completed,exempted,postponed,not_required',
            'image' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'position_applied_for_id' => 'required|integer|exists:positions,id',
            'employment_type_id' => 'nullable|integer|exists:shifts,id',
            'employee.part_time_type' => 'required_if:employee.employment_type,part_time|in:hours,days',
            // لو ساعات
            'employee.total_hours' => 'required_if:employee.part_time_type,hours|nullable|integer|min:1',

            // لو أيام
            'employee.days' => 'required_if:employee.part_time_type,days|array',
            'employee.days.*.day' => 'required_if:employee.part_time_type,days|string',
            'employee.days.*.start_time' => 'required_if:employee.part_time_type,days|date_format:H:i',
            'employee.days.*.end_time' => 'required_if:employee.part_time_type,days|date_format:H:i|after:employee.days.*.start_time',
            'work_setup' => 'nullable|in:onsite,remote,hybrid',
            'available_start_date' => 'nullable|date',
            'expected_salary' => 'nullable|numeric|min:0',
            'how_did_you_hear_about_this_role' => 'nullable|string|max:255',
            'cv' => 'nullable|file|mimes:pdf,doc,docx|max:4096',
            'certification_attatchment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
            'cover_letter' => 'nullable|string',
            'facebook_link' => 'nullable|url',
            'linkedin_link' => 'nullable|url',
            //  'github_link' => 'nullable|url',
            'additional_link' => 'nullable|array',
            'additional_link.*' => 'nullable|url',

            'educations' => 'nullable|array',
            'educations.*.degree_level' => 'nullable|string|max:255',
            'educations.*.field_of_study' => 'nullable|string|max:255',
            'educations.*.institution' => 'nullable|string|max:255',
            'educations.*.institution_country' => 'nullable|string|max:255',
            'educations.*.start_date' => 'nullable|date',
            'educations.*.end_date'   => 'nullable|date|after_or_equal:start_date',
            'educations.*.education_attatchment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',


            'skills' => 'nullable|array',
            'skills.*.name' => 'required_with:skills|string|max:255',
            'skills.*.level' => 'nullable|integer|min:1|max:5',
            'languages' => 'nullable|array',
            'languages.*.language' => 'required_with:languages|string|max:100',
            'languages.*.level' => 'nullable|string|max:100',
            'experiences' => 'nullable|array',
            'experiences.*.job_title' => 'required_with:experiences|string|max:255',
            'experiences.*.company_name' => 'nullable|string|max:255',
            'experiences.*.employment_type' => 'nullable|string',
            'experiences.*.work_setup' => 'nullable|string',
            'experiences.*.start_date' => 'nullable|date',
            'experiences.*.end_date'   => 'nullable|date|after_or_equal:start_date',

            'experiences.*.salary' => 'nullable|numeric|min:0',
            'experiences.*.key_responsibilities' => 'nullable|string',

            'experiences.*.manager_name' => 'nullable|string',
            'experiences.*.manager_phone' => 'nullable|string',
            'experiences.*.manager_email' => 'nullable|string',



            'experiences.*.is_current' => 'nullable|boolean',
            'experiences.*.okay_to_contact' => 'nullable|boolean',

            'employee' => 'nullable|array',

            'employee.applicant_id' => 'nullable|integer|exists:applicants,id',
            'employee.code' => 'required|string|max:50|unique:employees,code',
            'employee.department_id' => 'nullable|integer|exists:departments,id',
            'employee.manager_id' => 'nullable|integer|exists:employees,id',
            'employee.branch_id' => 'nullable|array',
            'employee.branch_id.*' => 'integer|exists:branches,id',

            'employee.company_id' => 'nullable|integer|exists:companies,id',
            'employee.shift_id' => 'nullable|integer|exists:shifts,id',
            'employee.position_id' => 'nullable|integer|exists:positions,id',

            'employee.salary_type' => 'required|in:single,multi',
            'employee.contracts' => 'nullable|array',
            'employee.contracts.*' => 'file|mimes:pdf,doc,docx|max:10240',

            'employee.commission_percentage' => 'nullable|numeric|min:0',



            // Employment info
            'employee.hybrid_schedule' => 'nullable|json',
            'educations.*.join_date' => 'nullable|date',
            'educations.*.end_date'   => 'nullable|date|after_or_equal:join_date',
            'employee.salary_details' => 'nullable|array',
            'employee.salary_details.*.department_name' => 'nullable|string|max:255',
            'employee.salary_details.*.amount' => 'nullable|numeric|min:0',




            // Attendance
            'employee.has_fingerprint' => 'nullable|boolean',
            'employee.has_location_tracking' => 'nullable|boolean',
            'employee.weekly_work_days' => 'nullable|json',
            'employee.monthly_hours_required' => 'nullable|integer',

            // Salary & Compensation
            'employee.compensation_type' => 'nullable|string',
            'employee.base_salary' => 'required|numeric|min:0',
            'employee.hourly_rate' => 'nullable|numeric|min:0',
            'employee.commission_percentage' => 'nullable|numeric|min:0|max:100',
            'employee.kpi' => 'nullable|numeric|min:0',
            'employee.salary_method' => 'nullable|string',
            'employee.has_fixed_salary' => 'nullable|boolean',
            'employee.card_number' => 'nullable|string',
            'employee.wallet_number' => 'nullable|string',

            'employee.is_manager' => 'nullable|boolean',
            'employee.is_sales' => 'nullable|boolean',
        ];
    }
}
