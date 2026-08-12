<?php

use App\Http\Controllers\Api\UserRoleController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| User Role Assignments
|--------------------------------------------------------------------------
*/

Route::post(
    '/users/{user}/roles',
    [UserRoleController::class, 'store']
)->middleware(
    'permission:user_roles.assign'
);


Route::delete(
    '/users/{user}/roles/{role}',
    [UserRoleController::class, 'destroy']
)->middleware(
    'permission:user_roles.remove'
);