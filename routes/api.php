<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    LandController,
    CropController,
    CycleController,
    WarehouseController
};

Route::apiResource('lands', LandController::class);
Route::apiResource('crops', CropController::class);
Route::apiResource('cycles', CycleController::class);
Route::apiResource('warehouses', WarehouseController::class);
