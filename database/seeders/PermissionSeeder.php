<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            [
                'code' => 'dashboard.view',
                'name' => 'View Dashboard',
                'module' => 'dashboard',
                'description' => 'Can view dashboard',
            ],

            [
                'code' => 'users.view',
                'name' => 'View Users',
                'module' => 'users',
                'description' => 'Can view users',
            ],
            [
                'code' => 'users.create',
                'name' => 'Create Users',
                'module' => 'users',
                'description' => 'Can create users',
            ],
            [
                'code' => 'users.update',
                'name' => 'Update Users',
                'module' => 'users',
                'description' => 'Can update users',
            ],
            [
                'code' => 'users.delete',
                'name' => 'Delete Users',
                'module' => 'users',
                'description' => 'Can delete users',
            ],

            [
                'code' => 'roles.view',
                'name' => 'View Roles',
                'module' => 'roles',
                'description' => 'Can view roles',
            ],
            [
                'code' => 'roles.create',
                'name' => 'Create Roles',
                'module' => 'roles',
                'description' => 'Can create roles',
            ],
            [
                'code' => 'roles.update',
                'name' => 'Update Roles',
                'module' => 'roles',
                'description' => 'Can update roles',
            ],
            [
                'code' => 'roles.delete',
                'name' => 'Delete Roles',
                'module' => 'roles',
                'description' => 'Can delete roles',
            ],

            [
                'code' => 'permissions.view',
                'name' => 'View Permissions',
                'module' => 'permissions',
                'description' => 'Can view permissions',
            ],
            [
                'code' => 'permissions.create',
                'name' => 'Create Permissions',
                'module' => 'permissions',
                'description' => 'Can create permissions',
            ],
            [
                'code' => 'permissions.update',
                'name' => 'Update Permissions',
                'module' => 'permissions',
                'description' => 'Can update permissions',
            ],
            [
                'code' => 'permissions.delete',
                'name' => 'Delete Permissions',
                'module' => 'permissions',
                'description' => 'Can delete permissions',
            ],

            [
                'code' => 'sessions.view',
                'name' => 'View Sessions',
                'module' => 'sessions',
                'description' => 'Can view user sessions',
            ],
            [
                'code' => 'sessions.revoke',
                'name' => 'Revoke Sessions',
                'module' => 'sessions',
                'description' => 'Can revoke user sessions',
            ],

            [
                'code' => 'devices.view',
                'name' => 'View Devices',
                'module' => 'devices',
                'description' => 'Can view user devices',
            ],
            [
                'code' => 'devices.block',
                'name' => 'Block Devices',
                'module' => 'devices',
                'description' => 'Can block devices',
            ],

            [
                'code' => 'login_attempts.view',
                'name' => 'View Login Attempts',
                'module' => 'security',
                'description' => 'Can view login attempts',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                [
                    'code' => $permission['code'],
                ],
                $permission
            );
        }
    }
}