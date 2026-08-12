<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;

class UserDeviceController extends Controller
{
    /**
     * List registered devices for the authenticated user.
     */
    public function index()
    {
        return auth('api')
            ->user()
            ->devices()
            ->latest()
            ->get();
    }

    /**
     * Mark a device as trusted for the authenticated user.
     */
    public function trust(
        UserDevice $userDevice
    ) {
        abort_unless(
            $userDevice->user_id ===
            auth('api')->id(),
            403
        );

        $userDevice->update([
            'is_trusted' => true
        ]);

        return $userDevice;
    }

    /**
     * Block a device and revoke all active sessions associated with it.
     */
    public function block(
        UserDevice $userDevice
    ) {
        abort_unless(
            $userDevice->user_id ===
            auth('api')->id(),
            403
        );

        $userDevice->update([
            'is_blocked' => true
        ]);

        $userDevice->sessions()
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now()
            ]);

        return $userDevice;
    }
}