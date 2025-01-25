<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\RentalRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class MyListingsController extends Controller
{
    public function index(Request $request)
    {
        $listings = Listing::where('user_id', Auth::id())
            ->with([
                'category', 
                'images', 
                'location',
                'latestRejection.rejectionReason',
                'latestTakedown.takedownReason',
                'rentalRequests' => function($query) {  
                    $query->with(['renter', 'listing'])  // Include listing relationship
                        ->latest();
                }
            ])
            ->latest()
            ->get();

        // Collect all rental requests for the user's listings
        $rentalRequests = $listings->flatMap(function ($listing) {
            return $listing->rentalRequests->map(function ($request) use ($listing) {
                $request->listing = $listing; // Ensure listing is attached
                return $request;
            });
        });

        // Get rental management stats using the collected requests
        $rentalStats = [
            'pending_requests' => $rentalRequests->where('status', 'pending')->count(),
            'to_handover' => $rentalRequests->where('status', 'approved')
                            ->where('handover_at', null)->count(),
            'active_rentals' => $rentalRequests->where('status', 'active')
                            ->where('return_at', null)->count(),
            'pending_returns' => $rentalRequests->where('status', 'active')
                            ->whereNotNull('return_at')->count(),
            'completed' => $rentalRequests->where('status', 'completed')->count()
        ];

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
            'rentalRequests' => $rentalRequests->values(),  // Add this line
            'rentalStats' => $rentalStats,
            'listingStats' => $listingStats
        ]);
    }
}