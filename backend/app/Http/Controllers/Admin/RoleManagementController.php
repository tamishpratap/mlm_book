<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleManagementController extends Controller
{
    /**
     * Display a listing of Admin roles and assigned users.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('q'));

        $query = Role::with(['permissions', 'users']);

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        $roles = $query->get();

        $totalRoles = $roles->count();
        $customRoles = $roles->where('is_system', false)->count();
        $totalUsersAssigned = Admin::whereHas('roles')->count();
        $totalPermissions = Permission::count();

        $adminUsers = Admin::with('roles')->get();

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'roles' => $roles,
                'search' => $search,
                'totalRoles' => $totalRoles,
                'customRoles' => $customRoles,
                'totalUsersAssigned' => $totalUsersAssigned,
                'totalPermissions' => $totalPermissions,
                'adminUsers' => $adminUsers,
            ]);
        }

        return view('admin.roles.index', compact(
            'roles',
            'search',
            'totalRoles',
            'customRoles',
            'totalUsersAssigned',
            'totalPermissions',
            'adminUsers'
        ));
    }

    /**
     * Display interactive Role-Permission Matrix.
     */
    public function matrix()
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all()->groupBy('group');

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'roles' => $roles,
                'permissions' => $permissions,
            ]);
        }

        return view('admin.roles.matrix', compact('roles', 'permissions'));
    }

    /**
     * Update permissions matrix in bulk.
     */
    public function updateMatrix(Request $request)
    {
        $matrix = $request->input('matrix', []); // role_id => [permission_id1, permission_id2]

        $roles = Role::all();

        foreach ($roles as $role) {
            // Super Admin always keeps all permissions
            if ($role->is_system && $role->slug === 'super-admin') {
                $allPerms = Permission::pluck('id')->toArray();
                $role->permissions()->sync($allPerms);
                continue;
            }

            $assignedPerms = isset($matrix[$role->id]) ? array_keys($matrix[$role->id]) : [];
            $role->permissions()->sync($assignedPerms);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Permission matrix updated successfully.',
            ]);
        }

        return redirect()->route('admin.roles.matrix')->with('success', 'Permission matrix updated successfully.');
    }

    /**
     * Show create role form.
     */
    public function create()
    {
        $permissions = Permission::all()->groupBy('group');

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'permissions' => $permissions,
            ]);
        }

        return view('admin.roles.create', compact('permissions'));
    }

    /**
     * Store custom role.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:500',
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $request->input('name'),
            'slug' => Str::slug($request->input('name')),
            'description' => $request->input('description'),
            'is_system' => false,
        ]);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->input('permissions'));
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Role '{$role->name}' created successfully.",
                'role' => $role,
            ]);
        }

        return redirect()->route('admin.roles.index')->with('success', "Role '{$role->name}' created successfully.");
    }

    /**
     * Show edit role form.
     */
    public function edit(Role $role)
    {
        $role->load('permissions');
        $permissions = Permission::all()->groupBy('group');

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'role' => $role,
                'permissions' => $permissions,
            ]);
        }

        return view('admin.roles.edit', compact('role', 'permissions'));
    }

    /**
     * Update role.
     */
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'description' => 'nullable|string|max:500',
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        if (!$role->is_system) {
            $role->update([
                'name' => $request->input('name'),
                'slug' => Str::slug($request->input('name')),
                'description' => $request->input('description'),
            ]);
        } else {
            $role->update([
                'description' => $request->input('description'),
            ]);
        }

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->input('permissions'));
        } else {
            $role->permissions()->detach();
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Role '{$role->name}' updated successfully.",
                'role' => $role,
            ]);
        }

        return redirect()->route('admin.roles.index')->with('success', "Role '{$role->name}' updated successfully.");
    }

    /**
     * Delete role.
     */
    public function destroy(Role $role)
    {
        if ($role->is_system) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'System roles cannot be deleted.'], 422);
            }
            return redirect()->back()->with('error', 'System roles cannot be deleted.');
        }

        $name = $role->name;
        $role->delete();

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Role '{$name}' deleted successfully.",
            ]);
        }

        return redirect()->route('admin.roles.index')->with('success', "Role '{$name}' deleted successfully.");
    }

    /**
     * Assign role to admin user.
     */
    public function assignUserRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'role_id' => 'required|integer|exists:roles,id',
        ]);

        $admin = Admin::find($request->input('user_id')) ?? User::findOrFail($request->input('user_id'));
        $roleId = $request->input('role_id');

        $admin->roles()->sync([$roleId]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Assigned role to admin {$admin->name}.",
            ]);
        }

        return redirect()->back()->with('success', "Assigned role to admin {$admin->name}.");
    }
}
