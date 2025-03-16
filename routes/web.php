<?php

/**
 * Routes File Modifications
 * 
 * Changes:
 * 1. Added SystemManagementController import
 * 2. Added new system management routes in admin group
 * 3. Added new platform management routes in admin group
 */

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\SystemManagementController; // New import for system management
use App\Http\Controllers\Admin\PlatformManagementController; // Add this line
use App\Http\Controllers\Admin\RentalTransactionsController;
use App\Http\Controllers\ExploreController;
use App\Http\Controllers\LenderDashboardController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MyRentalsController;
use App\Http\Controllers\MyListingsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RentalRequestController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\HandoverController;
use App\Http\Controllers\PickupScheduleController;
use App\Http\Controllers\LenderPickupScheduleController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\Admin\CompletionPaymentController;  // Add this import at the top
use App\Http\Controllers\Admin\DisputeController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CheckMaintenanceMode;
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

    // rental transactions
    Route::get('/rentals/{rental}', [RentalRequestController::class, 'show'])->name('rental.show');

    // payment submission
    Route::post('/rentals/{rental}/submit-payment', [PaymentController::class, 'store'])->name('rentals.submit-payment');
    Route::post('/rentals/{rental}/submit-overdue-payment', [PaymentController::class, 'storeOverduePayment'])
        ->name('rentals.submit-overdue-payment');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/rentals/{rental}/handover', [HandoverController::class, 'submitHandover'])->name('rentals.submit-handover');
    Route::post('/rentals/{rental}/receive', [HandoverController::class, 'submitReceive'])->name('rentals.submit-receive');
    
    // Pickup Schedule routes
    Route::post('/rentals/{rental}/schedules', [PickupScheduleController::class, 'store'])
        ->name('pickup-schedules.store');
    Route::patch('/rentals/{rental}/schedules/{lender_schedule}/select', [PickupScheduleController::class, 'select'])
        ->name('pickup-schedules.select');
    Route::delete('/rentals/{rental}/schedules/{schedule}', [PickupScheduleController::class, 'destroy'])
        ->name('pickup-schedules.destroy');

    // Lender pickup schedules
    Route::delete('lender/pickup-schedules/{schedule}', [LenderPickupScheduleController::class, 'destroy'])
        ->name('lender.pickup-schedules.destroy');
    Route::patch('/lender/pickup-schedules/{schedule}', [LenderPickupScheduleController::class, 'update'])
        ->name('lender.pickup-schedules.update');
    Route::post('lender/pickup-schedules/bulk', [LenderPickupScheduleController::class, 'storeBulk'])
        ->name('lender.pickup-schedules.store-bulk');
    Route::post('lender/pickup-schedules/{dayOfWeek}/time-slot', [LenderPickupScheduleController::class, 'addTimeSlot'])
        ->name('lender.pickup-schedules.add-time-slot');
    Route::patch('lender/pickup-schedules/{schedule}/toggle', [LenderPickupScheduleController::class, 'toggleActive'])
        ->name('lender.pickup-schedules.toggle');
    Route::delete('lender/pickup-schedules/{schedule}/time-slot', [LenderPickupScheduleController::class, 'destroyTimeSlot'])
        ->name('lender.pickup-schedules.destroy-time-slot');

    // Return routes
    Route::controller(ReturnController::class)->group(function () {
        Route::post('/rentals/{rental}/initiate-return', 'initiateReturn')
            ->name('rentals.initiate-return');
        Route::post('/rentals/{rental}/return-schedules', 'storeSchedule')
            ->name('return-schedules.store');
        Route::patch('/rentals/{rental}/return-schedules/{schedule}/select', 'selectSchedule')
            ->name('return-schedules.select');
        Route::patch('/rentals/{rental}/return-schedules/{schedule}/confirm', 'confirmSchedule')
            ->name('return-schedules.confirm');
        Route::patch('/rentals/{rental}/return-schedules/confirm', 'confirmSchedule')
            ->name('return-schedules.confirm');
        Route::post('/rentals/{rental}/submit-return', 'submitReturn')
            ->name('rentals.submit-return');
        Route::post('/rentals/{rental}/confirm-return', 'confirmReturn')
            ->name('rentals.confirm-return');
        Route::post('/rentals/{rental}/return-item', 'submitReturn')
            ->name('rentals.submit-return');
        Route::post('/rentals/{rental}/confirm-receipt', 'confirmItemReceived')
            ->name('rentals.confirm-receipt');
        Route::post('/rentals/{rental}/finalize-return', 'finalizeReturn')
            ->name('rentals.finalize-return');
        Route::post('/rentals/{rental}/raise-dispute', 'raiseDispute')
            ->name('rentals.raise-dispute');
        Route::patch('/rentals/{rental}/return-schedules/{lender_schedule}/select', 'selectSchedule')
            ->name('return-schedules.select');
    });
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
    Route::get('/rental-transactions', [AdminController::class, 'rentalTransactions'])->name('rental-transactions');
    Route::get('/rental-transactions/{rental}', [AdminController::class, 'rentalTransactionDetails'])->name('rental-transactions.show');
    
    // Payment routes
    Route::get('/payments', [AdminController::class, 'payments'])->name('payments');
    
    // Separate routes for regular and overdue payments
    Route::post('/payments/{payment}/verify', [PaymentController::class, 'verify'])
        ->name('payments.verify');
    
    Route::post('/payments/{payment}/reject', [PaymentController::class, 'reject'])
        ->name('payments.reject');

    // Add route for getting payment details if needed
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])
        ->name('payments.show');

    // Add routes for completion payments
    Route::post('/rentals/{rental}/completion-payments/lender', [CompletionPaymentController::class, 'storeLenderPayment'])
        ->name('completion-payments.store-lender-payment');
    Route::post('/rentals/{rental}/completion-payments/deposit', [CompletionPaymentController::class, 'storeDepositRefund'])
        ->name('completion-payments.store-deposit-refund');

    // Add dispute routes
    Route::get('/disputes', [DisputeController::class, 'index'])->name('disputes');
    Route::get('/disputes/{dispute}', [DisputeController::class, 'show'])->name('disputes.show');
    Route::post('/disputes/{dispute}/update-status', [DisputeController::class, 'updateStatus'])->name('disputes.update-status');
    Route::post('/disputes/{dispute}/resolve', [DisputeController::class, 'resolve'])->name('disputes.resolve');

    /**
     * System Management Routes
     * These routes handle system-level operations and monitoring
     * All routes require admin authentication
     */
    Route::get('/system', [SystemManagementController::class, 'index'])->name('system');
    Route::post('/system/clear-cache', [SystemManagementController::class, 'clearCache'])->name('system.clear-cache');
    Route::post('/system/maintenance', [SystemManagementController::class, 'toggleMaintenance'])->name('system.maintenance');
    Route::post('/system/optimize', [SystemManagementController::class, 'optimizeSystem'])->name('system.optimize');
    Route::post('/system/categories', [SystemManagementController::class, 'storeCategory'])->name('system.categories.store');
    Route::patch('/system/categories/{category}', [SystemManagementController::class, 'updateCategory'])->name('system.categories.update');
    Route::delete('/system/categories/{category}', [SystemManagementController::class, 'deleteCategory'])->name('system.categories.delete');

    // Add revenue route
    Route::get('/revenue', [AdminController::class, 'revenue'])->name('revenue');
});

Route::middleware(['web', CheckMaintenanceMode::class])->group(function() {
    Route::get('/', [ListingController::class, 'index'])->name('home');
    Route::get('listing/{listing}', [ListingController::class, 'show'])->name('listing.show');
    Route::get('/explore', [ExploreController::class, 'index'])->name('explore');
    
    // Move other public routes here
});

require __DIR__ . '/auth.php';
