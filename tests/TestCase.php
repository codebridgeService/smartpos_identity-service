<?php

namespace Tests;

use App\Models\UserDevice;
use App\Models\UserSession;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create a UserDevice + UserSession for the given user,
     * then return a JWT that includes the required `sid` claim.
     *
     * IDN-02: The session.active middleware now requires `sid` in all JWTs.
     * This helper ensures all tests create properly session-bound tokens.
     */
    protected function createTestSession($user): string
    {
        $device = UserDevice::firstOrCreate(
            [
                'user_id' => $user->id,
                'device_uuid' => 'test-device-' . $user->id,
            ],
            [
                'device_name' => 'Test Device',
                'device_type' => 'browser',
                'platform' => 'testing',
                'first_ip_address' => '127.0.0.1',
            ]
        );

        $session = UserSession::create([
            'user_id' => $user->id,
            'user_device_id' => $device->id,
            'refresh_token_hash' => Hash::make(Str::random(80)),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestSuite/1.0',
            'last_activity_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = auth('api');

        return $guard->claims([
            'sid' => $session->uuid,
            'user_uuid' => $user->uuid,
            'device_uuid' => $device->device_uuid,
        ])->login($user);
    }
}
