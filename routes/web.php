<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;

// Route utama untuk halaman depan
Route::get('/', [HomeController::class, 'index'])->name('home');
