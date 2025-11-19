<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Governorate;
use App\Models\Country;

class EmployeeProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    protected $extra;

    public function __construct($resource, $extra = [])
    {
        parent::__construct($resource);
        $this->extra = $extra;
    }
    public function toArray(Request $request): array
    {

        $governorateName = Governorate::where('id', $this->applicant->governorate_id)->value('name_en');
        $countryName = Country::where('id', $this->applicant->country_id)->value('name_en');


        return [
            'id' => $this->id,
            'full_name' => $this->applicant->first_name . ' ' . $this->applicant->middle_name . ' ' . $this->applicant->last_name,
            'employee_id' => $this->code,
            'birth_date' => $this->applicant->birth_date,
            'marital_status' => $this->applicant->marital_status,
            'national_id' => $this->applicant->national_id,
            'phone' => $this->applicant->phone,
            'whatsapp_number' => $this->applicant->whatsapp_number,
            'email' => $this->applicant->email,

            'address' => implode('/', array_filter([$governorateName, $this->applicant->city])),
            'country' => $countryName,

            'contracts' => $this->applicant->employee?->contracts,

            'manager' => $this->applicant->employee?->department?->manager,


            'shift' => $this->applicant->employee->shift->name_en ?? null,
            'position' => $this->applicant->employee?->position?->title_en,
            'shift_hours' => $this->applicant->employee?->shift?->duration,

            'contract_type' => $this->applicant->employee?->contract_type,
            'contract_duration' => $this->applicant->employee?->contract_duration,
            'join_date' => $this->applicant->employee?->join_date,
            'end_date' => $this->applicant->employee?->end_date,

            'salary_type' => $this->applicant->employee?->salary_type,
            'total_salary' => $this->applicant->employee?->salary,
            'kpi' => $this->applicant->employee?->kpi,
            'commission' => $this->applicant->employee?->commission,


            'attendace_card' => [
                'present_days' => $this->extra['present_days'] ?? 0,
                'absent_days' => $this->extra['absent_days'] ?? 0,
                'total_late_minutes' => $this->extra['total_late_minutes'] ?? 0,
            ],

            'meetings_card' => [
                'upcoming' => 3,
                'attended' => 4,
                'missed' => 1,
            ],


            'performance_card' => [
                'kpi' => 85,
                'feedback' => 'Good',
               
            ],

             'holiday_card' => [
                'remaining' => 8,
                'used' => 3,
               
            ],

        ];
    }
}
