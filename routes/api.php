<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
<<<<<<< HEAD
=======
    AuthController,
>>>>>>> 99373cb737a48033aed895cda11b7d9a2f7ece9b
    LandController,
    CropController,
    CycleController,
    WarehouseController
};
<<<<<<< HEAD

=======
use App\Http\Controllers\Admin\UserController;

Route::prefix('auth')->group(function () {
    // ========== PUBLIC ==========
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // ========== PROTECTED (login required) ==========
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/password', [AuthController::class, 'changePassword']);
        Route::delete('/delete', [AuthController::class, 'deleteAccount']);

        // Token management (optional)
        Route::get('/tokens', [AuthController::class, 'listTokens']);
        Route::delete('/tokens', [AuthController::class, 'revokeAllTokens']);
        Route::delete('/tokens/{id}', [AuthController::class, 'revokeToken']);
    });

    // ========== ADMIN ==========
    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
        Route::patch('/users/{id}/role', [UserController::class, 'updateRole']);
        Route::get('/roles', [UserController::class, 'roles']);
    });
});

>>>>>>> 99373cb737a48033aed895cda11b7d9a2f7ece9b
Route::apiResource('lands', LandController::class);
Route::apiResource('crops', CropController::class);
Route::apiResource('cycles', CycleController::class);
Route::apiResource('warehouses', WarehouseController::class);
