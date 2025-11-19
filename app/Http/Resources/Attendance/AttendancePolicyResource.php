<?php

namespace App\Http\Resources\Attendance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendancePolicyResource extends JsonResource
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
        
        // ✅ نعرضها بالساعات بدل الدقايق
        'default_required' => round($this->default_required / 60, 2),
        'default_break' => round($this->default_break / 60, 2),

        'late_grace' => round($this->late_grace / 60, 2),
        'early_grace' => round($this->early_grace / 60, 2),

        'max_daily_deficit_compensate' => $this->max_daily_deficit_compensate
            ? round($this->max_daily_deficit_compensate / 60, 2)
            : null,

        'overtime_rules' => $this->overtime_rules,
        'penalties' => $this->penalties,
        
    ];
    }
}
