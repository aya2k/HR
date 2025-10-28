<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Branch;

class BranchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $branches = Branch::with('company')->latest()->get();
        return response()->json($branches);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
         $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'address_ar' => 'nullable|string|max:255',
            'address_en' => 'nullable|string|max:255',
            'city_ar' => 'nullable|string|max:255',
            'city_en' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        $branch = Branch::create($validated);

        return response()->json([
            'message' => 'Branch created successfully ✅',
            'branch' => $branch
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Branch $branch)
    {
       return response()->json($branch->load('company'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Branch $branch)
    {
         $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'name_ar' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'address_ar' => 'nullable|string|max:255',
            'address_en' => 'nullable|string|max:255',
            'city_ar' => 'nullable|string|max:255',
            'city_en' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        $branch->update($validated);

        return response()->json([
            'message' => 'Branch updated successfully ✅',
            'branch' => $branch
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Branch $branch)
    {
        $branch->delete();

        return response()->json(['message' => 'Branch deleted successfully 🗑️']);
    }
}
