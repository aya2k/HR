<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class AttendancePolicyRequest extends FormRequest
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

            'name' => 'required|string|max:255',
            'default_required' => 'nullable|integer',
            'default_break' => 'nullable|integer',
            'late_grace' => 'nullable|integer',
            'early_grace' => 'nullable|integer',
            'max_daily_deficit_compensate' => 'nullable|integer',
            'overtime_rules' => 'nullable|array',
            'penalties' => 'nullable|array',
        ];
    }
}
