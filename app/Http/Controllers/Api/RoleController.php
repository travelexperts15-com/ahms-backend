<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends BaseController
{
    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/roles
    // ─────────────────────────────────────────────────────────────────────────
    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')
            ->withCount('users')
            ->get()
            ->map(fn($role) => [
                'id'           => $role->id,
                'name'         => $role->name,
                'label'        => $role->label,            // Accessor: "Lab Technician"
                'users_count'  => $role->users_count,
                'permissions'  => $role->permissions->pluck('name'),
            ]);

        return $this->success($roles, 'Roles retrieved successfully.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/permissions
    // ─────────────────────────────────────────────────────────────────────────
    public function permissions(): JsonResponse
    {
        $permissions = Permission::all()
            ->groupBy(fn($p) => explode('.', $p->name)[0]) // Group by module
            ->map(fn($group) => $group->pluck('name'));

        return $this->success($permissions, 'Permissions retrieved successfully.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/roles/{role}/permissions  — sync permissions to a role
    // ─────────────────────────────────────────────────────────────────────────
    public function syncPermissions(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'permissions'   => ['required', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->syncPermissions($request->permissions);

        return $this->success(
            $role->load('permissions')->permissions->pluck('name'),
            "Permissions updated for role [{$role->name}]."
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/users/{user}/roles  — assign/change a user's role
    // ─────────────────────────────────────────────────────────────────────────
    public function assignRole(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        // syncRoles replaces all existing roles (single-role system by default)
        $user->syncRoles([$request->role]);

        return $this->success(
            $user->load('roles')->roles->pluck('name'),
            "Role [{$request->role}] assigned to {$user->name}."
        );
    }
}
