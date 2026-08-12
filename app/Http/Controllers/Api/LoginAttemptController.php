<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class LoginAttemptController extends Controller
{
    /**
     * Get paginated login attempts for the authenticated user.
     */
    public function index()
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');

        /** @var User|null $user */
        $user = $guard->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return $user
            ->loginAttempts()
            ->latest('attempted_at')
            ->paginate(30);
    }
}