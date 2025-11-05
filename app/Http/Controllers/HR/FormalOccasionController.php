<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FormalOccasion;
use App\Traits\ApiResponder;

class FormalOccasionController extends Controller
{
     public function index()
    {
        return response()->json(FormalOccasion::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title_ar' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        // نحسب المدة تلقائيًا
        $validated['duration'] = now()->parse($validated['date_from'])->diffInDays(now()->parse($validated['date_to'])) + 1;

        $occasion = FormalOccasion::create($validated);

        return response()->json([
            'message' => 'تم إنشاء المناسبة بنجاح ✅',
            'data' => $occasion
        ]);
    }

    public function show(FormalOccasion $formalOccasion)
    {
        return response()->json($formalOccasion);
    }

    public function update(Request $request, FormalOccasion $formalOccasion)
    {
        $validated = $request->validate([
            'title_ar' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $validated['duration'] = now()->parse($validated['date_from'])->diffInDays(now()->parse($validated['date_to'])) + 1;

        $formalOccasion->update($validated);

        return response()->json([
            'message' => 'تم تحديث المناسبة بنجاح ✅',
            'data' => $formalOccasion
        ]);
    }

    public function destroy(FormalOccasion $formalOccasion)
    {
        $formalOccasion->delete();
        return response()->json(['message' => 'تم حذف المناسبة بنجاح 🗑️']);
    }
}
