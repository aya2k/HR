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

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $employmentTypeId = $this->input('employee.employment_type_id');
            $shift = \App\Models\Shift::find($employmentTypeId);

            $partTimeType = $this->input('employee.part_time_type');

            if ($shift && strtolower(trim($shift->name_en)) === 'full time') {
                if (!$this->filled('employee.days')) {
                    $validator->errors()->add('employee.days', 'Work days are required for full-time employees.');
                }
            }

            if ($partTimeType === 'days') {
                if (!$this->filled('employee.days')) {
                    $validator->errors()->add('employee.days', 'Work days are required for part-time days employees.');
                }
            }


             $details = $this->input('employee.salary_details', []);

        if (!empty($details)) {
            $total = collect($details)->sum(function ($item) {
                return (float) ($item['amount'] ?? 0);
            });

            if (round($total, 2) !== 100.00) {
                $validator->errors()->add(
                    'employee.salary_details',
                    '⚠️ Total salary distribution must equal 100%'
                );
            }
        }
        });
    }


    public function rules(): array
    {
        $employeeData = $this->input('employee', []);
        $hasSalaryDetails = !empty($employeeData['salary_details']);


        $employmentTypeId = $employeeData['employment_type_id'] ?? null;
        $shift = \App\Models\Shift::find($employmentTypeId);
        $employmentType = $shift?->name_en ?? null;

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
            'employee.employment_type_id' => 'nullable|integer|exists:shifts,id',
            'employee.part_time_type' => 'required_if:employee.employment_type,part_time|in:hours,days',
          
            'employee.total_hours' => 'nullable|integer',

           
            'employee.days' => 'array',
            'employee.days.*.day' => 'string',
            'employee.days.*.start_time' => 'date_format:H:i',
            'employee.days.*.end_time' => 'date_format:H:i|after:employee.days.*.start_time',

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

            'employee.position_id' => 'required|integer|exists:positions,id',

            'employee.salary_type' => 'required|in:single,multi',
            'employee.contracts' => 'nullable|array',
            'employee.contracts.*' => 'file|mimes:pdf,doc,docx|max:10240',
            'employee.contract_type' => 'nullable|string',

            //  'employee.commission_percentage' => 'nullable|numeric|min:0',



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

            'employee.salary_method_id' => 'nullable|integer|exists:salary_methods,id',

            'employee.has_fixed_salary' => 'nullable|boolean',
            'employee.card_number' => 'nullable|string',
            'employee.wallet_number' => 'nullable|string',

            'employee.is_manager' => 'nullable|boolean',
            'employee.is_sales' => 'nullable|boolean',
        ];
    }



    public function messages(): array
    {
        return [
            // Basic info
            'first_name.required' => '⚠️ Please enter the first name.',
            'middle_name.string' => '⚠️ Middle name must be a string.',
            'last_name.required' => '⚠️ Please enter the last name.',
            'preferred_name.string' => '⚠️ Preferred name must be a string.',
            'national_id.required' => '⚠️ Please enter the national ID.',
            'national_id.unique' => '⚠️ This national ID is already used.',
            'email.required' => '⚠️ Please enter the email address.',
            'email.email' => '⚠️ Please enter a valid email address.',
            'email.unique' => '⚠️ This email is already used.',
            'phone.required' => '⚠️ Please enter the phone number.',
            'whatsapp_number.string' => '⚠️ WhatsApp number must be a string.',
            'birth_date.required' => '⚠️ Please enter the birth date.',
            'birth_date.date' => '⚠️ Birth date must be a valid date.',
            'birth_date.before_or_equal' => '⚠️ Employee must be at least 15 years old.',

            // Employee info
            'employee.employment_type_id.exists' => '⚠️ Selected employment type is invalid.',
            'employee.part_time_type.required_if' => '⚠️ Please select part-time type (hours/days).',
            'employee.part_time_type.in' => '⚠️ Part-time type must be either hours or days.',
            'employee.total_hours.integer' => '⚠️ Total hours must be a number.',
            'employee.days.array' => '⚠️ Work days format is invalid.',
            'employee.days.*.day.required' => '⚠️ Please enter the day.',
            'employee.days.*.day.string' => '⚠️ Day value must be a string.',
            'employee.days.*.start_time.required_with' => '⚠️ Start time is required when a day is selected.',
            'employee.days.*.start_time.date_format' => '⚠️ Start time must be in HH:MM format.',
            'employee.days.*.end_time.required_with' => '⚠️ End time is required when a day is selected.',
            'employee.days.*.end_time.date_format' => '⚠️ End time must be in HH:MM format.',
            'employee.days.*.end_time.after' => '⚠️ End time must be after start time.',


            'employee.code.required' => '⚠️ Please enter the employee code.',
            'employee.code.unique' => '⚠️ Employee code already exists.',
            'employee.department_id.exists' => '⚠️ Selected department is invalid.',
            'employee.manager_id.exists' => '⚠️ Selected manager is invalid.',
            'employee.branch_id.array' => '⚠️ Branches must be an array.',
            'employee.branch_id.*.exists' => '⚠️ Selected branch is invalid.',
            'employee.company_id.exists' => '⚠️ Selected company is invalid.',
            'employee.position_id.required' => '⚠️ Please select a position.',
            'employee.position_id.exists' => '⚠️ Selected position is invalid.',
            'employee.salary_type.in' => '⚠️ Salary type must be single or multi.',
            'employee.contracts.*.mimes' => '⚠️ Contracts must be PDF, DOC, or DOCX.',
            'employee.contracts.*.max' => '⚠️ Contract file size must not exceed 10MB.',
            'employee.commission_percentage.numeric' => '⚠️ Commission percentage must be a number.',
            'employee.commission_percentage.min' => '⚠️ Commission percentage must be at least 0.',
            'employee.commission_percentage.max' => '⚠️ Commission percentage must not exceed 100.',

            // Education
            'educations.array' => '⚠️ Educations must be an array.',
            'educations.*.degree_level.string' => '⚠️ Degree level must be a string.',
            'educations.*.field_of_study.string' => '⚠️ Field of study must be a string.',
            'educations.*.institution.string' => '⚠️ Institution must be a string.',
            'educations.*.institution_country.string' => '⚠️ Institution country must be a string.',
            'educations.*.start_date.date' => '⚠️ Start date must be a valid date.',
            'educations.*.end_date.date' => '⚠️ End date must be a valid date.',
            'educations.*.end_date.after_or_equal' => '⚠️ End date must be after or equal to start date.',
            'educations.*.education_attatchment.mimes' => '⚠️ Education attachment must be PDF, JPG, JPEG, or PNG.',
            'educations.*.education_attatchment.max' => '⚠️ Education attachment must not exceed 4MB.',

            // Skills
            'skills.array' => '⚠️ Skills must be an array.',
            'skills.*.name.required_with' => '⚠️ Skill name is required.',
            'skills.*.name.string' => '⚠️ Skill name must be a string.',
            'skills.*.level.integer' => '⚠️ Skill level must be a number between 1 and 5.',
            'skills.*.level.min' => '⚠️ Skill level must be at least 1.',
            'skills.*.level.max' => '⚠️ Skill level must not exceed 5.',

            // Languages
            'languages.array' => '⚠️ Languages must be an array.',
            'languages.*.language.required_with' => '⚠️ Language name is required.',
            'languages.*.language.string' => '⚠️ Language must be a string.',
            'languages.*.level.string' => '⚠️ Language level must be a string.',

            // Experiences
            'experiences.array' => '⚠️ Experiences must be an array.',
            'experiences.*.job_title.required_with' => '⚠️ Job title is required.',
            'experiences.*.company_name.string' => '⚠️ Company name must be a string.',
            'experiences.*.start_date.date' => '⚠️ Start date must be a valid date.',
            'experiences.*.end_date.date' => '⚠️ End date must be a valid date.',
            'experiences.*.end_date.after_or_equal' => '⚠️ End date must be after or equal to start date.',
            'experiences.*.salary.numeric' => '⚠️ Salary must be a number.',

            // Files
            'cv.mimes' => '⚠️ CV must be PDF, DOC, or DOCX.',
            'cv.max' => '⚠️ CV must not exceed 4MB.',
            'certification_attatchment.mimes' => '⚠️ Certification must be PDF, JPG, JPEG, or PNG.',
            'certification_attatchment.max' => '⚠️ Certification must not exceed 4MB.',
            'cover_letter.string' => '⚠️ Cover letter must be a string.',

            // Links
            'facebook_link.url' => '⚠️ Facebook link must be a valid URL.',
            'linkedin_link.url' => '⚠️ LinkedIn link must be a valid URL.',
            'additional_link.array' => '⚠️ Additional links must be an array.',
            'additional_link.*.url' => '⚠️ Each additional link must be a valid URL.',

            // Attendance
            'employee.days.json' => '⚠️ Weekly work days format is invalid.',
            'employee.monthly_hours_required.integer' => '⚠️ Monthly hours must be a number.',

            // Salary
            'employee.base_salary.required' => '⚠️ Base salary is required.',
            'employee.base_salary.numeric' => '⚠️ Base salary must be a number.',
            'employee.hourly_rate.numeric' => '⚠️ Hourly rate must be a number.',
            'employee.salary_method_id.exists' => '⚠️ Selected salary method is invalid.',

            // Booleans
            'employee.has_fingerprint.boolean' => '⚠️ Must select fingerprint .',
            'employee.has_location_tracking.boolean' => '⚠️ Has location tracking must be true or false.',
            'employee.is_manager.boolean' => '⚠️ Is manager must be true or false.',
            'employee.is_sales.boolean' => '⚠️ Is sales must be true or false.',
        ];
    }


   

}
