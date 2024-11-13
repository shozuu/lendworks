<?php

use App\Http\Controllers\Auth\AuthenticateController;
use App\Http\Controllers\Auth\ConfirmPasswordController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
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

    // by using password.confirm as middleware, we enable the password confirmation routes that confirm user's password before accessing a page/view
    // notice can be triggered if the user tries to access other features of the website. this logic can also be used to fully verify user in the future (other credentials/documents)

    // email verification 
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'handler'])->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])->middleware('throttle:6,1')->name('verification.send');

    // password confirmation
    Route::get('/confirm-password', [ConfirmPasswordController::class, 'create'])->name('password.confirm');
    Route::post('/confirm-password', [ConfirmPasswordController::class, 'store'])->middleware('throttle:6,1')->name('password.confirm');
});