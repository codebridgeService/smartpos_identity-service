<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\LoginAttemptController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserDeviceController;
use App\Http\Controllers\Api\UserPosPinController;
use App\Http\Controllers\Api\UserRoleController;
use App\Http\Controllers\Api\UserSessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public authentication
    |--------------------------------------------------------------------------
    */

    Route::prefix('auth')->group(function () {

        Route::post(
            '/login',
            [AuthController::class, 'login']
        );

        Route::post(
            '/refresh',
            [AuthController::class, 'refresh']
        );

        Route::post(
            '/forgot-password',
            [
                ForgotPasswordController::class,
                'requestCode'
            ]
        );

        Route::post(
            '/verify-reset-code',
            [
                ForgotPasswordController::class,
                'verifyCode'
            ]
        );

        Route::post(
            '/reset-password',
            [
                ForgotPasswordController::class,
                'resetPassword'
            ]
        );
    });


    /*
    |--------------------------------------------------------------------------
    | Protected JWT routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:api')
        ->group(function () {

            Route::get(
                '/auth/me',
                [AuthController::class, 'me']
            );

            Route::post(
                '/auth/logout',
                [AuthController::class, 'logout']
            );


            /*
            |--------------------------------------------------------------------------
            | Users
            |--------------------------------------------------------------------------
            */

            Route::apiResource(
                'users',
                UserController::class
            );


            /*
            |--------------------------------------------------------------------------
            | Roles
            |--------------------------------------------------------------------------
            */

            Route::apiResource(
                'roles',
                RoleController::class
            );

            Route::put(
                '/roles/{role}/permissions',
                [
                    RoleController::class,
                    'syncPermissions'
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | Permissions
            |--------------------------------------------------------------------------
            */

            Route::apiResource(
                'permissions',
                PermissionController::class
            );


            /*
            |--------------------------------------------------------------------------
            | User Role
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/users/{user}/roles',
                [
                    UserRoleController::class,
                    'store'
                ]
            );

            Route::delete(
                '/users/{user}/roles/{role}',
                [
                    UserRoleController::class,
                    'destroy'
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | POS PIN
            |--------------------------------------------------------------------------
            */

            Route::put(
                '/users/{user}/pos-pin',
                [
                    UserPosPinController::class,
                    'update'
                ]
            );

            Route::post(
                '/users/{user}/pos-pin/verify',
                [
                    UserPosPinController::class,
                    'verify'
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | Devices
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/devices',
                [
                    UserDeviceController::class,
                    'index'
                ]
            );

            Route::patch(
                '/devices/{userDevice}/trust',
                [
                    UserDeviceController::class,
                    'trust'
                ]
            );

            Route::patch(
                '/devices/{userDevice}/block',
                [
                    UserDeviceController::class,
                    'block'
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | Sessions
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/sessions',
                [
                    UserSessionController::class,
                    'index'
                ]
            );

            Route::delete(
                '/sessions/{userSession}',
                [
                    UserSessionController::class,
                    'destroy'
                ]
            );

            Route::delete(
                '/sessions',
                [
                    UserSessionController::class,
                    'destroyAll'
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | Login attempts
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/login-attempts',
                [
                    LoginAttemptController::class,
                    'index'
                ]
            );
        });
});