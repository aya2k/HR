<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Attendance\AttendancePolicyRequest;
use App\Http\Resources\Attendance\AttendancePolicyResource;
use App\Models\AttendancePolicy;

class AttendancePolicyController extends Controller
{
    public function index()
    {
        return AttendancePolicyResource::collection(AttendancePolicy::paginate());
    }

    public function store(AttendancePolicyRequest $request)
    {

        $data = $request->validated();
        $hourFields = [
            'default_required',
            'default_break',
            'late_grace',
            'early_grace',
            'max_daily_deficit_compensate',
        ];

        foreach ($hourFields as $field) {
            if (!empty($data[$field])) {
                $data[$field] = $data[$field] * 60; 
            }
        }



        $policy = AttendancePolicy::create($data);

        return new AttendancePolicyResource($policy);
    }

    public function show(AttendancePolicy $attendancePolicy)
    {
        return new AttendancePolicyResource($attendancePolicy);
    }

    public function update(AttendancePolicyRequest $request, AttendancePolicy $attendancePolicy)
    {
        $attendancePolicy->update($request->validated());
        return new AttendancePolicyResource($attendancePolicy);
    }

    public function destroy(AttendancePolicy $attendancePolicy)
    {
        if ($attendancePolicy->is_default) {
            return response()->json(['message' => 'Cannot delete default policy'], 400);
        }

        $attendancePolicy->delete();
        return response()->json(['message' => 'Policy deleted successfully']);
    }
}
