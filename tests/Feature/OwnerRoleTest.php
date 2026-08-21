<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\OwnerPermissionSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class OwnerRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_owner_role_has_full_business_and_management_permissions(): void
    {
        $ownerRole = Role::where('code', 'owner')->first();
        $this->assertNotNull($ownerRole);

        $permissionCodes = $ownerRole->permissions->pluck('code')->all();

        // Business management
        $this->assertContains('businesses.view', $permissionCodes);
        $this->assertContains('businesses.create', $permissionCodes);
        $this->assertContains('businesses.update', $permissionCodes);
        $this->assertContains('businesses.delete', $permissionCodes);

        // Outlets & locations
        $this->assertContains('outlets.view', $permissionCodes);
        $this->assertContains('outlets.create', $permissionCodes);
        $this->assertContains('outlets.update', $permissionCodes);
        $this->assertContains('outlets.delete', $permissionCodes);

        // Cash registers & POS devices
        $this->assertContains('registers.view', $permissionCodes);
        $this->assertContains('registers.create', $permissionCodes);
        $this->assertContains('registers.update', $permissionCodes);
        $this->assertContains('registers.manage', $permissionCodes);
        $this->assertContains('pos_devices.view', $permissionCodes);
        $this->assertContains('pos_devices.create', $permissionCodes);
        $this->assertContains('pos_devices.update', $permissionCodes);
        $this->assertContains('pos_devices.manage', $permissionCodes);

        // User & Role management
        $this->assertContains('users.view', $permissionCodes);
        $this->assertContains('users.create', $permissionCodes);
        $this->assertContains('users.update', $permissionCodes);
        $this->assertContains('users.delete', $permissionCodes);
        $this->assertContains('users.manage', $permissionCodes);
        $this->assertContains('roles.view', $permissionCodes);
        $this->assertContains('roles.create', $permissionCodes);
        $this->assertContains('roles.update', $permissionCodes);
        $this->assertContains('roles.delete', $permissionCodes);
        $this->assertContains('user_roles.assign', $permissionCodes);

        // POS Operations & Inventory
        $this->assertContains('pos.access', $permissionCodes);
        $this->assertContains('pos.checkout', $permissionCodes);
        $this->assertContains('pos.refund', $permissionCodes);
        $this->assertContains('inventory.view', $permissionCodes);
        $this->assertContains('inventory.update', $permissionCodes);

        // Security, PIN, Device, Sessions, Dashboard
        $this->assertContains('dashboard.view', $permissionCodes);
        $this->assertContains('pos_pin.manage', $permissionCodes);
        $this->assertContains('devices.manage', $permissionCodes);
        $this->assertContains('sessions.revoke', $permissionCodes);
        $this->assertContains('login_attempts.view', $permissionCodes);
    }

    public function test_owner_role_is_seeded_with_permissions(): void
    {
        $ownerRole = Role::where('code', 'owner')->first();
        $this->assertNotNull($ownerRole);
        $this->assertGreaterThan(0, $ownerRole->permissions()->count());
    }

    public function test_owner_user_login_returns_permissions_in_jwt_and_response(): void
    {
        $ownerRole = Role::where('code', 'owner')->first();

        $user = User::factory()->create([
            'username' => 'owner_1',
            'email' => 'owner@example.com',
            'password' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
        $user->roles()->attach($ownerRole->id);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'owner@example.com',
            'password' => 'Secret123!',
            'device_uuid' => 'owner-device-123',
        ]);

        $response->assertStatus(200);

        // Assert response contains roles and permissions
        $responseRoles = $response->json('user.roles');
        $responsePermissions = $response->json('user.permissions');

        $this->assertEquals(['owner'], $responseRoles);
        $this->assertNotEmpty($responsePermissions);
        $this->assertContains('businesses.view', $responsePermissions);
        $this->assertContains('businesses.create', $responsePermissions);
        $this->assertContains('outlets.view', $responsePermissions);
        $this->assertContains('users.manage', $responsePermissions);

        // Assert JWT token claims also contain permissions
        $token = $response->json('access_token');
        $payload = JWTAuth::setToken($token)->getPayload();
        $jwtPermissions = $payload->get('permissions');

        $this->assertNotEmpty($jwtPermissions);
        $this->assertContains('businesses.view', $jwtPermissions);
        $this->assertContains('pos.access', $jwtPermissions);
    }

    public function test_standalone_owner_permission_seeder(): void
    {
        $this->seed(OwnerPermissionSeeder::class);

        $ownerRole = Role::where('code', 'owner')->first();
        $this->assertNotNull($ownerRole);
        $this->assertGreaterThan(0, $ownerRole->permissions()->count());
    }

    public function test_creating_user_with_role_code_owner_auto_syncs_permissions(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $adminRole = Role::where('code', 'admin')->first();
        $admin->roles()->attach($adminRole->id);
        $adminToken = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->postJson('/api/v1/users', [
                'name' => 'Store Owner',
                'username' => 'store_owner_1',
                'email' => 'store_owner@pos.com',
                'password' => 'Password123!',
                'role_code' => 'owner',
            ]);

        $response->assertStatus(201);
        $this->assertEquals('owner', $response->json('data.roles.0.code'));

        $user = User::where('email', 'store_owner@pos.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('owner'));

        $permissions = $user->permissionCodes();
        $this->assertContains('businesses.view', $permissions);
        $this->assertContains('businesses.create', $permissions);
        $this->assertContains('outlets.create', $permissions);
        $this->assertContains('registers.manage', $permissions);
        $this->assertContains('pos.checkout', $permissions);
    }

    public function test_creating_owner_role_via_api_auto_syncs_permissions(): void
    {
        $admin = User::factory()->create(['status' => 'active']);
        $adminRole = Role::where('code', 'admin')->first();
        $admin->roles()->attach($adminRole->id);
        $adminToken = auth('api')->login($admin);

        $businessUuid = (string) \Illuminate\Support\Str::uuid();

        $response = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->postJson('/api/v1/roles', [
                'name' => 'Store Owner Custom',
                'code' => 'owner',
                'business_uuid' => $businessUuid,
            ]);

        $response->assertStatus(201);

        $role = Role::where('business_uuid', $businessUuid)->where('code', 'owner')->first();
        $this->assertNotNull($role);
        $this->assertGreaterThan(30, $role->permissions()->count());
        $this->assertTrue($role->permissions()->where('code', 'businesses.create')->exists());
        $this->assertTrue($role->permissions()->where('code', 'outlets.create')->exists());
    }
}
