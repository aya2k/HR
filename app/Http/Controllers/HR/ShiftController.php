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
        // 1️⃣ جلب كل الأسماء الموجودة مسبقًا في جدول shifts
        $existingNames = Shift::pluck('name_en')->toArray();

        // 2️⃣ Escape عشان سلامة الـ regex
        $escapedNames = array_map(function ($name) {
            return preg_quote($name, '/');
        }, $existingNames);

        // 3️⃣ بناء regex يمنع التطابق الكامل مع أي اسم موجود (case-insensitive)
        $regex = '/^(?!(' . implode('|', $escapedNames) . ')$).+$/i';

        // 4️⃣ Validation يدوي مع regex الديناميكي
        $validated = $request->validate([
            'name_en' => ['nullable', 'string', 'max:255', "regex:$regex"],
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'break_minutes' => 'nullable|integer|min:0',
            'duration' => 'nullable|integer|min:0',
        ], [
            'name_en.regex' => 'This shift name already exists.', // رسالة خطأ واضحة
        ]);

        // 5️⃣ إنشاء الـ Shift
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
            'duration' => 'nullable|integer|min:0',
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
