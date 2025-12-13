<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SalaryMethod;
use App\Http\Requests\StoreSalaryMethodRequest;
use App\Http\Requests\UpdateSalaryMethodRequest;

class SalaryMethodController extends Controller
{
   public function index()
    {
        return response()->json([
            'status' => true,
            'data' => SalaryMethod::all()
        ]);
    }

    // Create
    public function store(StoreSalaryMethodRequest $request)
    {
        $method = SalaryMethod::create($request->validated());

        return response()->json([
            'status' => true,
            'data' => $method
        ], 201);
    }

    // Show one
    public function show($id)
    {
        $method = SalaryMethod::findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $method
        ]);
    }

    // Update
    public function update(UpdateSalaryMethodRequest $request, $id)
    {
        $method = SalaryMethod::findOrFail($id);
        $method->update($request->validated());

        return response()->json([
            'status' => true,
            'data' => $method
        ]);
    }

    // Delete
    public function destroy($id)
    {
        $method = SalaryMethod::findOrFail($id);
        $method->delete();

        return response()->json([
            'status' => true,
            'message' => 'Salary Method deleted successfully'
        ]);
    }
}
