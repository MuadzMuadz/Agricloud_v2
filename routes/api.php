<?php

use App\Http\Controllers\CropTemplateController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// --- Publik ---
Route::get('/crop-templates', [CropTemplateController::class, 'index']);

// --- Terproteksi (Bearer / Sanctum) ---
Route::middleware('auth:sanctum')->group(function () {
    // Endpoint bawaan (kompatibilitas) — user yang sedang login.
    Route::get('/user', fn (Request $request) => $request->user());
});
