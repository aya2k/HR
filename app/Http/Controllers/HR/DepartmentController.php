<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Department;

class DepartmentController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => true,
            'data' => Department::with('company', 'manager')->get()
        ]);
    }

    // إنشاء قسم جديد
    public function store(Request $request)
    {
        $validated = $request->validate([

            'name_en' => 'required|string|max:255',
            'description_en' => 'nullable|string|max:255',
            'company_id' => 'nullable|integer|exists:companies,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'phones' => 'nullable|array',
            'phones.*' => 'string|max:20',

        ]);

        $colors = [
        '#4CAF50', '#2196F3', '#FF5722', '#9C27B0',
        '#E91E63', '#3F51B5', '#009688', '#FFC107',
        '#FF9800', '#795548', '#607D8B'
    ];

    $validated['color'] = $colors[array_rand($colors)];

        $department = Department::create($validated);



        return response()->json([
            'status' => true,
            'data' => $department
        ], 201);
    }

    // عرض قسم معين
    public function show($id)
    {
        $department = Department::with('company', 'manager', 'employees')->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $department
        ]);
    }

    // تحديث القسم
    public function update(Request $request, $id)
    {
        $department = Department::findOrFail($id);

        $validated = $request->validate([
            'name_en' => 'nullable|string|max:255',
            'description_en' => 'nullable|string|max:255',
            'company_id' => 'nullable|integer|exists:companies,id',
            'phones' => 'nullable|array',
            'phones.*' => 'string|max:20',

        ]);

        $department->update($validated);

        return response()->json([
            'status' => true,
            'data' => $department
        ]);
    }

    // حذف القسم
    public function destroy($id)
    {
        $department = Department::findOrFail($id);
        $department->delete();

        return response()->json([
            'status' => true,
            'message' => 'Department deleted successfully'
        ]);
    }
}
