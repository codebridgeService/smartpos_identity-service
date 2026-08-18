<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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
                'code' => 'user_roles.assign',
                'name' => 'Assign User Roles',
                'module' => 'roles',
                'description' => 'Can assign roles to users',
            ],
            [
                'code' => 'user_roles.remove',
                'name' => 'Remove User Roles',
                'module' => 'roles',
                'description' => 'Can remove roles from users',
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
                'code' => 'devices.trust',
                'name' => 'Trust Devices',
                'module' => 'devices',
                'description' => 'Can mark user devices as trusted',
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

            // Business Service Permissions
            [
                'code' => 'businesses.view',
                'name' => 'View Businesses',
                'module' => 'businesses',
                'description' => 'Can view business profiles and details',
            ],
            [
                'code' => 'businesses.create',
                'name' => 'Create Businesses',
                'module' => 'businesses',
                'description' => 'Can create new businesses',
            ],
            [
                'code' => 'businesses.update',
                'name' => 'Update Businesses',
                'module' => 'businesses',
                'description' => 'Can update business details and settings',
            ],
            [
                'code' => 'businesses.delete',
                'name' => 'Delete Businesses',
                'module' => 'businesses',
                'description' => 'Can delete businesses',
            ],

            [
                'code' => 'business_users.view',
                'name' => 'View Business Users',
                'module' => 'business_users',
                'description' => 'Can view users assigned to a business',
            ],
            [
                'code' => 'business_users.manage',
                'name' => 'Manage Business Users',
                'module' => 'business_users',
                'description' => 'Can add, update, suspend, or remove users in a business',
            ],

            [
                'code' => 'outlets.view',
                'name' => 'View Outlets',
                'module' => 'outlets',
                'description' => 'Can view outlet locations',
            ],
            [
                'code' => 'outlets.create',
                'name' => 'Create Outlets',
                'module' => 'outlets',
                'description' => 'Can create new outlets for a business',
            ],
            [
                'code' => 'outlets.update',
                'name' => 'Update Outlets',
                'module' => 'outlets',
                'description' => 'Can update outlet settings and details',
            ],
            [
                'code' => 'outlets.delete',
                'name' => 'Delete Outlets',
                'module' => 'outlets',
                'description' => 'Can delete outlets',
            ],

            [
                'code' => 'registers.view',
                'name' => 'View Registers',
                'module' => 'registers',
                'description' => 'Can view cash registers and points of sale',
            ],
            [
                'code' => 'registers.create',
                'name' => 'Create Registers',
                'module' => 'registers',
                'description' => 'Can create new cash registers for an outlet',
            ],
            [
                'code' => 'registers.update',
                'name' => 'Update Registers',
                'module' => 'registers',
                'description' => 'Can update cash register configurations',
            ],
            [
                'code' => 'registers.manage',
                'name' => 'Manage Registers',
                'module' => 'registers',
                'description' => 'Can delete and manage cash registers',
            ],

            [
                'code' => 'pos_devices.view',
                'name' => 'View POS Devices',
                'module' => 'pos_devices',
                'description' => 'Can view POS hardware devices and status',
            ],
            [
                'code' => 'pos_devices.create',
                'name' => 'Create POS Devices',
                'module' => 'pos_devices',
                'description' => 'Can register new POS devices for an outlet',
            ],
            [
                'code' => 'pos_devices.update',
                'name' => 'Update POS Devices',
                'module' => 'pos_devices',
                'description' => 'Can update POS device configurations',
            ],
            [
                'code' => 'pos_devices.manage',
                'name' => 'Manage POS Devices',
                'module' => 'pos_devices',
                'description' => 'Can activate, revoke, lock, and rotate secrets for POS devices',
            ],
        ];

        foreach ($permissions as $permission) {
            $existing = Permission::where('code', $permission['code'])->first();
            Permission::updateOrCreate(
                [
                    'code' => $permission['code'],
                ],
                array_merge($permission, [
                    'uuid' => $existing?->uuid ?? (string) Str::uuid(),
                ])
            );
        }
    }
}