<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Home')->name('home');
Route::inertia('/explore', 'Explore')->name('explore');
Route::inertia('/my-rentals', 'MyRentals')->name('my-rentals');

require __DIR__ . '/auth.php';