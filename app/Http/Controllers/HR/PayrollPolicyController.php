<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Payroll\PayrollPolicyRequest;
use App\Http\Resources\Payroll\PayrollPolicyResource;
use App\Models\PayrollPolicy;

class PayrollPolicyController extends Controller
{
    public function index()
    {
        return PayrollPolicyResource::collection(PayrollPolicy::paginate());
    }

    public function store(PayrollPolicyRequest $request)
    {
        $data = $request->validated();

        if (!PayrollPolicy::where('is_default', true)->exists()) {
            $data['is_default'] = true;
        }

        $policy = PayrollPolicy::create($data);

        return new PayrollPolicyResource($policy);
    }

    public function show(PayrollPolicy $payrollPolicy)
    {
        return new PayrollPolicyResource($payrollPolicy);
    }

    public function update(PayrollPolicyRequest $request, PayrollPolicy $payrollPolicy)
    {
        $payrollPolicy->update($request->validated());
        return new PayrollPolicyResource($payrollPolicy);
    }

   

    public function destroy(PayrollPolicy $payrollPolicy)
{
    if ($payrollPolicy->is_default) {
        return response()->json(['message' => 'Cannot delete default policy'], 400);
    }

    $payrollPolicy->delete();
    return response()->json(['message' => 'Policy deleted successfully']);
}

}
