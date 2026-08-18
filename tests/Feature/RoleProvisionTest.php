<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RoleProvisionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed baseline permissions
        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->create(['status' => 'active']);
        $adminRole = Role::where('code', 'admin')->first();
        $this->admin->roles()->attach($adminRole->id);

        $this->adminToken = auth('api')->login($this->admin);
    }

    public function test_can_auto_provision_standard_roles_for_a_business(): void
    {
        $businessUuid = (string) Str::uuid();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/api/v1/roles/provision', [
                'business_uuid' => $businessUuid,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Standard roles provisioned successfully.',
                'count' => 3,
            ]);

        // Assert 3 business roles were created
        $roles = Role::where('business_uuid', $businessUuid)->get();
        $this->assertCount(3, $roles);

        $storeManager = $roles->firstWhere('code', 'store_manager');
        $cashier = $roles->firstWhere('code', 'cashier');
        $inventoryClerk = $roles->firstWhere('code', 'inventory_clerk');

        $this->assertNotNull($storeManager);
        $this->assertNotNull($cashier);
        $this->assertNotNull($inventoryClerk);

        // Assert permissions are properly attached
        $this->assertTrue($storeManager->permissions()->where('code', 'pos.refund')->exists());
        $this->assertTrue($cashier->permissions()->where('code', 'pos.checkout')->exists());
        $this->assertFalse($cashier->permissions()->where('code', 'pos.refund')->exists());
        $this->assertTrue($inventoryClerk->permissions()->where('code', 'inventory.update')->exists());
    }

    public function test_can_edit_and_update_provisioned_role(): void
    {
        $businessUuid = (string) Str::uuid();

        $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/api/v1/roles/provision', [
                'business_uuid' => $businessUuid,
            ]);

        $cashierRole = Role::where('business_uuid', $businessUuid)->where('code', 'cashier')->first();

        // 1. Update role name
        $updateResponse = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->putJson("/api/v1/roles/{$cashierRole->uuid}", [
                'name' => 'Senior Cashier',
            ]);

        $updateResponse->assertStatus(200);
        $this->assertEquals('Senior Cashier', $cashierRole->fresh()->name);

        // 2. Add pos.refund permission to this cashier role
        $refundPermission = Permission::where('code', 'pos.refund')->first();
        $syncResponse = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson("/api/v1/roles/{$cashierRole->uuid}/permissions", [
                'permission_uuids' => [
                    $refundPermission->uuid,
                ],
            ]);

        $syncResponse->assertStatus(200);
        $this->assertTrue($cashierRole->fresh()->permissions()->where('code', 'pos.refund')->exists());
    }

    public function test_provision_requires_roles_create_permission(): void
    {
        $cashier = User::factory()->create(['status' => 'active']);
        $cashierRole = Role::where('code', 'cashier')->first();
        $cashier->roles()->attach($cashierRole->id);

        $cashierToken = auth('api')->login($cashier);

        $response = $this->withHeader('Authorization', 'Bearer ' . $cashierToken)
            ->postJson('/api/v1/roles/provision', [
                'business_uuid' => (string) Str::uuid(),
            ]);

        $response->assertStatus(403);
    }

    public function test_can_sync_all_permissions_to_a_role(): void
    {
        $customRole = Role::create([
            'name' => 'Custom Supervisor',
            'code' => 'custom_supervisor',
            'uuid' => (string) Str::uuid(),
        ]);

        $this->assertEquals(0, $customRole->permissions()->count());

        // Call /roles/{role}/permissions/all
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson("/api/v1/roles/{$customRole->uuid}/permissions/all");

        $response->assertStatus(200);
        $totalPermissions = Permission::count();
        $this->assertEquals($totalPermissions, $customRole->fresh()->permissions()->count());
    }

    public function test_creating_user_with_role_code_auto_assigns_role_and_default_permissions(): void
    {
        $businessUuid = (string) Str::uuid();

        // Create new cashier user with role_code=cashier and business_uuid
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/api/v1/users', [
                'name' => 'John Cashier',
                'username' => 'cashier_john',
                'email' => 'cashier_john@store.com',
                'password' => 'Password123!',
                'role_code' => 'cashier',
                'business_uuid' => $businessUuid,
            ]);

        $response->assertStatus(201);
        $this->assertCount(1, $response->json('data.roles'));
        $this->assertEquals('cashier', $response->json('data.roles.0.code'));

        $user = User::where('email', 'cashier_john@store.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->roles()->where('code', 'cashier')->exists());

        // Verify default template permissions are present for this cashier
        $permissions = $user->roles()->first()->permissions()->pluck('code')->all();
        $this->assertContains('pos.access', $permissions);
        $this->assertContains('pos.checkout', $permissions);
        $this->assertContains('pos_pin.verify', $permissions);
    }
}
