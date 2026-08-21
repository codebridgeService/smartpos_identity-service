<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ForgotPasswordCodeMail;
use App\Models\AuthOtp;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends Controller
{
    /**
     * Send forgot-password OTP to email.
     */
    public function sendCode(Request $request)
    {
        $data = $request->validate([
            'email' => [
                'required',
                'email',
                'max:150',
            ],
        ]);
    
        $user = User::where(
            'email',
            $data['email']
        )->first();
    
        if (! $user) {
            return response()->json([
                'message' =>
                    'If the email exists, a verification code has been sent.',
            ]);
        }
    
        /*
        |--------------------------------------------------------------------------
        | Generate OTP
        |--------------------------------------------------------------------------
        */
    
        $code = (string) random_int(
            100000,
            999999
        );
    
        /*
        |--------------------------------------------------------------------------
        | Save OTP
        |--------------------------------------------------------------------------
        */
    
        AuthOtp::create([
            'user_id' =>
                $user->id,
    
            'channel' =>
                'email',
    
            'identifier' =>
                $user->email,
    
            'purpose' =>
                'forgot_password',
    
            'code_hash' =>
                Hash::make($code),
    
            'expires_at' =>
                now()->addMinutes(10),
    
            'attempts' =>
                0,
        ]);
    
        /*
        |--------------------------------------------------------------------------
        | Send OTP to email
        |--------------------------------------------------------------------------
        */
    
        Mail::to(
            $user->email
        )->send(
            new ForgotPasswordCodeMail(
                $code
            )
        );
    
        return response()->json([
            'message' =>
                'If the email exists, a verification code has been sent.',

            'expires_in' =>
                600,
        ]);
    }

    /**
     * Verify forgot-password OTP.
     *
     * IDN-03 FIX: OTP verification now uses pessimistic locking (FOR UPDATE)
     * inside a DB transaction to prevent parallel brute-force requests from
     * bypassing the 5-attempt limit via stale counter reads.
     */
    public function verifyCode(Request $request)
    {
        $data = $request->validate([
            'email' => [
                'required',
                'email',
                'max:150',
            ],

            'code' => [
                'required',
                'digits:6',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | IDN-03 FIX: Atomic OTP verification with row-level locking
        |--------------------------------------------------------------------------
        */

        return DB::transaction(function () use ($data) {

            // lockForUpdate() ensures concurrent requests serialize on this row
            $otp = AuthOtp::where(
                'identifier',
                $data['email']
            )
                ->where(
                    'channel',
                    'email'
                )
                ->where(
                    'purpose',
                    'forgot_password'
                )
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $otp) {
                return response()->json([
                    'message' =>
                        'Invalid verification code.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Check already verified
            |--------------------------------------------------------------------------
            */

            if ($otp->verified_at) {
                return response()->json([
                    'message' =>
                        'Verification code has already been used.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Check expiration
            |--------------------------------------------------------------------------
            */

            if ($otp->expires_at->isPast()) {
                $otp->delete();

                return response()->json([
                    'message' =>
                        'Verification code has expired.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Check attempts (atomic under lock)
            |--------------------------------------------------------------------------
            */

            if ($otp->attempts >= 5) {
                $otp->delete();

                return response()->json([
                    'message' =>
                        'Too many failed attempts. Please request a new code.',
                ], 429);
            }

            /*
            |--------------------------------------------------------------------------
            | Verify code
            |--------------------------------------------------------------------------
            */

            if (
                ! Hash::check(
                    $data['code'],
                    $otp->code_hash
                )
            ) {
                $otp->increment(
                    'attempts'
                );

                return response()->json([
                    'message' =>
                        'Invalid verification code.',

                    'attempts_remaining' =>
                        max(
                            0,
                            4 - $otp->attempts
                        ),
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Mark OTP verified
            |--------------------------------------------------------------------------
            */

            $otp->update([
                'verified_at' =>
                    now(),
            ]);

            return response()->json([
                'message' =>
                    'Verification code verified successfully.',

                'otp_uuid' =>
                    $otp->uuid,
            ]);
        });
    }

    /**
     * Reset password after OTP verification.
     */
    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'email' => [
                'required',
                'email',
                'max:150',
            ],

            'otp_uuid' => [
                'required',
                'uuid',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Find user
        |--------------------------------------------------------------------------
        */

        $user = User::where(
            'email',
            $data['email']
        )->first();

        if (! $user) {
            return response()->json([
                'message' =>
                    'Invalid password reset request.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Find verified OTP
        |--------------------------------------------------------------------------
        */

        $otp = AuthOtp::where(
            'uuid',
            $data['otp_uuid']
        )
            ->where(
                'user_id',
                $user->id
            )
            ->where(
                'identifier',
                $user->email
            )
            ->where(
                'channel',
                'email'
            )
            ->where(
                'purpose',
                'forgot_password'
            )
            ->whereNotNull(
                'verified_at'
            )
            ->first();

        if (! $otp) {
            return response()->json([
                'message' =>
                    'Please verify your email code first.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Check expiration
        |--------------------------------------------------------------------------
        */

        if ($otp->expires_at->isPast()) {
            $otp->delete();

            return response()->json([
                'message' =>
                    'Password reset request has expired.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Reset password
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use (
            $user,
            $otp,
            $data
        ) {
            /*
            |--------------------------------------------------------------------------
            | Update user password
            |--------------------------------------------------------------------------
            */

            $user->update([
                'password' =>
                    Hash::make(
                        $data['password']
                    ),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Revoke all refresh-token sessions
            |--------------------------------------------------------------------------
            */

            UserSession::where(
                'user_id',
                $user->id
            )
                ->whereNull(
                    'revoked_at'
                )
                ->update([
                    'revoked_at' =>
                        now(),
                ]);

            /*
            |--------------------------------------------------------------------------
            | Delete OTP
            |--------------------------------------------------------------------------
            |
            | OTP can only be used once.
            |
            */

            $otp->delete();
        });

        return response()->json([
            'message' =>
                'Password reset successfully. Please login again.',
        ]);
    }
}