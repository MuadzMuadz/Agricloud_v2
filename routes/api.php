<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    LandController,
    CycleController,
    PhaseController,
    WarehouseController,
    ItemController,
    MovementController,
    NeedController,
    FieldController
};
use App\Http\Controllers\Admin\{
    UserController,
    CropController,
    StageController,
    AdminCycleController,
    AdminPhaseController,
    AdminWarehouseController,
    AdminItemController,
    AdminMovementController,
    AdminNeedController
};

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/password', [AuthController::class, 'changePassword']);
        Route::delete('/delete', [AuthController::class, 'deleteAccount']);
    });

    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::patch('users/{id}/role', [UserController::class, 'updateRole']);
        Route::get('roles', [UserController::class, 'roles']);
    });
});

/*
|--------------------------------------------------------------------------
| FIELD MODULE
|--------------------------------------------------------------------------
*/
Route::prefix('fields')->group(function () {
    Route::get('/', [FieldController::class, 'index']);
    Route::get('/search', [FieldController::class, 'search']);
    Route::get('/stats', [FieldController::class, 'stats']);
    Route::get('/farmer/{farmer_id}', [FieldController::class, 'byFarmer']);
    Route::post('/validate-location', [FieldController::class, 'validateLocation']);
    Route::get('/{id}', [FieldController::class, 'show']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/', [FieldController::class, 'store']);
        Route::put('/{id}', [FieldController::class, 'update']);
        Route::patch('/{id}/status', [FieldController::class, 'updateStatus']);
        Route::delete('/{id}', [FieldController::class, 'destroy']);
    });
});

/*
|--------------------------------------------------------------------------
| LAND MODULE
|--------------------------------------------------------------------------
*/
Route::prefix('lands')->group(function () {
    Route::get('/', [LandController::class, 'index']);
    Route::get('/{id}', [LandController::class, 'show']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/', [LandController::class, 'store']);
        Route::put('/{id}', [LandController::class, 'update']);
        Route::delete('/{id}', [LandController::class, 'destroy']);
    });
});

/*
|--------------------------------------------------------------------------
| CYCLE MODULE
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->prefix('cycles')->group(function () {
    Route::get('/', [CycleController::class, 'index']);
    Route::get('/{id}', [CycleController::class, 'show']);
    Route::post('/', [CycleController::class, 'store']);
    Route::put('/{id}', [CycleController::class, 'update']);
    Route::delete('/{id}', [CycleController::class, 'destroy']);

    // Nested routes for phases
    Route::get('/{cycle}/phases', [PhaseController::class, 'index']);
    Route::get('/{cycle}/phases/{phase}', [PhaseController::class, 'show']);
    Route::post('/{cycle}/phases', [PhaseController::class, 'store']);
    Route::put('/{cycle}/phases/{phase}', [PhaseController::class, 'update']);
    Route::delete('/{cycle}/phases/{phase}', [PhaseController::class, 'destroy']);
});

Route::middleware(['auth:sanctum'])->get('lands/{landId}/cycles', [CycleController::class, 'listByLand']);

/*
|--------------------------------------------------------------------------
| ADMIN — CROPS, STAGES, DAN LAINNYA
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::apiResource('crops', CropController::class);
    Route::apiResource('stages', StageController::class);
    Route::apiResource('cycles', AdminCycleController::class)->only(['index', 'show']);
    Route::apiResource('phases', AdminPhaseController::class)->only(['index', 'show']);
    Route::apiResource('warehouses', AdminWarehouseController::class)->only(['index', 'show']);
    Route::apiResource('items', AdminItemController::class)->only(['index', 'show']);
    Route::apiResource('movements', AdminMovementController::class)->only(['index', 'show']);
    Route::apiResource('needs', AdminNeedController::class)->only(['index', 'show']);
});

/*
|--------------------------------------------------------------------------
| FARMER MODULES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:farmer'])->prefix('farmer')->group(function () {
    Route::apiResource('warehouses', WarehouseController::class);
    Route::apiResource('items', ItemController::class);
    Route::apiResource('movements', MovementController::class);
    Route::apiResource('needs', NeedController::class);
});

/*
|--------------------------------------------------------------------------
| USER AUTH CHECK
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    return $request->user();
});
