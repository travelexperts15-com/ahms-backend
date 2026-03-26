<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ── 1. Create all permissions ─────────────────────────────────────────
        $this->command->info('  Creating permissions...');

        foreach (Permission::systemPermissions() as $permissionName) {
            Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web']
            );
        }

        // ── 2. Create roles and assign permissions ────────────────────────────
        $this->command->info('  Creating roles...');

        $rolePermissionMap = Permission::rolePermissionMap();

        foreach (Role::systemRoles() as $roleName => $description) {
            /** @var Role $role */
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web']
            );

            $permissionsForRole = $rolePermissionMap[$roleName] ?? [];

            if ($permissionsForRole === ['*']) {
                // Super admin gets every permission
                $role->syncPermissions(Permission::all());
            } elseif (!empty($permissionsForRole)) {
                $role->syncPermissions($permissionsForRole);
            }

            $this->command->line("    ✓ Role [{$roleName}] → " . count($permissionsForRole === ['*'] ? Permission::all() : $permissionsForRole) . " permissions");
        }
    }
}
