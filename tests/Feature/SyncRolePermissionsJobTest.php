<?php

namespace Tests\Feature;

use App\Jobs\SyncRolePermissionsJob;
use App\Models\Role;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class SyncRolePermissionsJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_sync_role_permissions_job_syncs_owner_permissions(): void
    {
        // 1. Create an owner role with zero permissions
        $businessUuid = (string) Str::uuid();
        $role = Role::create([
            'business_uuid' => $businessUuid,
            'name' => 'Store Owner',
            'code' => 'owner',
            'is_system' => false,
        ]);
        $role->permissions()->detach();
        $this->assertEquals(0, $role->permissions()->count());

        // 2. Dispatch job synchronously
        $job = new SyncRolePermissionsJob(roleCode: 'owner', businessUuid: $businessUuid);
        $job->handle();

        // 3. Assert permissions were synchronized
        $role->refresh();
        $this->assertGreaterThan(30, $role->permissions()->count());
        $this->assertTrue($role->permissions()->where('code', 'businesses.create')->exists());
        $this->assertTrue($role->permissions()->where('code', 'outlets.create')->exists());
        $this->assertTrue($role->permissions()->where('code', 'registers.manage')->exists());
    }

    public function test_sync_role_permissions_job_with_business_uuid_provisions_all_standard_roles(): void
    {
        $businessUuid = (string) Str::uuid();

        $job = new SyncRolePermissionsJob(businessUuid: $businessUuid);
        $job->handle();

        $roles = Role::where('business_uuid', $businessUuid)->get();
        $this->assertCount(4, $roles);

        $owner = $roles->firstWhere('code', 'owner');
        $this->assertNotNull($owner);
        $this->assertTrue($owner->permissions()->where('code', 'businesses.update')->exists());
    }

    public function test_artisan_rbac_sync_command(): void
    {
        $this->artisan('rbac:sync', ['--role' => 'owner'])
            ->expectsOutputToContain('Permissions synchronized successfully')
            ->assertExitCode(0);
    }

    public function test_artisan_rbac_sync_command_dispatches_to_queue(): void
    {
        Queue::fake();

        $this->artisan('rbac:sync', ['--role' => 'owner', '--queue' => true])
            ->expectsOutputToContain('Dispatched SyncRolePermissionsJob to queue successfully.')
            ->assertExitCode(0);

        Queue::assertPushed(SyncRolePermissionsJob::class, function ($job) {
            return $job->roleCode === 'owner';
        });
    }
}
