<?php

namespace App\Http\Resources\Attendance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonthlyAttendanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,         ////.///////////////////handle
            'code' => $this->employee->code,
            'position' => $this->employee->position->title_en ?? null,
            'employee' => $this->employee->applicant->first_name . ' ' . $this->employee->applicant->last_name ?? null,




            'date' => $this->date,
            'check_in' => $this->check_in,
            'check_out' => $this->check_out,
            'total_hours' => $this->total_hours,
            'overtime_minutes' => $this->overtime_minutes,
            'late_minutes' => $this->late_minutes,
            'status' => $this->status,

            'shift' => $this->employee->shift->name_en ?? null,
            'join_date' => $this->employee->join_date,

        ];
    }
}
