<?php

namespace App\Http\Resources\Payroll;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollPolicyResource extends JsonResource
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
            'name' => $this->name,
            'compensation_type' => $this->compensation_type,
            'overtime_rate_multiplier' => $this->overtime_rate_multiplier,
            'holiday_work_multiplier' => $this->holiday_work_multiplier,
            'deduction_mode' => $this->deduction_mode,
        ];
    }
}
