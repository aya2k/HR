<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use APP\Models\Permission;
use APP\Models\HR;

class PermissionController extends Controller
{
    public function index()
    {
        return Permission::all();
    }

    public function updatePermissions(Request $request, $id)
{
    $request->validate([
        'permissions' => 'array',
        'permissions.*' => 'exists:permissions,name'
    ]);

    $currentHr = auth('hr-api')->user();

    if (!$currentHr->is_super_admin) {
        return response()->json(['error' => 'Only super admin can update permissions'], 403);
    }

    $hr = Hr::findOrFail($id);

    $permissionIds = Permission::whereIn('name', $request->permissions)->pluck('id');

    // Sync permissions
    $hr->permissions()->sync($permissionIds);

    return response()->json([
        'message' => 'Permissions updated successfully',
        'hr' => $hr->load('permissions')
    ]);
}

}
