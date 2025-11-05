<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Http\Requests\Company\StoreCompanyRequest;
use App\Traits\ApiResponder;
use App\Http\Resources\Company\CompanyResource;
use App\Http\Requests\Company\UpdateCompanyRequest;

class CompanyController extends Controller
{
    use ApiResponder;

    public function index()
    {
        $companies = Company::all();
        return CompanyResource::collection($companies);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompanyRequest $request)
    {
        $company = Company::create($request->validated());

        return $this->respondResource(new CompanyResource($company), [
            'message' => 'Created successfully'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company)
    {
        return $this->respondResource(new CompanyResource($company));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCompanyRequest $request, Company $company)
    {
        $validated = $request->validate([]);

        $company->update($validated);


        return $this->respondResource(new CompanyResource($company), [
            'message' => 'Updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        $company->delete();

        return response()->json([
            'status' => true,
            'message' => 'Company deleted successfully 🗑️'
        ], 200);
    }
}
