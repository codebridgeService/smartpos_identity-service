<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginAttempt;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class AuthController extends Controller
{
   
    /**
     * Login
     */

    public function login(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate request
        |
        | Login using:
        |
        | - email
        | - phone
        | - username
        |--------------------------------------------------------------------------
        */

        $data = $request->validate([
            'login' => [
                'required',
                'string',
            ],

            'password' => [
                'required',
                'string',
            ],

            'device_uuid' => [
                'required',
                'string',
                'max:150',
            ],

            'device_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'device_type' => [
                'nullable',
                'string',
                'max:50',
            ],

            'platform' => [
                'nullable',
                'string',
                'max:50',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Detect login column
        |--------------------------------------------------------------------------
        */

        $column = $this->loginColumn(
            $data['login']
        );

        /*
        |--------------------------------------------------------------------------
        | Find user
        |--------------------------------------------------------------------------
        */

        $user = User::where(
            $column,
            $data['login']
        )->first();

        /*
        |--------------------------------------------------------------------------
        | Invalid credentials
        |--------------------------------------------------------------------------
        */

        if (
            ! $user ||
            ! $user->password ||
            ! Hash::check(
                $data['password'],
                $user->password
            )
        ) {
            LoginAttempt::create([
                'user_id' => $user?->id,

                'identifier' =>
                    $data['login'],

                'ip_address' =>
                    $request->ip(),

                'user_agent' =>
                    $request->userAgent(),

                'status' =>
                    'failed',

                'failure_reason' =>
                    'invalid_credentials',

                'attempted_at' =>
                    now(),
            ]);

            return response()->json([
                'message' => 'Invalid login credentials.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Check account status
        |--------------------------------------------------------------------------
        */

        if ($user->status !== 'active') {
            LoginAttempt::create([
                'user_id' => $user->id,

                'identifier' =>
                    $data['login'],

                'ip_address' =>
                    $request->ip(),

                'user_agent' =>
                    $request->userAgent(),

                'status' =>
                    'blocked',

                'failure_reason' =>
                    'account_not_active',

                'attempted_at' =>
                    now(),
            ]);

            return response()->json([
                'message' => 'Account is not active.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Find or create device
        |--------------------------------------------------------------------------
        */

        $device = UserDevice::firstOrCreate(
            [
                'user_id' =>
                    $user->id,

                'device_uuid' =>
                    $data['device_uuid'],
            ],
            [
                'device_name' =>
                    $data['device_name'] ?? null,

                'device_type' =>
                    $data['device_type'] ?? null,

                'platform' =>
                    $data['platform'] ?? null,

                'first_ip_address' =>
                    $request->ip(),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Check blocked device
        |--------------------------------------------------------------------------
        */

        if ($device->is_blocked) {
            LoginAttempt::create([
                'user_id' => $user->id,

                'identifier' =>
                    $data['login'],

                'ip_address' =>
                    $request->ip(),

                'user_agent' =>
                    $request->userAgent(),

                'status' =>
                    'blocked',

                'failure_reason' =>
                    'device_blocked',

                'attempted_at' =>
                    now(),
            ]);

            return response()->json([
                'message' => 'Device is blocked.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Update device information
        |--------------------------------------------------------------------------
        */

        $device->update([
            'device_name' =>
                $data['device_name']
                ?? $device->device_name,

            'device_type' =>
                $data['device_type']
                ?? $device->device_type,

            'platform' =>
                $data['platform']
                ?? $device->platform,

            'last_ip_address' =>
                $request->ip(),

            'last_seen_at' =>
                now(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Create refresh token
        |--------------------------------------------------------------------------
        */

        $refreshSecret = Str::random(80);

        /*
        |--------------------------------------------------------------------------
        | Create user session
        |--------------------------------------------------------------------------
        */

        $session = UserSession::create([
            'user_id' =>
                $user->id,

            'user_device_id' =>
                $device->id,

            'refresh_token_hash' =>
                Hash::make(
                    $refreshSecret
                ),

            'ip_address' =>
                $request->ip(),

            'user_agent' =>
                $request->userAgent(),

            'last_activity_at' =>
                now(),

            'expires_at' =>
                now()->addDays(30),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Load RBAC
        |--------------------------------------------------------------------------
        |
        | User
        |   ↓
        | user_roles
        |   ↓
        | roles
        |   ↓
        | role_permissions
        |   ↓
        | permissions
        |
        */

        $user->load(
            'roles.permissions'
        );

        /*
        |--------------------------------------------------------------------------
        | Role codes
        |--------------------------------------------------------------------------
        */

        $roles = $user->roleCodes();

        /*
        |--------------------------------------------------------------------------
        | Permission codes
        |--------------------------------------------------------------------------
        */

        $permissions =
            $user->permissionCodes();

        /*
        |--------------------------------------------------------------------------
        | Generate JWT access token
        |--------------------------------------------------------------------------
        */

        /** @var JWTGuard $guard */
        $guard = auth('api');

        $accessToken = $guard
            ->claims([
                /*
                |--------------------------------------------------------------------------
                | Session
                |--------------------------------------------------------------------------
                */

                'sid' =>
                    $session->uuid,

                /*
                |--------------------------------------------------------------------------
                | User UUID
                |--------------------------------------------------------------------------
                |
                | Useful for other microservices.
                |
                */

                'user_uuid' =>
                    $user->uuid,

                /*
                |--------------------------------------------------------------------------
                | Device
                |--------------------------------------------------------------------------
                */

                'device_uuid' =>
                    $device->device_uuid,

                /*
                |--------------------------------------------------------------------------
                | RBAC
                |--------------------------------------------------------------------------
                */

                'roles' =>
                    $roles,

                'permissions' =>
                    $permissions,
            ])
            ->login($user);

        /*
        |--------------------------------------------------------------------------
        | Update user login
        |--------------------------------------------------------------------------
        */

        $user->update([
            'last_login_at' =>
                now(),

            'last_login_ip' =>
                $request->ip(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Successful login attempt
        |--------------------------------------------------------------------------
        */

        LoginAttempt::create([
            'user_id' =>
                $user->id,

            'identifier' =>
                $data['login'],

            'ip_address' =>
                $request->ip(),

            'user_agent' =>
                $request->userAgent(),

            'status' =>
                'success',

            'failure_reason' =>
                null,

            'attempted_at' =>
                now(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'access_token' =>
                $accessToken,

            'refresh_token' =>
                $session->uuid
                . '.'
                . $refreshSecret,

            'token_type' =>
                'Bearer',

            'expires_in' =>
                config('jwt.ttl') * 60,

            'refresh_expires_at' =>
                $session->expires_at
                    ->toISOString(),

            'user' => [
                'uuid' =>
                    $user->uuid,

                'name' =>
                    $user->name,

                'username' =>
                    $user->username,

                'email' =>
                    $user->email,

                'phone' =>
                    $user->phone,

                'avatar' =>
                    $user->avatar,

                'status' =>
                    $user->status,

                /*
                |--------------------------------------------------------------------------
                | RBAC
                |--------------------------------------------------------------------------
                */

                'roles' =>
                    $roles,

                'permissions' =>
                    $permissions,
            ],
        ]);
    }

    /**
     * Register
     */

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'username' => [
                'required',
                'string',
                'max:100',
                'alpha_dash',
                'unique:users,username',
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
                'unique:users,email',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
                'unique:users,phone',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],

            /*
            |--------------------------------------------------------------------------
            | Device
            |--------------------------------------------------------------------------
            */

            'device_uuid' => [
                'required',
                'string',
                'max:150',
            ],

            'device_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'device_type' => [
                'nullable',
                'string',
                'max:50',
            ],

            'platform' => [
                'nullable',
                'string',
                'max:50',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Create user
        |--------------------------------------------------------------------------
        */

        $user = User::create([
            'uuid' =>
                (string) Str::uuid(),

            'name' =>
                $data['name'],

            'username' =>
                $data['username'],

            'email' =>
                $data['email'] ?? null,

            'phone' =>
                $data['phone'] ?? null,

            'password' =>
                Hash::make(
                    $data['password']
                ),

            'status' =>
                'active',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Login automatically
        |--------------------------------------------------------------------------
        */

        $request->merge([
            'login' =>
                $user->username,
        ]);

        return $this->login(
            $request
        );
    }

    /**
     * Refresh
     */

    public function refresh(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate
        |--------------------------------------------------------------------------
        */

        $data = $request->validate([
            'refresh_token' => [
                'required',
                'string',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Split refresh token
        |--------------------------------------------------------------------------
        |
        | Format:
        |
        | session_uuid.secret
        |
        */

        [
            $sessionUuid,
            $secret
        ] = array_pad(
            explode(
                '.',
                $data['refresh_token'],
                2
            ),
            2,
            null
        );

        if (
            ! $sessionUuid ||
            ! $secret
        ) {
            return response()->json([
                'message' =>
                    'Invalid refresh token.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Find session
        |--------------------------------------------------------------------------
        */

        $session = UserSession::with([
            'user.roles.permissions',
            'device',
        ])
            ->where(
                'uuid',
                $sessionUuid
            )
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Session not found
        |--------------------------------------------------------------------------
        */

        if (! $session) {
            return response()->json([
                'message' =>
                    'Invalid refresh token.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | User not found
        |--------------------------------------------------------------------------
        */

        if (! $session->user) {
            return response()->json([
                'message' =>
                    'User not found.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Device not found
        |--------------------------------------------------------------------------
        */

        if (! $session->device) {
            $session->update([
                'revoked_at' =>
                    now(),
            ]);

            return response()->json([
                'message' =>
                    'Device not found.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Revoked session
        |--------------------------------------------------------------------------
        */

        if ($session->revoked_at) {
            return response()->json([
                'message' =>
                    'Session has been revoked.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Expired refresh session
        |--------------------------------------------------------------------------
        */

        if (
            ! $session->expires_at ||
            $session->expires_at->isPast()
        ) {
            return response()->json([
                'message' =>
                    'Refresh session has expired. Please login again.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Validate refresh secret
        |--------------------------------------------------------------------------
        */

        if (
            ! Hash::check(
                $secret,
                $session->refresh_token_hash
            )
        ) {
            return response()->json([
                'message' =>
                    'Invalid refresh token.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Account status
        |--------------------------------------------------------------------------
        */

        if (
            $session->user->status
            !== 'active'
        ) {
            $session->update([
                'revoked_at' =>
                    now(),
            ]);

            return response()->json([
                'message' =>
                    'Account is not active.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Device blocked
        |--------------------------------------------------------------------------
        */

        if (
            $session->device->is_blocked
        ) {
            $session->update([
                'revoked_at' =>
                    now(),
            ]);

            return response()->json([
                'message' =>
                    'Device is blocked.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Generate new refresh secret
        |--------------------------------------------------------------------------
        |
        | Refresh token rotation:
        |
        | OLD:
        | session.secret-old
        |
        | NEW:
        | session.secret-new
        |
        */

        $newSecret =
            Str::random(80);

        /*
        |--------------------------------------------------------------------------
        | Update session
        |--------------------------------------------------------------------------
        */

        $session->update([
            'refresh_token_hash' =>
                Hash::make(
                    $newSecret
                ),

            'last_activity_at' =>
                now(),

            'ip_address' =>
                $request->ip(),

            'user_agent' =>
                $request->userAgent(),

            /*
            |--------------------------------------------------------------------------
            | IMPORTANT
            |--------------------------------------------------------------------------
            |
            | We DO NOT extend expires_at.
            |
            | The refresh session is still maximum 30 days
            | from the original login.
            |
            */
        ]);

        /*
        |--------------------------------------------------------------------------
        | Current RBAC
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | We read roles and permissions again during refresh.
        |
        | Therefore if Admin changes a user's permission,
        | the next access token receives the new permissions.
        |
        */

        $user = $session->user;

        $user->load(
            'roles.permissions'
        );

        $roles =
            $user->roleCodes();

        $permissions =
            $user->permissionCodes();

        /*
        |--------------------------------------------------------------------------
        | Generate new access token
        |--------------------------------------------------------------------------
        */

        /** @var JWTGuard $guard */
        $guard = auth('api');

        $accessToken = $guard
            ->claims([
                'sid' =>
                    $session->uuid,

                'user_uuid' =>
                    $user->uuid,

                'device_uuid' =>
                    $session
                        ->device
                        ->device_uuid,

                'roles' =>
                    $roles,

                'permissions' =>
                    $permissions,
            ])
            ->login($user);

        /*
        |--------------------------------------------------------------------------
        | Return new tokens
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'access_token' =>
                $accessToken,

            'refresh_token' =>
                $session->uuid
                . '.'
                . $newSecret,

            'token_type' =>
                'Bearer',

            'expires_in' =>
                config('jwt.ttl') * 60,

            'refresh_expires_at' =>
                $session
                    ->expires_at
                    ->toISOString(),

            /*
            |--------------------------------------------------------------------------
            | Optional but useful
            |--------------------------------------------------------------------------
            */

            'roles' =>
                $roles,

            'permissions' =>
                $permissions,
        ]);
    }

    /**
     * Me
     */

    public function me()
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');

        /*
        |--------------------------------------------------------------------------
        | Authenticated user ID
        |--------------------------------------------------------------------------
        */

        $userId =
            $guard->id();

        if (! $userId) {
            return response()->json([
                'message' =>
                    'Unauthenticated.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Session UUID from JWT
        |--------------------------------------------------------------------------
        */

        $sessionUuid = $guard
            ->payload()
            ->get('sid');

        if (! $sessionUuid) {
            return response()->json([
                'message' =>
                    'Invalid session.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Find current session
        |--------------------------------------------------------------------------
        */

        $session = UserSession::with(
            'device'
        )
            ->where(
                'uuid',
                $sessionUuid
            )
            ->where(
                'user_id',
                $userId
            )
            ->first();

        if (! $session) {
            return response()->json([
                'message' =>
                    'Session not found.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Revoked
        |--------------------------------------------------------------------------
        */

        if ($session->revoked_at) {
            return response()->json([
                'message' =>
                    'Session has been revoked.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Expired
        |--------------------------------------------------------------------------
        */

        if (
            $session->expires_at &&
            $session->expires_at->isPast()
        ) {
            return response()->json([
                'message' =>
                    'Session has expired.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Device blocked
        |--------------------------------------------------------------------------
        */

        if (
            $session->device?->is_blocked
        ) {
            return response()->json([
                'message' =>
                    'Device is blocked.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Load user RBAC
        |--------------------------------------------------------------------------
        */

        $user = User::with(
            'roles.permissions'
        )->find(
            $userId
        );

        if (! $user) {
            return response()->json([
                'message' =>
                    'User not found.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Check account
        |--------------------------------------------------------------------------
        */

        if ($user->status !== 'active') {
            return response()->json([
                'message' =>
                    'Account is not active.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | RBAC
        |--------------------------------------------------------------------------
        */

        $roles =
            $user->roleCodes();

        $permissions =
            $user->permissionCodes();

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'user' => [
                'uuid' =>
                    $user->uuid,

                'name' =>
                    $user->name,

                'username' =>
                    $user->username,

                'email' =>
                    $user->email,

                'phone' =>
                    $user->phone,

                'avatar' =>
                    $user->avatar,

                'status' =>
                    $user->status,

                'email_verified_at' =>
                    $user->email_verified_at,

                'last_login_at' =>
                    $user->last_login_at,

                /*
                |--------------------------------------------------------------------------
                | RBAC
                |--------------------------------------------------------------------------
                */

                'roles' =>
                    $roles,

                'permissions' =>
                    $permissions,
            ],

            'session' => [
                'uuid' =>
                    $session->uuid,

                'expires_at' =>
                    $session
                        ->expires_at
                        ?->toISOString(),

                'last_activity_at' =>
                    $session
                        ->last_activity_at
                        ?->toISOString(),
            ],

            'device' => $session->device
                ? [
                    'uuid' =>
                        $session->device->uuid,

                    'device_uuid' =>
                        $session->device->device_uuid,

                    'device_name' =>
                        $session->device->device_name,

                    'device_type' =>
                        $session->device->device_type,

                    'platform' =>
                        $session->device->platform,

                    'is_trusted' =>
                        $session->device->is_trusted,

                    'is_blocked' =>
                        $session->device->is_blocked,
                ]
                : null,
        ]);
    }

    /**
     * Logout
     */

    public function logout(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate
        |--------------------------------------------------------------------------
        */

        $data = $request->validate([
            'refresh_token' => [
                'required',
                'string',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Split refresh token
        |--------------------------------------------------------------------------
        */

        [
            $sessionUuid
        ] = array_pad(
            explode(
                '.',
                $data['refresh_token'],
                2
            ),
            2,
            null
        );

        /*
        |--------------------------------------------------------------------------
        | JWT Guard
        |--------------------------------------------------------------------------
        */

        /** @var JWTGuard $guard */
        $guard = auth('api');

        /*
        |--------------------------------------------------------------------------
        | Current JWT session
        |--------------------------------------------------------------------------
        */

        $jwtSessionUuid = $guard
            ->payload()
            ->get('sid');

        /*
        |--------------------------------------------------------------------------
        | Make sure refresh session matches JWT session
        |--------------------------------------------------------------------------
        */

        if (
            $sessionUuid &&
            $jwtSessionUuid &&
            $sessionUuid !== $jwtSessionUuid
        ) {
            return response()->json([
                'message' =>
                    'Invalid session.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Revoke refresh session
        |--------------------------------------------------------------------------
        */

        if ($sessionUuid) {
            UserSession::where(
                'uuid',
                $sessionUuid
            )
                ->where(
                    'user_id',
                    $guard->id()
                )
                ->whereNull(
                    'revoked_at'
                )
                ->update([
                    'revoked_at' =>
                        now(),
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Invalidate access token
        |--------------------------------------------------------------------------
        */

        $guard->logout();

        return response()->json([
            'message' =>
                'Logged out successfully.',
        ]);
    }

    /**
     * Detect Login Column
     */

    private function loginColumn(
        string $login
    ): string {
        /*
        |--------------------------------------------------------------------------
        | Email
        |--------------------------------------------------------------------------
        */

        if (
            filter_var(
                $login,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            return 'email';
        }

        /*
        |--------------------------------------------------------------------------
        | Phone
        |--------------------------------------------------------------------------
        */

        if (
            preg_match(
                '/^\+?[0-9]{7,20}$/',
                $login
            )
        ) {
            return 'phone';
        }

        /*
        |--------------------------------------------------------------------------
        | Username
        |--------------------------------------------------------------------------
        */

        return 'username';
    }
}