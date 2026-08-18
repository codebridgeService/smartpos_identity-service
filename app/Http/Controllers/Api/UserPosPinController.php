<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserPosPin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserPosPinController extends Controller
{
    /**
     * Create or update POS PIN.
     */
    public function update(
        Request $request,
        User $user
    ): JsonResponse {
        $authUser = auth('api')->user();

        if ($authUser && (int) $authUser->id !== (int) $user->id && ! $authUser->hasPermission('pos_pin.manage')) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $data = $request->validate([
            'business_uuid' => [
                'required',
                'uuid',
            ],

            'pin' => [
                'required',
                'digits_between:4,6',
            ],
        ]);

        $posPin = UserPosPin::updateOrCreate(
            [
                'user_id' => $user->id,
                'business_uuid' => $data['business_uuid'],
            ],
            [
                'pin_hash' => Hash::make($data['pin']),
                'is_active' => true,
                'failed_attempts' => 0,
                'locked_until' => null,
            ]
        );

        return response()->json([
            'message' => 'POS PIN updated successfully.',
            'data' => [
                'uuid' => $posPin->uuid,
                'user_uuid' => $user->uuid,
                'business_uuid' => $posPin->business_uuid,
                'is_active' => $posPin->is_active,
            ],
        ]);
    }

    /**
     * Verify POS PIN.
     */
    public function verify(
        Request $request,
        User $user
    ): JsonResponse {
        $authUser = auth('api')->user();

        if ($authUser && (int) $authUser->id !== (int) $user->id && ! $authUser->hasPermission('pos_pin.manage')) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $data = $request->validate([
            'business_uuid' => [
                'required',
                'uuid',
            ],

            'pin' => [
                'required',
                'digits_between:4,6',
            ],
        ]);

        $posPin = UserPosPin::query()
            ->where('user_id', $user->id)
            ->where(
                'business_uuid',
                $data['business_uuid']
            )
            ->first();

        if (! $posPin) {
            return response()->json([
                'message' => 'POS PIN has not been configured.',
            ], 404);
        }

        if (! $posPin->is_active) {
            return response()->json([
                'message' => 'POS PIN is disabled.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Check temporary lock
        |--------------------------------------------------------------------------
        */

        if (
            $posPin->locked_until &&
            $posPin->locked_until->isFuture()
        ) {
            return response()->json([
                'message' => 'POS PIN is temporarily locked.',
                'locked_until' =>
                    $posPin->locked_until->toIso8601String(),
            ], 423);
        }

        /*
        |--------------------------------------------------------------------------
        | Check PIN
        |--------------------------------------------------------------------------
        */

        if (! Hash::check(
            $data['pin'],
            $posPin->pin_hash
        )) {
            $attempts =
                $posPin->failed_attempts + 1;

            $lockedUntil = null;

            if ($attempts >= 5) {
                $lockedUntil = now()->addMinutes(15);
            }

            $posPin->update([
                'failed_attempts' => $attempts,
                'locked_until' => $lockedUntil,
            ]);

            return response()->json([
                'message' => $lockedUntil
                    ? 'Invalid PIN. PIN locked for 15 minutes.'
                    : 'Invalid PIN.',

                'remaining_attempts' =>
                    max(0, 5 - $attempts),

                'locked_until' =>
                    $lockedUntil?->toIso8601String(),
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | PIN correct
        |--------------------------------------------------------------------------
        */

        $posPin->update([
            'failed_attempts' => 0,
            'locked_until' => null,
        ]);

        return response()->json([
            'message' => 'POS PIN verified successfully.',
            'data' => [
                'user_uuid' => $user->uuid,
                'business_uuid' =>
                    $posPin->business_uuid,
            ],
        ]);
    }
}