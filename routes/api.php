<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CropTemplateController;
use App\Http\Controllers\CycleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\MovementController;
use App\Http\Controllers\MyFieldController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WeatherController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// --- Publik ---
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/auth/google', [AuthController::class, 'googleLogin']);
Route::get('/crop-templates', [CropTemplateController::class, 'index']);

// Cuaca — Bearer opsional: tanpa token resolusi via IP/default, dengan token
// ikut setelan & lahan user (lihat WeatherController::resolveLocation).
Route::get('/weather', [WeatherController::class, 'index']);
Route::get('/weather/search', [WeatherController::class, 'search']);

// --- Terproteksi (Bearer / Sanctum) ---
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::put('/auth/user', [AuthController::class, 'update']);
    Route::patch('/auth/settings', [AuthController::class, 'updateSettings']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

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

    // Tindakan Hari Ini (computed) & ringkasan KPI dashboard — owner-scoped.
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

    // Notifikasi in-app (channel database).
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read']);

    // Endpoint bawaan (kompatibilitas) — user yang sedang login.
    Route::get('/user', fn (Request $request) => $request->user());
});
