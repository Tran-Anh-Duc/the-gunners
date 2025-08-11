<?php
use App\Http\Controllers\Api\AuthController;







Route::prefix('auth')->group(function () {

    // Routes không cần token:
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

    // Routes cần token mới truy cập được:
    Route::middleware('jwt')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});
