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

   
    public function store(Request $request)
    {
        
        $existingNames = Shift::pluck('name_en')->toArray();

       
        $escapedNames = array_map(function ($name) {
            return preg_quote($name, '/');
        }, $existingNames);

       
        $regex = '/^(?!(' . implode('|', $escapedNames) . ')$).+$/i';

       
        $validated = $request->validate([
            'name_en' => ['nullable', 'string', 'max:255', "regex:$regex"],
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'break_minutes' => 'nullable|integer|min:0',
            'duration' => 'nullable|integer|min:0',
        ], [
            'name_en.regex' => 'This shift name already exists.', 
        ]);

      
        $shift = Shift::create($validated);

        return response()->json([
            'message' => 'Shift created successfully ✅',
            'shift' => $shift
        ], 201);
    }


   
    public function show(Shift $shift)
    {
        return response()->json($shift);
    }

  
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

    
    public function destroy(Shift $shift)
    {
        $shift->delete();

        return response()->json(['message' => 'Shift deleted successfully 🗑️']);
    }
}
