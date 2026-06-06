<?php

use App\Http\Controllers\CropTemplateController;
use App\Http\Controllers\CycleController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\MovementController;
use App\Http\Controllers\MyFieldController;
use App\Http\Controllers\TaskController;
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

    // Barang (items) di dalam gudang milik user — owner-scoped lewat gudang.
    Route::get('/warehouses/{id}/items', [ItemController::class, 'index']);
    Route::post('/warehouses/{id}/items', [ItemController::class, 'store']);
    Route::put('/items/{id}', [ItemController::class, 'update']);
    Route::delete('/items/{id}', [ItemController::class, 'destroy']);

    // Transaksi stok (movements) masuk/keluar pada item gudang milik user.
    Route::get('/warehouses/{id}/movements', [MovementController::class, 'index']);
    Route::post('/movements', [MovementController::class, 'store']);

    // Tindakan Hari Ini (computed) — owner-scoped.
    Route::get('/tasks', [TaskController::class, 'index']);

    // Endpoint bawaan (kompatibilitas) — user yang sedang login.
    Route::get('/user', fn (Request $request) => $request->user());
});
