<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
public function show(User $user)
{
    // Load the user with their profile
    $user->load('profile');
    
    // Get only active and approved listings WITH IMAGES
    $listings = $user->listings()
        ->with(['images', 'location']) // Add this line to eager load relationships
        ->where('status', 'approved')
        ->latest()
        ->get();
        
    return Inertia::render('Listing/UserInfo', [ 
        'user' => $user,
        'profile' => $user->profile,
        'listings' => $listings
    ]);
}
}