<?php

use App\Http\Controllers\ExploreController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SuggestedRateController;

Route::get('/', [ListingController::class, 'index'])->name('home');
Route::get('/explore', [ExploreController::class, 'index'])->name('explore');

Route::resource('listing', ListingController::class)->except('index');

Route::get('/api/categories', [CategoryController::class, 'index']);
Route::post('/rates/calculate', [SuggestedRateController::class, 'calculateDailyRate'])->name('rates.calculate');

// 'verified' and password.confirm middleware can be used to protect routes that need full user verification but for now, i'll put it at my rentals to demonstrate functionality

Route::middleware(['auth'])->group(function() {
    // profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');   
    Route::patch('/profile', [ProfileController::class, 'updateInfo'])->name('profile.info');
    Route::put('/profile', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // my rentals
    Route::inertia('/my-rentals', 'MyRentals')->middleware('verified')->name('my-rentals');
});
 
require __DIR__ . '/auth.php';