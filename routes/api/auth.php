<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ForgotPasswordController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/login',
        [AuthController::class, 'login']
    )->middleware(
        'throttle:login'
    )->name('login');


    Route::post(
        '/register',
        [AuthController::class, 'register']
    )->middleware(
        'throttle:register'
    );


    Route::post(
        '/refresh',
        [AuthController::class, 'refresh']
    )->middleware(
        'throttle:refresh'
    );


    /*
    |--------------------------------------------------------------------------
    | Forgot Password
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/forgot-password/send-code',
        [ForgotPasswordController::class, 'sendCode']
    )->middleware(
        'throttle:otp_send'
    );


    Route::post(
        '/verify-reset-code',
        [ForgotPasswordController::class, 'verifyCode']
    )->middleware(
        'throttle:otp_verify'
    );


    Route::post(
        '/reset-password',
        [ForgotPasswordController::class, 'resetPassword']
    )->middleware(
        'throttle:otp_reset'
    );


    /*
    |--------------------------------------------------------------------------
    | Protected Authentication
    |--------------------------------------------------------------------------
    */

    Route::middleware(['auth:api', 'session.active'])->group(function () {

        Route::get(
            '/me',
            [AuthController::class, 'me']
        );


        Route::post(
            '/logout',
            [AuthController::class, 'logout']
        );

    });

});