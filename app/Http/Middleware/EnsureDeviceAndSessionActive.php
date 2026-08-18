<?php

namespace App\Http\Middleware;

use App\Models\UserSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeviceAndSessionActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guard = auth('api');
        $user = $guard->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'Account is not active.',
            ], 403);
        }

        $sessionUuid = null;
        try {
            $sessionUuid = $guard->payload()->get('sid');
        } catch (\Throwable $e) {
            // If payload cannot be read
        }

        if ($sessionUuid) {
            $session = UserSession::with('device')
                ->where('uuid', $sessionUuid)
                ->where('user_id', $user->id)
                ->first();

            if (! $session) {
                return response()->json([
                    'message' => 'Session not found.',
                ], 401);
            }

            if ($session->revoked_at) {
                return response()->json([
                    'message' => 'Session has been revoked.',
                ], 401);
            }

            if ($session->expires_at && $session->expires_at->isPast()) {
                return response()->json([
                    'message' => 'Session has expired.',
                ], 401);
            }

            if ($session->device?->is_blocked) {
                return response()->json([
                    'message' => 'Device is blocked.',
                ], 403);
            }
        }

        return $next($request);
    }
}
