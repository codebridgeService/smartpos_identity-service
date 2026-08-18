<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\UserSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SessionAndDeviceSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private UserDevice $device;
    private UserSession $session;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'status' => 'active',
        ]);

        $role = Role::create([
            'name' => 'Admin',
            'code' => 'admin',
        ]);

        $permission = Permission::create([
            'name' => 'View Permissions',
            'code' => 'permissions.view',
            'module' => 'permissions',
        ]);

        $role->permissions()->attach($permission->id);
        $this->user->roles()->attach($role->id);

        $this->device = UserDevice::create([
            'user_id' => $this->user->id,
            'device_uuid' => (string) Str::uuid(),
            'device_name' => 'POS Terminal 01',
            'device_type' => 'pos',
            'platform' => 'android',
            'is_trusted' => true,
            'is_blocked' => false,
        ]);

        $this->session = UserSession::create([
            'user_id' => $this->user->id,
            'user_device_id' => $this->device->id,
            'refresh_token_hash' => Hash::make(Str::random(80)),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'SmartPOS Terminal',
            'last_activity_at' => now(),
            'expires_at' => now()->addDays(30),
            'revoked_at' => null,
        ]);

        $this->token = auth('api')->claims([
            'sid' => $this->session->uuid,
            'user_uuid' => $this->user->uuid,
            'device_uuid' => $this->device->device_uuid,
            'roles' => ['admin'],
            'permissions' => ['permissions.view'],
        ])->login($this->user);
    }

    public function test_active_session_and_unblocked_device_can_access_permissions(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/permissions');

        $response->assertStatus(200);
    }

    public function test_blocked_device_is_rejected_on_all_protected_endpoints(): void
    {
        // Block the device
        $this->device->update(['is_blocked' => true]);

        // Calling /permissions should now return 403 Forbidden with "Device is blocked."
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/permissions');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Device is blocked.',
            ]);

        // Calling /auth/me should also return 403 Forbidden with "Device is blocked."
        $meResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/auth/me');

        $meResponse->assertStatus(403)
            ->assertJson([
                'message' => 'Device is blocked.',
            ]);
    }

    public function test_revoked_session_is_rejected(): void
    {
        // Revoke the session
        $this->session->update(['revoked_at' => now()]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/permissions');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Session has been revoked.',
            ]);
    }

    public function test_expired_session_is_rejected(): void
    {
        // Expire the session
        $this->session->update(['expires_at' => now()->subMinutes(5)]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/permissions');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Session has expired.',
            ]);
    }

    public function test_inactive_user_is_rejected(): void
    {
        // Deactivate user
        $this->user->update(['status' => 'inactive']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/permissions');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Account is not active.',
            ]);
    }
}
