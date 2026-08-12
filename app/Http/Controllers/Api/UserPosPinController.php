<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserPosPin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserPosPinController extends Controller
{
    /**
     * Set or update the POS PIN for a user in a specific business.
     */
    public function update(
        Request $request,
        User $user
    ) {
        $data = $request->validate([
            'business_uuid' => [
                'required',
                'uuid'
            ],
            'pin' => [
                'required',
                'digits_between:4,6'
            ],
        ]);

        UserPosPin::updateOrCreate(
            [
                'user_id' => $user->id,
                'business_uuid' =>
                    $data['business_uuid'],
            ],
            [
                'pin_hash' =>
                    Hash::make($data['pin']),

                'is_active' => true,

                'failed_attempts' => 0,

                'locked_until' => null,
            ]
        );

        return response()->json([
            'message' => 'POS PIN updated.'
        ]);
    }

    /**
     * Verify a user's POS PIN for a business with security checks (lockout on repeated failures).
     */
    public function verify(
        Request $request,
        User $user
    ) {
        $data = $request->validate([
            'business_uuid' => [
                'required',
                'uuid'
            ],
            'pin' => [
                'required',
                'digits_between:4,6'
            ],
        ]);

        $pin = UserPosPin::where(
            'user_id',
            $user->id
        )
        ->where(
            'business_uuid',
            $data['business_uuid']
        )
        ->firstOrFail();

        if (! $pin->is_active) {
            return response()->json([
                'message' => 'PIN disabled.'
            ], 403);
        }

        if (
            $pin->locked_until &&
            $pin->locked_until->isFuture()
        ) {
            return response()->json([
                'message' => 'PIN temporarily locked.'
            ], 423);
        }

        if (! Hash::check(
            $data['pin'],
            $pin->pin_hash
        )) {
            $attempts =
                $pin->failed_attempts + 1;

            $pin->update([
                'failed_attempts' => $attempts,

                'locked_until' =>
                    $attempts >= 5
                        ? now()->addMinutes(15)
                        : null,
            ]);

            return response()->json([
                'message' => 'Invalid PIN.'
            ], 401);
        }

        $pin->update([
            'failed_attempts' => 0,
            'locked_until' => null,
        ]);

        return response()->json([
            'message' => 'PIN valid.'
        ]);
    }
}