<?php

use App\Http\Controllers\CropTemplateController;
use App\Http\Controllers\MyFieldController;
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

    // Endpoint bawaan (kompatibilitas) — user yang sedang login.
    Route::get('/user', fn (Request $request) => $request->user());
});
