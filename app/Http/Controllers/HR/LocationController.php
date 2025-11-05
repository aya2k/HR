<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Country;
use App\Models\Governorate;
use App\Models\City;

class LocationController extends Controller
{
    public function countries()
    {
        $countries = Country::all(); 
        return response()->json($countries);
    }

    public function governorates(Country $country)
    {
        $governorates = $country->governorates; 
        return response()->json($governorates);
    }

   
}
