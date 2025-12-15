<?php

namespace App\Http\Requests\Position;

use Illuminate\Foundation\Http\FormRequest;

class PositionRequest extends FormRequest
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
        // 1. هات كل أسماء الوظائف من قاعدة البيانات
        $existingNames = \App\Models\Position::pluck('title_en')->toArray();

        // 2. Escape الأسماء عشان regex
        $escapedNames = array_map(function ($name) {
            return preg_quote($name, '/');
        }, $existingNames);

        // 3. Regex يمنع التطابق الكامل (case insensitive)
        $regex = '/^(?!(' . implode('|', $escapedNames) . ')$).+$/i';

        return [
            'branch_id'     => 'nullable|exists:branches,id',
            'title_en'      => ['required', 'string', 'max:255', "regex:$regex"],
            'description_en' => 'nullable|string',
            'department_id'     => 'nullable|exists:departments,id',


        ];
    }



     public function messages(): array
    {
        return [
          
            'title_en.required' => '⚠️This position is not avaliable for that branch.',
                
        ];
    }


   

}

