<?php

use App\Http\Controllers\Auth\AuthenticateController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

// available for guests
Route::middleware('guest')->group(function() {
    // register
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    // login
    Route::get('/login', [AuthenticateController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticateController::class, 'store']);
});

// available for auth users
Route::middleware('auth')->group(function() {
    // logout
    Route::post('/logout', [AuthenticateController::class, 'destroy'])->name('logout');
});