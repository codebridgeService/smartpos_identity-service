<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;
use App\Models\LoginAttempt;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Login using email, phone number, or username.
     */
    public function login(Request $request)
    {
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
        | Find user
        |--------------------------------------------------------------------------
        */

        $column = $this->loginColumn(
            $data['login']
        );

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
                'identifier' => $data['login'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'failed',
                'failure_reason' => 'invalid_credentials',
                'attempted_at' => now(),
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
                'identifier' => $data['login'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'blocked',
                'failure_reason' => 'account_not_active',
                'attempted_at' => now(),
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
                'user_id' => $user->id,
                'device_uuid' => $data['device_uuid'],
            ],
            [
                'device_name' => $data['device_name'] ?? null,
                'device_type' => $data['device_type'] ?? null,
                'platform' => $data['platform'] ?? null,
        
                'first_ip_address' => $request->ip(),
            ]
        );

        
        /*
        |--------------------------------------------------------------------------
        | Blocked device
        |--------------------------------------------------------------------------
        */

        if ($device->is_blocked) {
            LoginAttempt::create([
                'user_id' => $user->id,
                'identifier' => $data['login'],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'blocked',
                'failure_reason' => 'device_blocked',
                'attempted_at' => now(),
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
                $data['device_name'] ?? $device->device_name,

            'device_type' =>
                $data['device_type'] ?? $device->device_type,

            'platform' =>
                $data['platform'] ?? $device->platform,

            'last_ip_address' =>
                $request->ip(),

            'last_seen_at' =>
                now(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Create refresh-token session
        |--------------------------------------------------------------------------
        */

        $refreshSecret = Str::random(80);

        $session = UserSession::create([
            'user_id' => $user->id,

            'user_device_id' =>
                $device->id,

            'refresh_token_hash' =>
                Hash::make($refreshSecret),

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
        | Generate JWT access token
        |--------------------------------------------------------------------------
        */

        /** @var JWTGuard $guard */
        $guard = auth('api');

        $accessToken = $guard
            ->claims([
                'sid' => $session->uuid,
                'device_uuid' => $device->uuid,
            ])
            ->login($user);
        /*
        |--------------------------------------------------------------------------
        | Update user login information
        |--------------------------------------------------------------------------
        */

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Save successful login
        |--------------------------------------------------------------------------
        */

        LoginAttempt::create([
            'user_id' => $user->id,
            'identifier' => $data['login'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'success',
            'failure_reason' => null,
            'attempted_at' => now(),
        ]);

        return response()->json([
            'access_token' => $accessToken,

            'refresh_token' =>
                $session->uuid . '.' . $refreshSecret,

            'token_type' => 'Bearer',

            'expires_in' =>
                config('jwt.ttl') * 60,

            'user' => $user,
        ]);
    }

    /**
     * Refresh JWT access token.
     */
  /**
 * Refresh JWT access token.
 */
public function refresh(Request $request)
{
    /*
    |--------------------------------------------------------------------------
    | Validate request
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

    [$sessionUuid, $secret] = array_pad(
        explode(
            '.',
            $data['refresh_token'],
            2
        ),
        2,
        null
    );

    if (! $sessionUuid || ! $secret) {
        return response()->json([
            'message' => 'Invalid refresh token.',
        ], 401);
    }

    /*
    |--------------------------------------------------------------------------
    | Find refresh-token session
    |--------------------------------------------------------------------------
    */

    $session = UserSession::with([
        'user',
        'device',
    ])
        ->where('uuid', $sessionUuid)
        ->first();

    /*
    |--------------------------------------------------------------------------
    | Check session exists
    |--------------------------------------------------------------------------
    */

    if (! $session) {
        return response()->json([
            'message' => 'Invalid refresh token.',
        ], 401);
    }

    /*
    |--------------------------------------------------------------------------
    | Check user exists
    |--------------------------------------------------------------------------
    */

    if (! $session->user) {
        return response()->json([
            'message' => 'User not found.',
        ], 401);
    }

    /*
    |--------------------------------------------------------------------------
    | Check device exists
    |--------------------------------------------------------------------------
    */

    if (! $session->device) {
        $session->update([
            'revoked_at' => now(),
        ]);

        return response()->json([
            'message' => 'Device not found.',
        ], 401);
    }

    /*
    |--------------------------------------------------------------------------
    | Check revoked session
    |--------------------------------------------------------------------------
    */

    if ($session->revoked_at) {
        return response()->json([
            'message' => 'Session has been revoked.',
        ], 401);
    }

    /*
    |--------------------------------------------------------------------------
    | Check refresh session expiration
    |--------------------------------------------------------------------------
    |
    | The refresh session expires 30 days after login.
    |
    | We DO NOT update expires_at here.
    |
    */

    if (
        ! $session->expires_at ||
        $session->expires_at->isPast()
    ) {
        return response()->json([
            'message' => 'Refresh session has expired. Please login again.',
        ], 401);
    }

    /*
    |--------------------------------------------------------------------------
    | Verify refresh-token secret
    |--------------------------------------------------------------------------
    */

    if (
        ! Hash::check(
            $secret,
            $session->refresh_token_hash
        )
    ) {
        return response()->json([
            'message' => 'Invalid refresh token.',
        ], 401);
    }

    /*
    |--------------------------------------------------------------------------
    | Check account status
    |--------------------------------------------------------------------------
    */

    if ($session->user->status !== 'active') {
        $session->update([
            'revoked_at' => now(),
        ]);

        return response()->json([
            'message' => 'Account is not active.',
        ], 403);
    }

    /*
    |--------------------------------------------------------------------------
    | Check blocked device
    |--------------------------------------------------------------------------
    */

    if ($session->device->is_blocked) {
        $session->update([
            'revoked_at' => now(),
        ]);

        return response()->json([
            'message' => 'Device is blocked.',
        ], 403);
    }

    /*
    |--------------------------------------------------------------------------
    | Generate new refresh-token secret
    |--------------------------------------------------------------------------
    |
    | Refresh-token rotation:
    |
    | old token -> invalid
    | new token -> returned to client
    |
    */

    $newSecret = Str::random(80);

    /*
    |--------------------------------------------------------------------------
    | Update refresh session
    |--------------------------------------------------------------------------
    */

    $session->update([
        'refresh_token_hash' => Hash::make(
            $newSecret
        ),

        'last_activity_at' => now(),

        'ip_address' => $request->ip(),

        'user_agent' => $request->userAgent(),

        // Do NOT update expires_at.
        // It remains 30 days from the original login.
    ]);

    /*
    |--------------------------------------------------------------------------
    | Generate new JWT access token
    |--------------------------------------------------------------------------
    */

    /** @var JWTGuard $guard */
    $guard = auth('api');

    $accessToken = $guard
        ->claims([
            'sid' => $session->uuid,
            'device_uuid' => $session->device->uuid,
        ])
        ->login($session->user);

    /*
    |--------------------------------------------------------------------------
    | Return new tokens
    |--------------------------------------------------------------------------
    */

    return response()->json([
        'access_token' => $accessToken,

        'refresh_token' =>
            $session->uuid . '.' . $newSecret,

        'token_type' => 'Bearer',

        'expires_in' =>
            config('jwt.ttl') * 60,

        'refresh_expires_at' =>
            $session->expires_at->toISOString(),
    ]);
}

    /**
     * Return the authenticated user.
     */

    public function me()
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');

        /*
        |--------------------------------------------------------------------------
        | Get authenticated user ID
        |--------------------------------------------------------------------------
        */

        $userId = $guard->id();

        if (! $userId) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Get session UUID from JWT
        |--------------------------------------------------------------------------
        */

        $sessionUuid = $guard
            ->payload()
            ->get('sid');

        if (! $sessionUuid) {
            return response()->json([
                'message' => 'Invalid session.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Check session
        |--------------------------------------------------------------------------
        */

        $session = UserSession::with('device')
            ->where('uuid', $sessionUuid)
            ->where('user_id', $userId)
            ->first();

        if (! $session) {
            return response()->json([
                'message' => 'Session not found.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Check revoked session
        |--------------------------------------------------------------------------
        */

        if ($session->revoked_at) {
            return response()->json([
                'message' => 'Session has been revoked.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Check expired session
        |--------------------------------------------------------------------------
        */

        if (
            $session->expires_at &&
            $session->expires_at->isPast()
        ) {
            return response()->json([
                'message' => 'Session has expired.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Check blocked device
        |--------------------------------------------------------------------------
        */

        if ($session->device?->is_blocked) {
            return response()->json([
                'message' => 'Device is blocked.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Load user with roles and permissions
        |--------------------------------------------------------------------------
        */

        $user = User::with('roles.permissions')
            ->find($userId);

        if (! $user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        return response()->json([
            'user' => $user,
        ]);
    }

    /**
     * Logout and revoke refresh-token session.
     */
    public function logout(Request $request)
    {
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
    
        [$sessionUuid] = array_pad(
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
        | Revoke refresh-token session
        |--------------------------------------------------------------------------
        */
    
        if ($sessionUuid) {
            UserSession::where('uuid', $sessionUuid)
                ->where('user_id', $guard->id())
                ->whereNull('revoked_at')
                ->update([
                    'revoked_at' => now(),
                ]);
        }
    
        /*
        |--------------------------------------------------------------------------
        | Invalidate JWT
        |--------------------------------------------------------------------------
        */
    
        $guard->logout();
    
        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Detect whether login is email, phone, or username.
     */
    private function loginColumn(
        string $login
    ): string {
        if (
            filter_var(
                $login,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            return 'email';
        }

        if (
            preg_match(
                '/^\+?[0-9]{7,20}$/',
                $login
            )
        ) {
            return 'phone';
        }

        return 'username';
    }
}