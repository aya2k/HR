<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'preferred_name' => $this->preferred_name,
            'national_id' => $this->national_id,
            'email' => $this->email,
            'phone' => $this->phone,
            'whatsapp_number' => $this->whatsapp_number,
            'birth_date' => $this->birth_date,
            'work_setup' => $this->work_setup,
            'available_start_date' => $this->available_start_date,
            'expected_salary' => $this->expected_salary,
            'cv' => $this->cv ? asset( $this->cv) : null,
            'image' => $this->image ? asset( $this->image) : null,
            'certification_attatchment' => $this->certification_attatchment ? asset( $this->certification_attatchment) : null,
            'educations' => $this->educations,
            'experiences' => $this->experiences,
            'skills' => $this->skills,
            
            'languages' => $this->languages,
            'created_at' => $this->created_at->toDateTimeString(),

            'employee' => $this->whenLoaded('employee', function () {
                return [
                    'id' => $this->employee->id,
                    'code' => $this->employee->code,
                    'position_id' => $this->employee->position->title_en?? null,
                   
                    'branch_id' => $this->employee->branch->name_en?? null,           //?????????
                    'shift_id' => $this->employee->shift->name_en?? null,
                    'status' => $this->employee->status,
                    'join_date' => $this->employee->join_date,
                    'end_date' => $this->employee->end_date,
                    'base_salary' => $this->employee->base_salary,
                    'compensation_type' => $this->employee->compensation_type,
                    'salary_method' => $this->employee->salary_method,
                    'is_manager' => $this->employee->is_manager,
                    'is_sales' => $this->employee->is_sales,
                    'salary_type' => $this->employee->salary_type ?? 'single',
                    'contracts' => $this->employee->contracts, // array of contracts
                  // 'salary_details' => $this->employee->salary_details ? 'required' : 'nullable',
                ];
            }),
        ];
    }
}
