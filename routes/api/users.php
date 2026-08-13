<?php

use App\Http\Controllers\Api\UserAvatarController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Users
|--------------------------------------------------------------------------
*/

Route::get(
    '/users',
    [UserController::class, 'index']
)->middleware(
    'permission:users.view'
);


Route::get(
    '/users/{user}',
    [UserController::class, 'show']
)->middleware(
    'permission:users.view'
);


Route::post(
    '/users',
    [UserController::class, 'store']
)->middleware(
    'permission:users.create'
);


Route::put(
    '/users/{user}',
    [UserController::class, 'update']
)->middleware(
    'permission:users.update'
);


Route::post(
    '/users/{user}/avatar',
    [UserAvatarController::class, 'upload']
)->middleware(
    'permission:users.update'
);


Route::delete(
    '/users/{user}/avatar',
    [UserAvatarController::class, 'destroy']
)->middleware(
    'permission:users.update'
);


Route::delete(
    '/users/{user}',
    [UserController::class, 'destroy']
)->middleware(
    'permission:users.delete'
);