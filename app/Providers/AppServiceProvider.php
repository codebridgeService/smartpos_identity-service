<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Scramble::configure()
            ->expose(
                ui: '/docs/identity',
                document: '/docs/identity.json',
            );

        $this->configureRateLimiting();
    }

    /**
     * Configure application rate limiters.
     */
    protected function configureRateLimiting(): void
    {
        // Multi-dimensional login rate limiter: keyed by (account + IP) with global IP ceiling
        RateLimiter::for('login', function (Request $request) {
            $account = Str::lower(trim((string) ($request->input('login') ?? $request->input('email') ?? $request->input('username') ?? '')));

            return [
                Limit::perMinute(10)->by($account . '|' . $request->ip())->response(function () {
                    return response()->json([
                        'message' => 'Too many login attempts for this account. Please try again in 1 minute.',
                    ], 429);
                }),
                Limit::perMinute(60)->by($request->ip()),
            ];
        });

        // Registration rate limiter
        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())->response(function () {
                return response()->json([
                    'message' => 'Too many registration requests. Please try again later.',
                ], 429);
            });
        });

        // Token refresh rate limiter
        RateLimiter::for('refresh', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip())->response(function () {
                return response()->json([
                    'message' => 'Too many token refresh requests.',
                ], 429);
            });
        });

        // Password reset OTP generation: keyed by (email + IP)
        RateLimiter::for('otp_send', function (Request $request) {
            $email = Str::lower(trim((string) $request->input('email', '')));

            return [
                Limit::perMinute(5)->by($email . '|' . $request->ip())->response(function () {
                    return response()->json([
                        'message' => 'Too many password reset requests for this email. Please try again later.',
                    ], 429);
                }),
                Limit::perMinute(30)->by($request->ip()),
            ];
        });

        // OTP verification attempts: keyed by (email + IP)
        RateLimiter::for('otp_verify', function (Request $request) {
            $email = Str::lower(trim((string) $request->input('email', '')));

            return Limit::perMinute(10)->by($email . '|' . $request->ip())->response(function () {
                return response()->json([
                    'message' => 'Too many verification attempts. Please try again later.',
                ], 429);
            });
        });

        // Password reset submission: keyed by (email + IP)
        RateLimiter::for('otp_reset', function (Request $request) {
            $email = Str::lower(trim((string) $request->input('email', '')));

            return Limit::perMinute(5)->by($email . '|' . $request->ip())->response(function () {
                return response()->json([
                    'message' => 'Too many password reset submissions. Please try again later.',
                ], 429);
            });
        });
    }
}
