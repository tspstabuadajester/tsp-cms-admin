<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed the application's roles and permissions.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'businesses.manage',
            'websites.manage',
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);

        $superAdmin->syncPermissions($permissions);

        $businessAdmin = Role::firstOrCreate([
            'name' => 'business-admin',
            'guard_name' => 'web',
        ]);

        $businessAdmin->syncPermissions([
            'websites.manage',
            'settings.manage',
        ]);

        $contentManager = Role::firstOrCreate([
            'name' => 'content-manager',
            'guard_name' => 'web',
        ]);

        $contentManager->syncPermissions([
            'websites.manage'
        ]);

    }
}
