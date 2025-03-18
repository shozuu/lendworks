<?php

use App\Http\Controllers\Auth\AuthenticateController;
use App\Http\Controllers\Auth\ConfirmPasswordController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\FaceMatchController;
use App\Http\Controllers\VerificationFormController;
use Illuminate\Support\Facades\Route;

// available for guests
Route::middleware('guest')->group(function() {
    // register
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    // login
    Route::get('/login', [AuthenticateController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticateController::class, 'store']);

    // reset password
    Route::get('/forgot-password', [ResetPasswordController::class, 'requestPass'])->name('password.request');
    Route::post('/forgot-password', [ResetPasswordController::class, 'sendEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'resetHandler'])->name('password.update');
});

// available for auth users
Route::middleware('auth')->group(function() {
    // logout
    Route::post('/logout', [AuthenticateController::class, 'destroy'])->name('logout'); 

    // email verification 
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'handler'])->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])->middleware('throttle:6,1')->name('verification.send');

    // ID verification
    Route::get('/verify-id', [FaceMatchController::class, 'show'])->name('verify-id.show');
    Route::post('/verify-id', [FaceMatchController::class, 'matchFace'])->name('verify-id.match');
    Route::post('/api/validate-id-type', [FaceMatchController::class, 'validateIdType'])->name('verify-id.validate');
    Route::get('/api/valid-id-types', [FaceMatchController::class, 'getValidIdTypes'])->name('verify-id.types');
    Route::post('/verify-liveness', [FaceMatchController::class, 'verifyLiveness']);

    // Consolidated password confirmation routes
    Route::prefix('confirm-password')->group(function() {
        Route::get('/', [ConfirmPasswordController::class, 'create'])->name('password.confirm');
        Route::post('/', [ConfirmPasswordController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('password.confirm.store');
    });

    // Profile verification routes
    Route::get('/verification-form', [VerificationFormController::class, 'show'])
        ->name('verification.form');
    Route::get('/verify-id/extracted-data', [VerificationFormController::class, 'extractData'])
        ->name('verification.extracted-data');
    Route::post('/verification-form', [VerificationFormController::class, 'submit'])
        ->name('verification.form.submit');
});