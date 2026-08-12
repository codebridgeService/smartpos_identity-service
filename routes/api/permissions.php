<?php

use App\Http\Controllers\Api\PermissionController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Permissions
|--------------------------------------------------------------------------
*/

Route::get(
    '/permissions',
    [PermissionController::class, 'index']
)->middleware(
    'permission:permissions.view'
);


Route::get(
    '/permissions/{permission}',
    [PermissionController::class, 'show']
)->middleware(
    'permission:permissions.view'
);


Route::post(
    '/permissions',
    [PermissionController::class, 'store']
)->middleware(
    'permission:permissions.create'
);


Route::put(
    '/permissions/{permission}',
    [PermissionController::class, 'update']
)->middleware(
    'permission:permissions.update'
);


Route::delete(
    '/permissions/{permission}',
    [PermissionController::class, 'destroy']
)->middleware(
    'permission:permissions.delete'
);