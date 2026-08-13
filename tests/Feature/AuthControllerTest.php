<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful user registration flow.
     */
    public function test_user_can_register_successfully()
    {
        $payload = [
            'name' => 'John Doe',
            'username' => 'johndoe',
            'email' => 'johndoe@example.com',
            'phone' => '0123456789',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_uuid' => 'test-device-uuid-12345',
            'device_name' => 'Chrome Browser',
            'device_type' => 'desktop',
            'platform' => 'macOS',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'refresh_token',
                'token_type',
                'expires_in',
                'refresh_expires_at',
                'user' => [
                    'uuid',
                    'name',
                    'username',
                    'email',
                    'phone',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'username' => 'johndoe',
            'email' => 'johndoe@example.com',
            'phone' => '0123456789',
            'status' => 'active',
        ]);
    }

    /**
     * Test successful user login flow with valid credentials.
     */
    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'username' => 'validuser',
            'email' => 'validuser@example.com',
            'password' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);

        $payload = [
            'login' => 'validuser@example.com',
            'password' => 'Secret123!',
            'device_uuid' => 'device-uuid-67890',
            'device_name' => 'POS Terminal 1',
            'device_type' => 'pos_terminal',
            'platform' => 'android',
        ];

        $response = $this->postJson('/api/v1/auth/login', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'refresh_token',
                'token_type',
                'expires_in',
                'refresh_expires_at',
                'user' => [
                    'uuid',
                    'name',
                    'username',
                    'email',
                ],
            ]);
    }

    /**
     * Test login failure with invalid credentials.
     */
    public function test_user_login_fails_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);

        $payload = [
            'login' => 'testuser',
            'password' => 'WrongPassword!',
            'device_uuid' => 'device-uuid-99999',
        ];

        $response = $this->postJson('/api/v1/auth/login', $payload);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid login credentials.',
            ]);

        $this->assertDatabaseHas('login_attempts', [
            'identifier' => 'testuser',
            'status' => 'failed',
            'failure_reason' => 'invalid_credentials',
        ]);
    }

    /**
     * Test retrieving authenticated user profile (/me endpoint).
     */
    public function test_user_can_get_authenticated_profile()
    {
        $user = User::factory()->create([
            'username' => 'profileuser',
            'password' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'login' => 'profileuser',
            'password' => 'Secret123!',
            'device_uuid' => 'device-uuid-profile',
        ]);

        $token = $loginResponse->json('access_token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'uuid',
                    'name',
                    'username',
                    'email',
                ],
                'session' => [
                    'uuid',
                ],
                'device' => [
                    'uuid',
                    'device_uuid',
                ],
            ]);
    }

    /**
     * Test refreshing access token using refresh token.
     */
    public function test_user_can_refresh_access_token()
    {
        $user = User::factory()->create([
            'username' => 'refreshuser',
            'password' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'login' => 'refreshuser',
            'password' => 'Secret123!',
            'device_uuid' => 'device-uuid-refresh',
        ]);

        $refreshToken = $loginResponse->json('refresh_token');

        $response = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'refresh_token',
                'token_type',
                'expires_in',
            ]);
    }

    /**
     * Test logout flow revoking refresh session and invalidating token.
     */
    public function test_user_can_logout_and_revoke_session()
    {
        $user = User::factory()->create([
            'username' => 'logoutuser',
            'password' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'login' => 'logoutuser',
            'password' => 'Secret123!',
            'device_uuid' => 'device-uuid-logout',
        ]);

        $accessToken = $loginResponse->json('access_token');
        $refreshToken = $loginResponse->json('refresh_token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $accessToken)
            ->postJson('/api/v1/auth/logout', [
                'refresh_token' => $refreshToken,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logged out successfully.',
            ]);

        [$sessionUuid] = explode('.', $refreshToken, 2);
        $this->assertDatabaseHas('user_sessions', [
            'uuid' => $sessionUuid,
        ]);

        $session = UserSession::where('uuid', $sessionUuid)->first();
        $this->assertNotNull($session->revoked_at);
    }
}
