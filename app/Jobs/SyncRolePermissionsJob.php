<?php

namespace App\Jobs;

use App\Models\Permission;
use App\Models\Role;
use App\Services\RbacCacheService;
use App\Services\RoleProvisionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncRolePermissionsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param string|null $roleCode Role code to sync (e.g., 'business_owner', 'owner', 'store_manager', 'cashier')
     * @param string|null $businessUuid Optional business UUID to scope the sync
     * @param int|null $roleId Optional specific role ID to sync
     * @param bool $syncAllTemplates Whether to sync all standard role templates across all businesses
     */
    public function __construct(
        public ?string $roleCode = null,
        public ?string $businessUuid = null,
        public ?int $roleId = null,
        public bool $syncAllTemplates = false
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $templates = RoleProvisionService::getStandardRoleTemplates();

        // 1. Specific Role ID provided
        if ($this->roleId) {
            $role = Role::find($this->roleId);
            if ($role) {
                $this->syncRoleByTemplate($role, $templates);
            }
            return;
        }

        // 2. Specific Role Code provided (e.g. 'business_owner' or 'owner')
        if ($this->roleCode) {
            $normalizedCode = strtolower(trim($this->roleCode));

            $query = Role::query()->where('code', $normalizedCode);
            if ($this->businessUuid) {
                $query->where('business_uuid', $this->businessUuid);
            }

            $roles = $query->get();

            // If role does not exist yet for this business, provision it
            if ($roles->isEmpty() && $this->businessUuid) {
                $matchingTemplate = collect($templates)->firstWhere('code', $normalizedCode);
                if ($matchingTemplate) {
                    $role = Role::create([
                        'business_uuid' => $this->businessUuid,
                        'name' => collect($templates)->search($matchingTemplate),
                        'code' => $normalizedCode,
                        'is_system' => false,
                    ]);
                    $roles = collect([$role]);
                }
            }

            foreach ($roles as $role) {
                $this->syncRoleByTemplate($role, $templates);
            }
            return;
        }

        // 3. Business UUID provided without specific roleCode -> provision all templates for that business
        if ($this->businessUuid) {
            app(RoleProvisionService::class)->provisionForBusiness($this->businessUuid);
            return;
        }

        // 4. Global sync -> sync all standard role templates
        if ($this->syncAllTemplates || (! $this->roleCode && ! $this->businessUuid && ! $this->roleId)) {
            foreach ($templates as $roleName => $config) {
                $roles = Role::where('code', $config['code'])->get();
                foreach ($roles as $role) {
                    $this->syncRoleByTemplate($role, $templates);
                }
            }
        }
    }

    /**
     * Sync permissions to a role based on standard templates.
     */
    protected function syncRoleByTemplate(Role $role, array $templates): void
    {
        $code = strtolower(trim($role->code));
        if ($code === 'business_owner') {
            $code = 'owner';
        }

        $matchingTemplate = collect($templates)->firstWhere('code', $code);

        if (! $matchingTemplate) {
            Log::warning("SyncRolePermissionsJob: No matching template found for role code '{$role->code}'");
            return;
        }

        $permissionIds = Permission::whereIn('code', $matchingTemplate['permissions'])->pluck('id');
        $role->permissions()->sync($permissionIds);

        // Invalidate Redis RBAC cache for all users holding this role
        RbacCacheService::forgetRoleUsersCache($role);

        Log::info("SyncRolePermissionsJob: Synced {$permissionIds->count()} permissions to role '{$role->code}' (ID: {$role->id}).");
    }
}
