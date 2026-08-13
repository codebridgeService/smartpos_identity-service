<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Services\RbacCacheService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Define baseline permission matrix
        $permissions = [
            // User Management
            ['code' => 'users.view', 'name' => 'View Users', 'module' => 'users', 'description' => 'Can view users list and details'],
            ['code' => 'users.create', 'name' => 'Create Users', 'module' => 'users', 'description' => 'Can create new users'],
            ['code' => 'users.update', 'name' => 'Update Users', 'module' => 'users', 'description' => 'Can update user profiles'],
            ['code' => 'users.delete', 'name' => 'Delete Users', 'module' => 'users', 'description' => 'Can delete users'],
            ['code' => 'users.manage', 'name' => 'Full User Management', 'module' => 'users', 'description' => 'Can perform all user administrative actions'],

            // Role Management
            ['code' => 'roles.view', 'name' => 'View Roles', 'module' => 'roles', 'description' => 'Can view roles and permissions'],
            ['code' => 'roles.create', 'name' => 'Create Roles', 'module' => 'roles', 'description' => 'Can create new roles'],
            ['code' => 'roles.update', 'name' => 'Update Roles', 'module' => 'roles', 'description' => 'Can update roles'],
            ['code' => 'roles.delete', 'name' => 'Delete Roles', 'module' => 'roles', 'description' => 'Can delete roles'],
            ['code' => 'roles.manage', 'name' => 'Full Role Management', 'module' => 'roles', 'description' => 'Can perform all role administrative actions'],

            // Permission Management
            ['code' => 'permissions.view', 'name' => 'View Permissions', 'module' => 'permissions', 'description' => 'Can view permission lists'],
            ['code' => 'permissions.create', 'name' => 'Create Permissions', 'module' => 'permissions', 'description' => 'Can create permissions'],
            ['code' => 'permissions.update', 'name' => 'Update Permissions', 'module' => 'permissions', 'description' => 'Can update permissions'],
            ['code' => 'permissions.delete', 'name' => 'Delete Permissions', 'module' => 'permissions', 'description' => 'Can delete permissions'],

            // POS Terminal Quick PIN Management
            ['code' => 'pos_pin.view', 'name' => 'View POS PIN Status', 'module' => 'pos_pin', 'description' => 'Can view POS PIN status'],
            ['code' => 'pos_pin.update', 'name' => 'Update POS PIN', 'module' => 'pos_pin', 'description' => 'Can update cashier POS PIN'],
            ['code' => 'pos_pin.verify', 'name' => 'Verify POS PIN', 'module' => 'pos_pin', 'description' => 'Can quick-verify POS PIN at terminal'],
            ['code' => 'pos_pin.manage', 'name' => 'Full POS PIN Management', 'module' => 'pos_pin', 'description' => 'Can manage cashier PINs'],

            // Device & Session Management
            ['code' => 'devices.view', 'name' => 'View Devices', 'module' => 'devices', 'description' => 'Can view trusted devices'],
            ['code' => 'devices.block', 'name' => 'Block Devices', 'module' => 'devices', 'description' => 'Can block/unblock devices'],
            ['code' => 'devices.manage', 'name' => 'Full Device Management', 'module' => 'devices', 'description' => 'Can manage device trust status'],
            ['code' => 'sessions.view', 'name' => 'View Active Sessions', 'module' => 'sessions', 'description' => 'Can view active sessions'],
            ['code' => 'sessions.revoke', 'name' => 'Revoke Sessions', 'module' => 'sessions', 'description' => 'Can terminate user sessions'],

            // Security Audit & Dashboard
            ['code' => 'dashboard.view', 'name' => 'View Dashboard', 'module' => 'dashboard', 'description' => 'Can view identity service metrics'],
            ['code' => 'login_attempts.view', 'name' => 'View Login Attempts', 'module' => 'security', 'description' => 'Can view login security logs'],

            // POS Operation Permissions
            ['code' => 'pos.access', 'name' => 'Access POS Terminal', 'module' => 'pos', 'description' => 'Can access cashier POS interface'],
            ['code' => 'pos.checkout', 'name' => 'Process Checkout', 'module' => 'pos', 'description' => 'Can process sales transactions'],
            ['code' => 'pos.refund', 'name' => 'Process Refund', 'module' => 'pos', 'description' => 'Can issue sales refunds'],

            // Inventory Permissions
            ['code' => 'inventory.view', 'name' => 'View Inventory', 'module' => 'inventory', 'description' => 'Can view store stock levels'],
            ['code' => 'inventory.update', 'name' => 'Update Inventory', 'module' => 'inventory', 'description' => 'Can adjust stock levels'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::updateOrCreate(
                ['code' => $permissionData['code']],
                $permissionData
            );
        }

        // 2. Define Standard POS Roles and Permission Matrix
        $rolesMatrix = [
            'Admin' => [
                'code' => 'admin',
                'is_system' => true,
                'permissions' => Permission::pluck('code')->all(),
            ],
            'Store_Manager' => [
                'code' => 'store_manager',
                'is_system' => false,
                'permissions' => [
                    'dashboard.view',
                    'users.view', 'users.create', 'users.update', 'users.manage',
                    'roles.view',
                    'pos_pin.view', 'pos_pin.update', 'pos_pin.verify', 'pos_pin.manage',
                    'devices.view', 'devices.block', 'devices.manage',
                    'sessions.view', 'sessions.revoke',
                    'login_attempts.view',
                    'pos.access', 'pos.checkout', 'pos.refund',
                    'inventory.view', 'inventory.update',
                ],
            ],
            'Cashier' => [
                'code' => 'cashier',
                'is_system' => false,
                'permissions' => [
                    'pos_pin.verify',
                    'pos.access',
                    'pos.checkout',
                ],
            ],
            'Inventory_Clerk' => [
                'code' => 'inventory_clerk',
                'is_system' => false,
                'permissions' => [
                    'dashboard.view',
                    'inventory.view',
                    'inventory.update',
                ],
            ],
        ];

        foreach ($rolesMatrix as $roleName => $config) {
            $role = Role::firstOrCreate(
                ['code' => $config['code']],
                [
                    'name' => $roleName,
                    'is_system' => $config['is_system'],
                    'uuid' => (string) Str::uuid(),
                ]
            );

            $role->update([
                'name' => $roleName,
                'is_system' => $config['is_system'],
            ]);

            $permissionIds = Permission::whereIn('code', $config['permissions'])->pluck('id');
            $role->permissions()->sync($permissionIds);

            // Invalidate Redis cache for users with this role
            RbacCacheService::forgetRoleUsersCache($role);
        }
    }
}
