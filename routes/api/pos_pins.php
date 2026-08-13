<?php

use App\Http\Controllers\Api\UserPosPinController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| POS PIN Routes
|--------------------------------------------------------------------------
*/

Route::prefix('users/{user:uuid}/pos-pin')->group(function () {

    // Create / update cashier PIN
    Route::put(
        '/',
        [UserPosPinController::class, 'update']
    )->middleware('permission:pos_pin.update');

    // Verify cashier PIN
    Route::post(
        '/verify',
        [UserPosPinController::class, 'verify']
    );
});