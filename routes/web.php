<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\RentalTransactionsController;
use App\Http\Controllers\ExploreController;
use App\Http\Controllers\LenderDashboardController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MyRentalsController;
use App\Http\Controllers\MyListingsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RentalRequestController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;

// 'verified' and password.confirm middleware can be used to protect routes that need full user verification but for now, i'll put it at my rentals to demonstrate functionality

Route::middleware(['auth', 'verified'])->group(function() {
    // profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'updateInfo'])->name('profile.info');
    Route::put('/profile', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // my rentals 
    Route::get('/my-rentals', [MyRentalsController::class, 'index'])->middleware('verified')->name('my-rentals');

    // my listings
    Route::get('/my-listings', [MyListingsController::class, 'index'])->middleware('verified')->name('my-listings');

    // listing crud 
    Route::resource('listing', ListingController::class)->except(['index', 'show'])->middleware('verified');

    // notification 
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead']);

    // rental request
    Route::post('/rentals', [RentalRequestController::class, 'store'])->name('rentals.store');
    Route::patch('/rental-request/{rentalRequest}/approve', [RentalRequestController::class, 'approve'])
        ->name('rental-request.approve');
    Route::patch('/rental-request/{rentalRequest}/reject', [RentalRequestController::class, 'reject'])
        ->name('rental-request.reject');
    Route::patch('/rental-request/{rentalRequest}/cancel', [RentalRequestController::class, 'cancel'])->name('rental-request.cancel');

    // lender dashboard
    Route::get('/lender-dashboard', [LenderDashboardController::class, 'index'])->name('lender-dashboard');
});

// Admin routes with auth and admin middleware
Route::middleware(['auth', AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function() {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    
    // User management routes
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::patch('/users/{user}/suspend', [AdminController::class, 'suspendUser'])->name('users.suspend');
    Route::patch('/users/{user}/activate', [AdminController::class, 'activateUser'])->name('users.activate');
    Route::get('/users/{user}', [AdminController::class, 'showUser'])->name('users.show');
    
    // Listing management routes
    Route::get('/listings', [AdminController::class, 'listings'])->name('listings');
    Route::get('/listings/{listing}', [AdminController::class, 'showListing'])->name('listings.show');
    Route::patch('/listings/{listing}/approve', [AdminController::class, 'approveListing'])->name('listings.approve');
    Route::patch('/listings/{listing}/reject', [AdminController::class, 'rejectListing'])->name('listings.reject');
    Route::patch('/listings/{listing}/takedown', [AdminController::class, 'takedownListing'])->name('listings.takedown');

    // Rental transactions routes
    Route::get('/rental-transactions', [RentalTransactionsController::class, 'index'])
        ->name('rental-transactions');
    Route::get('/rental-transactions/{rental}', [RentalTransactionsController::class, 'show'])
        ->name('rental-transactions.show');
});

Route::get('/', [ListingController::class, 'index'])->name('home');
Route::get('listing/{listing}', [ListingController::class, 'show'])->name('listing.show');
Route::get('/explore', [ExploreController::class, 'index'])->name('explore');

require __DIR__ . '/auth.php';
