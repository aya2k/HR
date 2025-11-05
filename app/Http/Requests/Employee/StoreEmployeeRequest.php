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
        return [

            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'preferred_name' => 'nullable|string|max:255',
            'national_id' => 'required|string|max:20|unique:applicants,national_id',
            'email' => 'required|email|unique:applicants,email',
            'phone' => 'required|string|max:20',
            'whatsapp_number' => 'nullable|string|max:20',
            'birth_date' => 'required|date',
            'country_id' => 'nullable|integer',
            'governorate_id' => 'nullable|integer',
            'city' => 'nullable|string',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'gender' => 'nullable|in:male,female',
            'military_service' => 'nullable|in:completed,exempted,postponed,not_required',
            'image' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'position_applied_for' => 'required|string|max:255',
            'employment_type' => 'nullable|string',
            'work_setup' => 'nullable|in:onsite,remote,hybrid',
            'available_start_date' => 'nullable|date',
            'expected_salary' => 'nullable|numeric|min:0',
            'how_did_you_hear_about_this_role' => 'nullable|string|max:255',
            'cv' => 'nullable|file|mimes:pdf,doc,docx|max:4096',
            'certification_attatchment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
            'cover_letter' => 'nullable|string',
            'facebook_link' => 'nullable|url',
            'linkedin_link' => 'nullable|url',
            'github_link' => 'nullable|url',
            'additional_link' => 'nullable|url',
            'educations' => 'nullable|array',
            'educations.*.degree_level' => 'nullable|string|max:255',
            'educations.*.field_of_study' => 'nullable|string|max:255',
            'educations.*.institution' => 'nullable|string|max:255',
            'educations.*.institution_country' => 'nullable|string|max:255',
            'educations.*.start_date' => 'nullable|date',
            'educations.*.end_date' => 'nullable|date',
            'skills' => 'nullable|array',
            'skills.*.name' => 'required_with:skills|string|max:255',
            'skills.*.skill_level' => 'nullable|integer|min:1|max:5',
            'languages' => 'nullable|array',
            'languages.*.language' => 'required_with:languages|string|max:100',
            'languages.*.level' => 'nullable|string|max:100',
            'experiences' => 'nullable|array',
            'experiences.*.job_title' => 'required_with:experiences|string|max:255',
            'experiences.*.company_name' => 'nullable|string|max:255',
            'experiences.*.employment_type' => 'nullable|in:full_time,part_time,contract,internship,freelance',
            'experiences.*.work_setup' => 'nullable|in:onsite,remote,hybrid',
            'experiences.*.start_date' => 'nullable|date',
            'experiences.*.end_date' => 'nullable|date',
            'experiences.*.salary' => 'nullable|numeric|min:0',
            'experiences.*.key_responsibilities' => 'nullable|string',


            'employee' => 'nullable|array',

            'employee.applicant_id' => 'nullable|integer|exists:applicants,id',
            'employee.code' => 'nullable|string|max:50|unique:employees,code',
            'employee.department_id' => 'nullable|integer|exists:departments,id',
            'employee.manager_id' => 'nullable|integer|exists:employees,id',
            'employee.branch_id' => 'nullable|integer|exists:branches,id',
            'employee.company_id' => 'nullable|integer|exists:companies,id',

            // Employment info
            'employee.hybrid_schedule' => 'nullable|json',
            'employee.join_date' => 'nullable|date',
            'employee.end_date' => 'nullable|date',
            'employee.contract_duration' => 'nullable|string|max:100',
            'employee.status' => 'nullable|in:active,inactive,terminated,resigned',

            // Attendance
            'employee.has_fingerprint' => 'nullable|boolean',
            'employee.has_location_tracking' => 'nullable|boolean',
            'employee.weekly_work_days' => 'nullable|json',
            'employee.monthly_hours_required' => 'nullable|integer',

            // Salary & Compensation
            'employee.compensation_type' => 'nullable|in:fixed,hourly,commission,mixed',
            'employee.base_salary' => 'nullable|numeric|min:0',
            'employee.hourly_rate' => 'nullable|numeric|min:0',
            'employee.commission_percentage' => 'nullable|numeric|min:0|max:100',
            'employee.kpi' => 'nullable|numeric|min:0|max:100',
            'employee.salary_method' => 'nullable|in:cash,bank,wallet',
            'employee.has_fixed_salary' => 'nullable|boolean',
            'employee.num_of_call_system' => 'nullable|integer|min:0',
            'employee.is_manager' => 'nullable|boolean',
            'employee.is_sales' => 'nullable|boolean',
        ];
    }
}
