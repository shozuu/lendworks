<?php

use App\Http\Controllers\ExploreController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// 'verified' and password.confirm middleware can be used to protect routes that need full user verification but for now, i'll put it at my rentals to demonstrate functionality

Route::middleware(['auth'])->group(function() {
    // profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'updateInfo'])->name('profile.info');
    Route::put('/profile', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // my rentals
    Route::inertia('/my-rentals', 'MyRentals')->middleware('verified')->name('my-rentals');

    // listing crud 
    Route::resource('listing', ListingController::class)->except(['index', 'show'])->middleware('verified');
});

Route::get('/', [ListingController::class, 'index'])->name('home');
Route::get('listing/{listing}', [ListingController::class, 'show'])->name('listing.show');
Route::get('/explore', [ExploreController::class, 'index'])->name('explore');
 
require __DIR__ . '/auth.php';
