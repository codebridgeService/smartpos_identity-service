<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RoleProvisionService
{
    /**
     * Standard role templates and their default permission mappings.
     */
    public static function getStandardRoleTemplates(): array
    {
        return [
            'Owner' => [
                'code' => 'owner',
                'permissions' => [
                    'dashboard.view',
                    'users.view', 'users.create', 'users.update', 'users.delete', 'users.manage',
                    'roles.view', 'roles.create', 'roles.update', 'roles.delete', 'roles.manage',
                    'user_roles.assign', 'user_roles.remove',
                    'permissions.view',
                    'pos_pin.view', 'pos_pin.update', 'pos_pin.verify', 'pos_pin.manage',
                    'devices.view', 'devices.trust', 'devices.block', 'devices.manage',
                    'sessions.view', 'sessions.revoke',
                    'login_attempts.view',
                    'pos.access', 'pos.checkout', 'pos.refund',
                    'inventory.view', 'inventory.update',
                    'businesses.view', 'businesses.create', 'businesses.update', 'businesses.delete',
                    'business_users.view', 'business_users.manage',
                    'outlets.view', 'outlets.create', 'outlets.update', 'outlets.delete',
                    'registers.view', 'registers.create', 'registers.update', 'registers.manage',
                    'pos_devices.view', 'pos_devices.create', 'pos_devices.update', 'pos_devices.manage',
                ],
            ],
            'Store_Manager' => [
                'code' => 'store_manager',
                'permissions' => [
                    'dashboard.view',
                    'users.view', 'users.create', 'users.update', 'users.manage',
                    'roles.view',
                    'pos_pin.view', 'pos_pin.update', 'pos_pin.verify', 'pos_pin.manage',
                    'devices.view', 'devices.trust', 'devices.block', 'devices.manage',
                    'sessions.view', 'sessions.revoke',
                    'login_attempts.view',
                    'pos.access', 'pos.checkout', 'pos.refund',
                    'inventory.view', 'inventory.update',
                    'businesses.view',
                    'business_users.view',
                    'outlets.view', 'outlets.update',
                    'registers.view', 'registers.create', 'registers.update', 'registers.manage',
                    'pos_devices.view', 'pos_devices.create', 'pos_devices.update', 'pos_devices.manage',
                ],
            ],
            'Cashier' => [
                'code' => 'cashier',
                'permissions' => [
                    'pos_pin.verify',
                    'pos.access',
                    'pos.checkout',
                    'registers.view',
                    'pos_devices.view',
                ],
            ],
            'Inventory_Clerk' => [
                'code' => 'inventory_clerk',
                'permissions' => [
                    'dashboard.view',
                    'inventory.view',
                    'inventory.update',
                    'outlets.view',
                ],
            ],
        ];
    }

    /**
     * Auto-provision standard roles and permissions for a business.
     */
    public function provisionForBusiness(string $businessUuid): Collection
    {
        $templates = self::getStandardRoleTemplates();
        $provisionedRoles = collect();

        foreach ($templates as $roleName => $config) {
            $role = Role::firstOrCreate(
                [
                    'business_uuid' => $businessUuid,
                    'code' => $config['code'],
                ],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $roleName,
                    'is_system' => false,
                ]
            );

            $role->update([
                'name' => $roleName,
                'is_system' => false,
            ]);

            // Sync predefined permissions
            $permissionIds = Permission::whereIn('code', $config['permissions'])->pluck('id');
            $role->permissions()->sync($permissionIds);

            // Invalidate Redis cache
            RbacCacheService::forgetRoleUsersCache($role);

            $provisionedRoles->push($role->fresh('permissions'));
        }

        return $provisionedRoles;
    }
}
