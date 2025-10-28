<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Governorate;

class GovernorateController extends Controller
{
    public function index()
    {
        $governorates = Governorate::with('cities')->get();
        return response()->json($governorates);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
        ]);

        $gov = Governorate::create($data);
        return response()->json(['message' => 'Governorate created', 'governorate' => $gov]);
    }

    public function show(Governorate $governorate)
    {
        return response()->json($governorate->load('cities'));
    }

    public function update(Request $request, Governorate $governorate)
    {
        $governorate->update($request->all());
        return response()->json(['message' => 'Governorate updated', 'governorate' => $governorate]);
    }

    public function destroy(Governorate $governorate)
    {
        $governorate->delete();
        return response()->json(['message' => 'Governorate deleted']);
    }
}

