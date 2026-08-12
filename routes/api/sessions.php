<?php

use App\Http\Controllers\Api\UserSessionController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Sessions
|--------------------------------------------------------------------------
*/

Route::get(
    '/sessions',
    [UserSessionController::class, 'index']
)->middleware(
    'permission:sessions.view'
);


Route::delete(
    '/sessions/{userSession}',
    [UserSessionController::class, 'destroy']
)->middleware(
    'permission:sessions.revoke'
);


Route::delete(
    '/sessions',
    [UserSessionController::class, 'destroyAll']
)->middleware(
    'permission:sessions.revoke'
);