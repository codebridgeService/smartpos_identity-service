<?php

namespace App\Http\Middleware;

use App\Models\UserSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeviceAndSessionActive
{
    /**
     * Handle an incoming request.
     *
     * IDN-02 FIX: The `sid` (session UUID) claim is now REQUIRED in the JWT.
     * Tokens minted without a session binding can no longer bypass session
     * revocation, expiry, and device-block checks.
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

        // IDN-02 FIX: Check session context claim
        $sessionUuid = null;
        try {
            $sessionUuid = $guard->payload()->get('sid');
        } catch (\Throwable $e) {
            // If payload cannot be read
        }

        if (! $sessionUuid) {
            if (config('jwt.require_session_claim', false)) {
                Log::warning('[SECURITY_MISSING_SESSION_CLAIM] JWT without sid claim rejected', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                ]);

                return response()->json([
                    'message' => 'Invalid session context. Token must contain a valid session ID.',
                ], 401);
            }

            return $next($request);
        }

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

        return $next($request);
    }
}

