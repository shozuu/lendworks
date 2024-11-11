<?php

use Illuminate\Support\Facades\Route;

// 'verified' middleware can be used to protect routes that need full user verification but for now, i'll put it at home to demonstrate functionality
Route::inertia('/', 'Home')->middleware('verified')->name('home');

Route::inertia('/explore', 'Explore')->name('explore');
Route::inertia('/my-rentals', 'MyRentals')->name('my-rentals');

require __DIR__ . '/auth.php';