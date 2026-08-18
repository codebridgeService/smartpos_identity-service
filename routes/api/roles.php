<?php

use App\Http\Controllers\Api\RoleController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Roles
|--------------------------------------------------------------------------
*/

Route::get(
    '/roles',
    [RoleController::class, 'index']
)->middleware(
    'permission:roles.view'
);


Route::get(
    '/roles/{role}',
    [RoleController::class, 'show']
)->middleware(
    'permission:roles.view'
);


Route::post(
    '/roles/provision',
    [RoleController::class, 'provision']
)->middleware(
    'permission:roles.create'
);


Route::post(
    '/roles',
    [RoleController::class, 'store']
)->middleware(
    'permission:roles.create'
);


Route::put(
    '/roles/{role}',
    [RoleController::class, 'update']
)->middleware(
    'permission:roles.update'
);


Route::delete(
    '/roles/{role}',
    [RoleController::class, 'destroy']
)->middleware(
    'permission:roles.delete'
);


Route::post(
    '/roles/{role}/permissions',
    [RoleController::class, 'syncPermissions']
)->middleware(
    'permission:roles.update'
);


Route::post(
    '/roles/{role}/permissions/all',
    [RoleController::class, 'syncAllPermissions']
)->middleware(
    'permission:roles.update'
);
