<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shift;
use App\Traits\ApiResponder;

class ShiftController extends Controller
{
      public function index()
    {
        $shifts = Shift::latest()->get();
        return response()->json($shifts);
    }

    // إنشاء شيفت جديد
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'break_minutes' => 'nullable|integer|min:0',
        ]);

        $shift = Shift::create($validated);

        return response()->json([
            'message' => 'Shift created successfully ✅',
            'shift' => $shift
        ], 201);
    }

    // عرض شيفت محدد
    public function show(Shift $shift)
    {
        return response()->json($shift);
    }

    // تحديث شيفت
    public function update(Request $request, Shift $shift)
    {
        $validated = $request->validate([
            'name_ar' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'break_minutes' => 'nullable|integer|min:0',
        ]);

        $shift->update($validated);

        return response()->json([
            'message' => 'Shift updated successfully ✅',
            'shift' => $shift
        ]);
    }

    // حذف شيفت
    public function destroy(Shift $shift)
    {
        $shift->delete();

        return response()->json(['message' => 'Shift deleted successfully 🗑️']);
    }
}
