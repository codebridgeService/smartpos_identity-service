<?php

use App\Http\Controllers\Api\LoginAttemptController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Login Attempts
|--------------------------------------------------------------------------
*/

Route::get(
    '/login-attempts',
    [LoginAttemptController::class, 'index']
)->middleware(
    'permission:login_attempts.view'
);