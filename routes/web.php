<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ExploreController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MyRentalsController;
use App\Http\Controllers\MyListingsController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// 'verified' and password.confirm middleware can be used to protect routes that need full user verification but for now, i'll put it at my rentals to demonstrate functionality

Route::middleware(['auth', 'verified'])->group(function() {
    // profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'updateInfo'])->name('profile.info');
    Route::put('/profile', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // my rentals & listings
    Route::get('/my-rentals', [MyRentalsController::class, 'index'])->middleware('verified')->name('my-rentals');
    Route::get('/my-listings', [MyListingsController::class, 'index'])->middleware('verified')->name('my-listings');

    // listing crud 
    Route::resource('listing', ListingController::class)->except(['index', 'show'])->middleware('verified');
});

// Admin routes with auth and admin middleware
Route::middleware([AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function() {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
});

Route::get('/', [ListingController::class, 'index'])->name('home');
Route::get('listing/{listing}', [ListingController::class, 'show'])->name('listing.show');
Route::get('/explore', [ExploreController::class, 'index'])->name('explore');
 
require __DIR__ . '/auth.php';
