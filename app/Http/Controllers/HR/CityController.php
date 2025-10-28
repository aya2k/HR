<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\City;

class CityController extends Controller
{
     public function index()
    {
        $cities = City::with('governorate')->get();
        return response()->json($cities);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'governorate_id' => 'required|exists:governorates,id',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
        ]);

        $city = City::create($data);
        return response()->json(['message' => 'City created', 'city' => $city]);
    }

    public function show(City $city)
    {
        return response()->json($city->load('governorate'));
    }

    public function update(Request $request, City $city)
    {
        $city->update($request->all());
        return response()->json(['message' => 'City updated', 'city' => $city]);
    }

    public function destroy(City $city)
    {
        $city->delete();
        return response()->json(['message' => 'City deleted']);
    }
}
