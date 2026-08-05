<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;

class AdminRoleController extends Controller
{
    /**
     * Lightweight role list (id + name), used for dropdowns like the user create/edit form.
     */
    public function simpleList()
    {
        return response()->json(Role::orderBy('level', 'desc')->get(['id', 'name', 'slug']));
    }

    public function index()
    {
        $roles = Role::with('permissions')->withCount('users')->get();
        $allPermissions = Permission::all();
        
        $mapped = $roles->map(function ($role) use ($allPermissions) {
            $permissionsByGroup = [];
            $rolePermIds = $role->permissions->pluck('id')->toArray();
            
            foreach ($allPermissions as $perm) {
                $group = ucfirst($perm->group ?? 'General');
                
                if (!isset($permissionsByGroup[$group])) {
                    $permissionsByGroup[$group] = [];
                }
                
                $permissionsByGroup[$group][] = [
                    'id' => $perm->id,
                    'name' => $perm->name,
                    'granted' => in_array($perm->id, $rolePermIds)
                ];
            }
            
            return [
                'id' => $role->id,
                'name' => $role->name,
                'is_system' => $role->level >= 90,
                'users_count' => $role->users_count,
                'permissionsByGroup' => $permissionsByGroup
            ];
        });
        
        return response()->json($mapped);
    }
}
