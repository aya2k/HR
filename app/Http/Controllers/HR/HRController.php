<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Middleware\CheckPermission;
use App\Models\Hr;

class HRController extends Controller
{
    


    public function index()
    {
        return Hr::select('id', 'name', 'email')->get();
    }

    public function getPermissions($id)
    {
        $hr = Hr::with('permissions')->findOrFail($id);

        return [
            'hr_id' => $hr->id,
            'permissions' => $hr->permissions->pluck('name'),
        ];
    }

   
    //GET /api/hrs-with-permissions?permission=add_employee


    public function listWithPermissions(Request $request)
    {
       $hrs = Hr::with('permissions')->get(); 

    $data = $hrs->map(function($hr) {
        return [
            'id' => $hr->id,
            'name' => $hr->name,
            'email' => $hr->email,
            'is_super_admin' => $hr->is_super_admin,
            'permissions' => $hr->permissions->pluck('name'), 
        ];
    });

    return response()->json($data);
    }


    // HrController.php

public function updatePermissions(Request $request, Hr $hr)
{
    $request->validate([
        'permissions' => 'required|array',
        'permissions.*' => 'exists:permissions,name',
    ]);

    $currentHr = auth('hr-api')->user();

   
    if (!$currentHr->is_super_admin) {
        return response()->json(['error' => 'Only super admin can assign permissions'], 403);
    }

    
    $permissionIds = \App\Models\Permission::whereIn('name', $request->permissions)->pluck('id');
    $hr->permissions()->sync($permissionIds);

    return response()->json([
        'message' => 'Permissions updated successfully',
        'hr' => [
            'id' => $hr->id,
            'name' => $hr->name,
            'permissions' => $hr->permissions->pluck('name')
        ]
    ]);
}

    
}
