<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MyListingsController extends Controller
{
    public function index()
    {
        $listings = Listing::where('user_id', Auth::id())
            ->with(['category', 'images', 'location'])
            ->latest()
            ->get();

        // Get listing stats
        $listingStats = [
            'total' => $listings->count(),
            'available' => $listings->where('status', 'approved')
                                    ->where('is_available', true)
                                    ->count(),
            'unavailable' => $listings->where('status', 'approved')
                                    ->where('is_available', false)
                                    ->count(),
            'pending' => $listings->where('status', 'pending')->count(),
            'rejected' => $listings->where('status', 'rejected')->count(),
            'taken_down' => $listings->where('status', 'taken_down')->count()
        ];

        return Inertia::render('MyListings/MyListings', [
            'listings' => $listings,
            'listingStats' => $listingStats
        ]);
    }
}