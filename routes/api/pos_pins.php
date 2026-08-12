<?php

use App\Http\Controllers\Api\UserPosPinController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| POS PIN
|--------------------------------------------------------------------------
*/

Route::put(
    '/users/{user}/pos-pin',
    [UserPosPinController::class, 'update']
)->middleware(
    'permission:pos_pin.update'
);


Route::post(
    '/users/{user}/pos-pin/verify',
    [UserPosPinController::class, 'verify']
);