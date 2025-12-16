<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;

class RoleController extends Controller
{
   public function index()
    {
        return Role::with('permissions')->get();
    }

    
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles',
            'permissions' => 'array'
        ]);

        $role = Role::create(['name' => $request->name]);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return response()->json($role->load('permissions'));
    }

    
    public function update(Request $request, Role $role)
    {
        $role->update(['name' => $request->name]);
        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }
        return response()->json($role->load('permissions'));
    }
}
