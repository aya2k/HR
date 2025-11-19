<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class PayrollPolicyRequest extends FormRequest
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
            'compensation_type' => 'required|in:fixed,hourly,commission,mixed',
            'overtime_rate_multiplier' => 'required|numeric|min:1|max:5',
            'holiday_work_multiplier' => 'required|numeric|min:1|max:5',
            'deduction_mode' => 'required|in:per_minute,tiers',
        ];
    }
}
