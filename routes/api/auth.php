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
        'throttle:10,1'
    )->name('login');


    Route::post(
        '/register',
        [AuthController::class, 'register']
    )->middleware(
        'throttle:5,1'
    );


    Route::post(
        '/refresh',
        [AuthController::class, 'refresh']
    )->middleware(
        'throttle:20,1'
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
        'throttle:5,1'
    );


    Route::post(
        '/verify-reset-code',
        [ForgotPasswordController::class, 'verifyCode']
    )->middleware(
        'throttle:10,1'
    );


    Route::post(
        '/reset-password',
        [ForgotPasswordController::class, 'resetPassword']
    )->middleware(
        'throttle:5,1'
    );


    /*
    |--------------------------------------------------------------------------
    | Protected Authentication
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:api')->group(function () {

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