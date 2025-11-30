<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrivateEmployeeResource extends JsonResource
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
            'code' => $this->code,
            'image' => $this->applicant->image,
            'full_name' => $this->applicant->first_name . ' '  . $this->applicant->last_name,

            'phone' => $this->applicant->phone,
            'position' => $this->position->title_en ?? null,
            'department' => $this->department->name_en,
            // 'branch' => $this->applicant->employee->branch->name_en?? null,
            'shift' => $this->applicant->employee->shift->name_en ?? null,
            'join_date' => $this->applicant->employee?->join_date,












        ];
    }
}
