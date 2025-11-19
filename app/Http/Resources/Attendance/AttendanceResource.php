<?php

namespace App\Http\Resources\Attendance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
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
            'code'=>$this->employee->code,
            'employee' => $this->employee->applicant->first_name . ' ' . $this->employee->applicant->last_name?? null,
            'date' => $this->date,
            'check_in' => $this->check_in,
            'check_out' => $this->check_out,
            'total_hours' => $this->total_hours,
            'overtime_minutes' => $this->overtime_minutes,
            'late_minutes' => $this->late_minutes,
            'status' => $this->status,
            'position'=>$this->employee->position->title_en ?? null,     
            'shift'=>$this->employee->shift->name_en ?? null,
            'join_date'=>$this->employee-> join_date,
           
        ];
    }
}
