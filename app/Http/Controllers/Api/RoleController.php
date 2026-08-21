<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\RbacCacheService;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * List all roles with attached permissions, optionally filtered by business_uuid.
     */
    public function index(Request $request)
    {
        return Role::query()
            ->with('permissions')
            ->when(
                $request->business_uuid,
                fn ($q, $uuid) =>
                    $q->where('business_uuid', $uuid)
            )
            ->paginate(20);
    }

    /**
     * Create a new role.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'business_uuid' => [
                'nullable',
                'uuid'
            ],
            'name' => [
                'required',
                'string',
                'max:100'
            ],
            'code' => [
                'required',
                'string',
                'max:100'
            ],
            'is_system' => [
                'boolean'
            ],
        ]);

        $role = Role::create($data);

        // Auto-synchronize default permissions if matching standard template exists
        $templates = \App\Services\RoleProvisionService::getStandardRoleTemplates();
        $matchingTemplate = collect($templates)->firstWhere('code', strtolower(trim($role->code)));

        if ($matchingTemplate) {
            $permissionIds = Permission::whereIn('code', $matchingTemplate['permissions'])->pluck('id');
            $role->permissions()->sync($permissionIds);
            \App\Services\RbacCacheService::forgetRoleUsersCache($role);
        }

        return $role->load('permissions');
    }

    /**
     * Get role details with loaded permissions.
     */
    public function show(Role $role)
    {
        return $role->load('permissions');
    }

    /**
     * Update role information.
     */
    public function update(
        Request $request,
        Role $role
    ) {
        $role->update(
            $request->validate([
                'name' => [
                    'sometimes',
                    'string',
                    'max:100'
                ],
                'code' => [
                    'sometimes',
                    'string',
                    'max:100'
                ],
            ])
        );

        return $role;
    }

    /**
     * Delete a role.
     *
     * IDN-01 FIX: System template roles (is_system = true) are protected
     * from deletion to prevent accidental destruction of core RBAC templates
     * (owner, admin, cashier, etc.) that the provisioning system relies on.
     */
    public function destroy(Role $role)
    {
        if ($role->is_system) {
            \Illuminate\Support\Facades\Log::warning('[SECURITY_SYSTEM_ROLE_DELETE_BLOCKED] Attempt to delete system role', [
                'role_id' => $role->id,
                'role_code' => $role->code,
                'user_id' => auth('api')->id(),
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'message' => 'System roles cannot be deleted. Remove the system flag first if this is intentional.',
            ], 403);
        }

        $role->delete();

        return response()->json([
            'message' => 'Role deleted.'
        ]);
    }

    /**
     * Synchronize a list of permissions with a role using permission UUIDs.
     */
    /**
     * Synchronize a list of permissions with a role using permission UUIDs, or all permissions.
     */
    public function syncPermissions(
        Request $request,
        Role $role
    ) {
        $data = $request->validate([
            'all' => [
                'sometimes',
                'boolean',
            ],
            'permission_uuids' => [
                'required_without:all',
                'array',
            ],
            'permission_uuids.*' => [
                'uuid',
                'exists:permissions,uuid',
            ],
        ]);

        if (! empty($data['all']) && $data['all'] === true) {
            $ids = Permission::pluck('id');
            $role->permissions()->sync($ids);
        } else {
            $ids = Permission::query()
                ->whereIn(
                    'uuid',
                    $data['permission_uuids'] ?? []
                )
                ->pluck('id');

            $role->permissions()->syncWithoutDetaching($ids);
        }

        RbacCacheService::forgetRoleUsersCache($role);

        return $role->load('permissions');
    }

    /**
     * Attach ALL available permissions to a role in one call.
     */
    public function syncAllPermissions(Role $role)
    {
        $ids = Permission::pluck('id');
        $role->permissions()->sync($ids);

        RbacCacheService::forgetRoleUsersCache($role);

        return response()->json([
            'message' => 'All permissions attached to role successfully.',
            'count' => $ids->count(),
            'data' => $role->load('permissions'),
        ]);
    }

    /**
     * Auto-provision standard roles (Store_Manager, Cashier, Inventory_Clerk) for a business.
     */
    public function provision(
        Request $request,
        \App\Services\RoleProvisionService $provisioner
    ) {
        $data = $request->validate([
            'business_uuid' => [
                'required',
                'uuid',
            ],
        ]);

        $roles = $provisioner->provisionForBusiness($data['business_uuid']);

        return response()->json([
            'message' => 'Standard roles provisioned successfully.',
            'count' => $roles->count(),
            'data' => $roles,
        ], 201);
    }
}