<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;

class UserDeviceController extends Controller
{
    public function index()
    {
        return auth('api')
            ->user()
            ->devices()
            ->latest()
            ->get();
    }

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