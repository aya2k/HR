<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Middleware\CheckPermission;
use App\Models\Hr;

class HRController extends Controller
{
    // public function assignRole(Request $request, Hr $hr)
    // {
    //     $request->validate([
    //         'role_id' => 'required|exists:roles,id',
    //     ]);

    //     $currentHr = auth('hr-api')->user();

    //     // Only Super Admin can assign roles
    //     if (!$currentHr->isSuperAdmin()) {
    //         return response()->json(['error' => 'Only super admin can assign roles'], 403);
    //     }

    //     if ($hr->isSuperAdmin()) {
    //         return response()->json(['error' => 'You cannot modify Super Admin role'], 403);
    //     }


    //     // Assign role
    //     $hr->roles()->sync([$request->role_id]);

    //     return response()->json([
    //         'message' => 'Role assigned successfully',
    //         'hr' => $hr->load('roles.permissions')
    //     ]);
    // }


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
       $hrs = Hr::with('permissions')->get(); // جلب كل HRs مع الصلاحيات المرتبطة بهم

    $data = $hrs->map(function($hr) {
        return [
            'id' => $hr->id,
            'name' => $hr->name,
            'email' => $hr->email,
            'is_super_admin' => $hr->is_super_admin,
            'permissions' => $hr->permissions->pluck('name'), // قائمة أسماء الصلاحيات
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

    // التحقق من أن السوبر أدمن فقط يمكنه تحديث الصلاحيات
    if (!$currentHr->is_super_admin) {
        return response()->json(['error' => 'Only super admin can assign permissions'], 403);
    }

    // تحديث الصلاحيات (sync)
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
