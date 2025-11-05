<?php

namespace App\Http\Requests\Branch;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchRequest extends FormRequest
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
            'company_id' => 'nullable|exists:companies,id',
            'name_ar' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'address_ar' => 'nullable|string|max:255',
            'address_en' => 'nullable|string|max:255',
            'city_ar' => 'nullable|string|max:255',
            'city_en' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
        ];
    }

    public function messages()
    {
        return [
            'company_id.required' => 'Company is required.',
            'company_id.exists' => 'Selected company does not exist.',
            'name_en.required' => 'Branch name (EN) is required.',
        ];
    }
}
