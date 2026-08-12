<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserSession;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class UserSessionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | List My Sessions
    |--------------------------------------------------------------------------
    |
    | GET /api/v1/sessions
    |
    */

    public function index(Request $request)
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');

        $userId = $guard->id();

        if (! $userId) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Get current session UUID from JWT
        |--------------------------------------------------------------------------
        */

        $currentSessionUuid = $guard
            ->payload()
            ->get('sid');

        /*
        |--------------------------------------------------------------------------
        | Get sessions
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | Do NOT return refresh_token_hash.
        |
        */

        $sessions = UserSession::query()
            ->select([
                'id',
                'uuid',
                'user_id',
                'user_device_id',
                'ip_address',
                'user_agent',
                'last_activity_at',
                'expires_at',
                'revoked_at',
                'created_at',
                'updated_at',
            ])
            ->with([
                'device',
            ])
            ->where(
                'user_id',
                $userId
            )
            ->orderByDesc(
                'last_activity_at'
            )
            ->paginate(30);

        /*
        |--------------------------------------------------------------------------
        | Add session status
        |--------------------------------------------------------------------------
        */

        $sessions->getCollection()
            ->transform(
                function (UserSession $session) use ($currentSessionUuid) {

                    return [
                        'uuid' =>
                            $session->uuid,

                        'is_current' =>
                            $session->uuid === $currentSessionUuid,

                        'status' =>
                            $this->sessionStatus($session),

                        'ip_address' =>
                            $session->ip_address,

                        'user_agent' =>
                            $session->user_agent,

                        'last_activity_at' =>
                            $session->last_activity_at,

                        'expires_at' =>
                            $session->expires_at,

                        'revoked_at' =>
                            $session->revoked_at,

                        'created_at' =>
                            $session->created_at,

                        /*
                        |--------------------------------------------------------------------------
                        | Device
                        |--------------------------------------------------------------------------
                        */

                        'device' =>
                            $session->device
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

                                    'last_seen_at' =>
                                        $session->device->last_seen_at,
                                ]
                                : null,
                    ];
                }
            );

        return response()->json(
            $sessions
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Revoke One Session
    |--------------------------------------------------------------------------
    |
    | DELETE /api/v1/sessions/{userSession}
    |
    */

    public function destroy(
        UserSession $userSession
    ) {
        /** @var JWTGuard $guard */
        $guard = auth('api');

        $userId = $guard->id();

        if (! $userId) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Security Check
        |--------------------------------------------------------------------------
        |
        | User can only revoke their own session.
        |
        */

        if (
            (int) $userSession->user_id
            !==
            (int) $userId
        ) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Already Revoked
        |--------------------------------------------------------------------------
        */

        if ($userSession->revoked_at) {
            return response()->json([
                'message' =>
                    'Session is already revoked.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Current Session UUID
        |--------------------------------------------------------------------------
        */

        $currentSessionUuid = $guard
            ->payload()
            ->get('sid');

        $isCurrentSession =
            $currentSessionUuid ===
            $userSession->uuid;

        /*
        |--------------------------------------------------------------------------
        | Revoke Session
        |--------------------------------------------------------------------------
        */

        $userSession->update([
            'revoked_at' =>
                now(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | If Current Session
        |--------------------------------------------------------------------------
        |
        | Invalidate current JWT too.
        |
        */

        if ($isCurrentSession) {
            $guard->logout();
        }

        return response()->json([
            'message' =>
                'Session revoked successfully.',

            'session_uuid' =>
                $userSession->uuid,

            'was_current_session' =>
                $isCurrentSession,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Revoke All Sessions
    |--------------------------------------------------------------------------
    |
    | DELETE /api/v1/sessions
    |
    | Default:
    | Keep current session active.
    |
    | Request:
    |
    | {
    |     "except_current": true
    | }
    |
    */

    public function destroyAll(
        Request $request
    ) {
        $data = $request->validate([
            'except_current' => [
                'sometimes',
                'boolean',
            ],
        ]);

        /** @var JWTGuard $guard */
        $guard = auth('api');

        $userId =
            $guard->id();

        if (! $userId) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Current Session
        |--------------------------------------------------------------------------
        */

        $currentSessionUuid = $guard
            ->payload()
            ->get('sid');

        /*
        |--------------------------------------------------------------------------
        | Default Safe Behavior
        |--------------------------------------------------------------------------
        |
        | Keep the current device logged in.
        |
        */

        $exceptCurrent =
            $data['except_current'] ?? true;

        /*
        |--------------------------------------------------------------------------
        | Build Query
        |--------------------------------------------------------------------------
        */

        $query = UserSession::query()
            ->where(
                'user_id',
                $userId
            )
            ->whereNull(
                'revoked_at'
            );

        /*
        |--------------------------------------------------------------------------
        | Keep Current Session
        |--------------------------------------------------------------------------
        */

        if (
            $exceptCurrent &&
            $currentSessionUuid
        ) {
            $query->where(
                'uuid',
                '!=',
                $currentSessionUuid
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Revoke Sessions
        |--------------------------------------------------------------------------
        */

        $revokedCount =
            $query->update([
                'revoked_at' =>
                    now(),
            ]);

        /*
        |--------------------------------------------------------------------------
        | Logout Current Session
        |--------------------------------------------------------------------------
        |
        | If except_current = false,
        | current session is also revoked.
        |
        */

        if (! $exceptCurrent) {
            $guard->logout();
        }

        return response()->json([
            'message' =>
                $exceptCurrent
                    ? 'Other sessions revoked successfully.'
                    : 'All sessions revoked successfully.',

            'revoked_count' =>
                $revokedCount,

            'current_session_kept' =>
                $exceptCurrent,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Session Status
    |--------------------------------------------------------------------------
    */

    private function sessionStatus(
        UserSession $session
    ): string {

        /*
        |--------------------------------------------------------------------------
        | Revoked
        |--------------------------------------------------------------------------
        */

        if ($session->revoked_at) {
            return 'revoked';
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
            return 'expired';
        }

        /*
        |--------------------------------------------------------------------------
        | Active
        |--------------------------------------------------------------------------
        */

        return 'active';
    }
}