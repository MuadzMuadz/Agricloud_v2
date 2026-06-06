<?php

use App\Http\Controllers\CropTemplateController;
use App\Http\Controllers\CycleController;
use App\Http\Controllers\MyFieldController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// --- Publik ---
Route::get('/crop-templates', [CropTemplateController::class, 'index']);

// --- Terproteksi (Bearer / Sanctum) ---
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/myfields', [MyFieldController::class, 'index']);
    Route::post('/myfields', [MyFieldController::class, 'store']);
    Route::get('/myfields/{id}', [MyFieldController::class, 'show']);
    Route::put('/myfields/{id}', [MyFieldController::class, 'update']); // POST + _method=PUT (multipart) didukung otomatis
    Route::delete('/myfields/{id}', [MyFieldController::class, 'destroy']);

    // Siklus tanam (cycles) pada lahan milik user.
    Route::get('/cycles', [CycleController::class, 'index']);
    Route::post('/cycles', [CycleController::class, 'store']);

    // Gudang (warehouses) milik user — owner-scoped.
    Route::get('/warehouses', [WarehouseController::class, 'index']);
    Route::get('/warehouses/{id}', [WarehouseController::class, 'show']);
    Route::post('/warehouses', [WarehouseController::class, 'store']);
    Route::put('/warehouses/{id}', [WarehouseController::class, 'update']);
    Route::delete('/warehouses/{id}', [WarehouseController::class, 'destroy']);

    // Endpoint bawaan (kompatibilitas) — user yang sedang login.
    Route::get('/user', fn (Request $request) => $request->user());
});
