<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ExploreController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MyRentalsController;
use App\Http\Controllers\MyListingsController;
use App\Http\Controllers\RentalController;
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
    Route::patch('/my-listings/{listing}/availability', [MyListingsController::class, 'toggleAvailability'])
         ->name('my-listings.toggle-availability');

    // listing crud 
    Route::resource('listing', ListingController::class)->except(['index', 'show'])->middleware('verified');

    // rental
    Route::post('/rentals', [RentalController::class, 'store'])->name('rentals.store');
    Route::patch('/rentals/{rental}/approve', [RentalController::class, 'approve'])->name('rentals.approve');
    Route::patch('/rentals/{rental}/decline', [RentalController::class, 'decline'])->name('rentals.decline');
    Route::post('/rentals/{rental}/start', [RentalController::class, 'startRental'])->name('rentals.start');
    Route::post('/rentals/{rental}/handover', [RentalController::class, 'handover'])->name('rentals.handover');
    Route::post('/rentals/{rental}/return', [RentalController::class, 'initiateReturn'])->name('rentals.return');
    Route::post('/rentals/{rental}/complete', [RentalController::class, 'completeRental'])->name('rentals.complete');
    Route::patch('/rentals/{rental}/cancel', [RentalController::class, 'cancel'])->name('rentals.cancel');
    Route::patch('/rentals/{rental}/pay', [RentalController::class, 'markAsPaid'])->name('rentals.pay');
    Route::patch('/rentals/{rental}/reject', [RentalController::class, 'reject'])->name('rentals.reject');
});

// payment
Route::middleware(['auth'])->group(function() {
    Route::get('/rentals/{rental}/pay', [RentalController::class, 'showPaymentPage'])->name('rentals.payment');
    Route::post('/rentals/{rental}/submit-payment', [RentalController::class, 'submitPayment'])->name('rentals.submit-payment');
});

// Admin routes with auth and admin middleware
Route::middleware([AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function() {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/listings', [AdminController::class, 'listings'])->name('listings');
    Route::get('/payments', [AdminController::class, 'payments'])->name('payments');
    
    // Listing management
    Route::patch('/listings/{listing}/approve', [AdminController::class, 'approveListing'])->name('listings.approve');
    Route::patch('/listings/{listing}/reject', [AdminController::class, 'rejectListing'])->name('listings.reject');
    
    // Payment management
    Route::patch('/payments/{rental}/verify', [AdminController::class, 'verifyPayment'])->name('payments.verify');
    Route::patch('/payments/{rental}/reject', [AdminController::class, 'rejectPayment'])->name('payments.reject');
    Route::patch('/payments/{rental}/release', [AdminController::class, 'releasePayment'])->name('payments.release');
});

Route::get('/', [ListingController::class, 'index'])->name('home');
Route::get('listing/{listing}', [ListingController::class, 'show'])->name('listing.show');
Route::get('/explore', [ExploreController::class, 'index'])->name('explore');
 
require __DIR__ . '/auth.php';
