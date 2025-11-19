<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequest extends FormRequest
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

            'name_en' => 'sometimes|string|max:255',
            'address_en' => 'nullable|string|max:255',
            'phones' => 'nullable|array',
            'phones.*' => 'string|max:20',

            'email' => 'nullable|email|max:255',
        ];
    }
}
