<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Position;

class PositionController extends Controller
{
    public function index()
    {
        $positions = Position::with('department')->latest()->get();
        return response()->json($positions);
    }

    // إنشاء وظيفة جديدة
    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'title_ar' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
        ]);

        $position = Position::create($validated);

        return response()->json([
            'message' => 'Position created successfully ✅',
            'position' => $position->load('department')
        ], 201);
    }

    // عرض وظيفة محددة
    public function show(Position $position)
    {
        return response()->json($position->load('department'));
    }

    // تحديث وظيفة
    public function update(Request $request, Position $position)
    {
        $validated = $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'title_ar' => 'nullable|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
        ]);

        $position->update($validated);

        return response()->json([
            'message' => 'Position updated successfully ✅',
            'position' => $position->load('department')
        ]);
    }

    // حذف وظيفة
    public function destroy(Position $position)
    {
        $position->delete();

        return response()->json(['message' => 'Position deleted successfully 🗑️']);
    }
}
