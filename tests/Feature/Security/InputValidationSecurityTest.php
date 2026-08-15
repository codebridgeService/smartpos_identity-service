<?php

namespace Tests\Feature\Security;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class InputValidationSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to authenticate a user with specific RBAC permissions.
     */
    protected function authenticateWithPermissions(array $permissions = []): array
    {
        $user = User::factory()->create(['status' => 'active']);
        $role = Role::create([
            'name' => 'Tester Role',
            'code' => 'tester_' . Str::random(8),
        ]);

        foreach ($permissions as $code) {
            $permission = Permission::firstOrCreate(
                ['code' => $code],
                ['name' => $code, 'module' => explode('.', $code)[0] ?? 'general']
            );
            $role->permissions()->attach($permission->id);
        }

        $user->roles()->attach($role->id);
        $token = auth('api')->login($user);

        return [$user, $token];
    }

    /*
    |--------------------------------------------------------------------------
    | Auth Registration Validation Tests
    |--------------------------------------------------------------------------
    */

    public function test_registration_fails_when_required_fields_are_missing(): void
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'username', 'password', 'device_uuid']);
    }

    public function test_registration_fails_with_invalid_email_and_short_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'not-a-valid-email',
            'password' => 'short',
            'password_confirmation' => 'mismatch',
            'device_uuid' => (string) Str::uuid(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_registration_fails_with_invalid_username_characters(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'username' => 'user name with spaces and special @#$!',
            'email' => 'valid@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_uuid' => (string) Str::uuid(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_registration_fails_when_username_or_email_already_exists(): void
    {
        User::factory()->create([
            'username' => 'duplicateuser',
            'email' => 'duplicate@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Another User',
            'username' => 'duplicateuser',
            'email' => 'duplicate@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_uuid' => (string) Str::uuid(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username', 'email']);
    }

    /*
    |--------------------------------------------------------------------------
    | Auth Login Validation Tests
    |--------------------------------------------------------------------------
    */

    public function test_login_fails_when_required_fields_are_missing(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['login', 'password', 'device_uuid']);
    }

    /*
    |--------------------------------------------------------------------------
    | Auth Refresh & Logout Validation Tests
    |--------------------------------------------------------------------------
    */

    public function test_refresh_token_fails_without_refresh_token(): void
    {
        $response = $this->postJson('/api/v1/auth/refresh', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['refresh_token']);
    }

    public function test_logout_fails_without_refresh_token(): void
    {
        [$user, $token] = $this->authenticateWithPermissions();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['refresh_token']);
    }

    /*
    |--------------------------------------------------------------------------
    | Forgot Password Validation Tests
    |--------------------------------------------------------------------------
    */

    public function test_forgot_password_send_code_fails_with_invalid_or_missing_email(): void
    {
        $responseEmpty = $this->postJson('/api/v1/auth/forgot-password/send-code', []);
        $responseEmpty->assertStatus(422)->assertJsonValidationErrors(['email']);

        $responseInvalid = $this->postJson('/api/v1/auth/forgot-password/send-code', [
            'email' => 'invalid-email-address',
        ]);
        $responseInvalid->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_forgot_password_verify_code_fails_with_invalid_code_format(): void
    {
        $response = $this->postJson('/api/v1/auth/verify-reset-code', [
            'email' => 'user@example.com',
            'code' => 'abc', // not 6 digits
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_forgot_password_reset_password_fails_with_invalid_inputs(): void
    {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'invalid-email',
            'otp_uuid' => 'non-uuid-string',
            'password' => 'short',
            'password_confirmation' => 'mismatch',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'otp_uuid', 'password']);
    }

    /*
    |--------------------------------------------------------------------------
    | Users API Validation Tests
    |--------------------------------------------------------------------------
    */

    public function test_user_creation_fails_with_missing_name_and_invalid_status(): void
    {
        [, $token] = $this->authenticateWithPermissions(['users.create']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/users', [
                'status' => 'invalid_status',
                'password' => 'short',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'status', 'password']);
    }

    public function test_user_update_fails_with_invalid_email_and_invalid_status(): void
    {
        [$user, $token] = $this->authenticateWithPermissions(['users.update']);
        $targetUser = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/v1/users/{$targetUser->uuid}", [
                'email' => 'not-an-email',
                'status' => 'unknown_status',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'status']);
    }

    /*
    |--------------------------------------------------------------------------
    | Roles & Permissions API Validation Tests
    |--------------------------------------------------------------------------
    */

    public function test_role_creation_fails_with_missing_name_code_and_invalid_business_uuid(): void
    {
        [, $token] = $this->authenticateWithPermissions(['roles.create']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/roles', [
                'business_uuid' => 'not-a-uuid',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'code', 'business_uuid']);
    }

    public function test_sync_role_permissions_fails_with_invalid_permission_uuids(): void
    {
        [, $token] = $this->authenticateWithPermissions(['roles.update']);
        $role = Role::create(['name' => 'Custom Role', 'code' => 'custom_role']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/roles/{$role->uuid}/permissions", [
                'permission_uuids' => ['not-a-uuid', (string) Str::uuid()], // non-existent uuid
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['permission_uuids.0', 'permission_uuids.1']);
    }

    public function test_permission_batch_creation_fails_with_missing_fields_and_duplicates(): void
    {
        [, $token] = $this->authenticateWithPermissions(['permissions.create']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/permissions', [
                [
                    'code' => 'test.duplicate',
                    'name' => '', // missing name
                ],
                [
                    'code' => 'test.duplicate', // duplicate code in batch
                    'name' => 'Duplicate Name',
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['0.name', '1.code']);
    }

    /*
    |--------------------------------------------------------------------------
    | User POS PIN API Validation Tests
    |--------------------------------------------------------------------------
    */

    public function test_pos_pin_update_fails_with_invalid_business_uuid_and_invalid_pin_length(): void
    {
        [$user, $token] = $this->authenticateWithPermissions(['pos_pin.update']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/v1/users/{$user->uuid}/pos-pin", [
                'business_uuid' => 'invalid-uuid',
                'pin' => '12', // too short (<4 digits)
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['business_uuid', 'pin']);
    }

    public function test_pos_pin_verify_fails_with_missing_pin_and_invalid_uuid(): void
    {
        [$user, $token] = $this->authenticateWithPermissions();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/users/{$user->uuid}/pos-pin/verify", [
                'business_uuid' => 'invalid-uuid',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['business_uuid', 'pin']);
    }

    /*
    |--------------------------------------------------------------------------
    | User Role Assignment Validation Tests
    |--------------------------------------------------------------------------
    */

    public function test_user_role_assignment_fails_with_nonexistent_role_uuid(): void
    {
        [, $token] = $this->authenticateWithPermissions(['user_roles.assign']);
        $targetUser = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/users/{$targetUser->uuid}/roles", [
                'role_uuid' => (string) Str::uuid(), // valid UUID format but does not exist
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role_uuid']);
    }

    /*
    |--------------------------------------------------------------------------
    | User Avatar Upload Validation Tests
    |--------------------------------------------------------------------------
    */

    public function test_avatar_upload_fails_with_invalid_file_type(): void
    {
        Storage::fake('public');
        [$user, $token] = $this->authenticateWithPermissions(['users.update']);

        $file = UploadedFile::fake()->create('malicious.php', 100, 'text/x-php');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/users/{$user->uuid}/avatar", [
                'avatar' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    }

    public function test_avatar_upload_fails_when_file_is_missing(): void
    {
        [$user, $token] = $this->authenticateWithPermissions(['users.update']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/users/{$user->uuid}/avatar", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    }

    /*
    |--------------------------------------------------------------------------
    | User Sessions Revoke All Validation Tests
    |--------------------------------------------------------------------------
    */

    public function test_session_destroy_all_fails_with_non_boolean_except_current(): void
    {
        [, $token] = $this->authenticateWithPermissions(['sessions.revoke']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/v1/sessions', [
                'except_current' => 'invalid_non_boolean',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['except_current']);
    }
}
