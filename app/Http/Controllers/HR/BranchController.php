<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Branch;
use App\Traits\ApiResponder;
use App\Http\Resources\Branch\BranchResource;
use App\Http\Requests\Branch\UpdateBranchRequest;
use App\Http\Requests\Branch\StoreBranchRequest;

class BranchController extends Controller
{
    use ApiResponder;
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
    public function store(StoreBranchRequest $request)
    {


        $branch = Branch::create([
            'company_id' => 1,
            'name_en' => $request->name_en,

        ]);
        return response()->json([
            'message' => 'Branch created successfully ✅',
            'branch' => $branch
        ], 201);

        return $this->respondResource(new BranchResource($branch), [
            'message' => 'Created successfully'
        ]);
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
    public function update(UpdateBranchRequest $request, Branch $branch)
    {


        $branch->update($request->validated());

        return $this->respondResource(new BranchResource($branch), [
            'message' => 'Updated successfully'
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
