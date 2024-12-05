<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Inertia\Inertia;
use Illuminate\Http\Request;

class MyListingsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Base query for ALL user's listings without any filters
        $listingsQuery = $user->listings()
            ->with(['images', 'category', 'rentals'])
            ->withCount(['rentals as is_currently_rented' => function ($query) {
                $query->whereIn('rental_status_id', [3, 4]); // paid or active rentals
            }]);

        // Get all listings for stats
        $listings = $listingsQuery->get();

        // Calculate listing stats
        $listingStats = [
            'all' => $listings->count(),
            'available' => $listings->filter(function($listing) {
                return $listing->approved && $listing->is_available && !$listing->is_currently_rented;
            })->count(),
            'rented' => $listings->filter(function($listing) {
                return $listing->is_currently_rented > 0;
            })->count(),
        ];

        // Get rentals for lender's listings
        $rentals = $user->receivedRentals()
            ->with(['listing.images', 'renter', 'status'])
            ->latest()
            ->get();

        // Calculate rental stats
        $rentalStats = [
            'to_approve' => $rentals->where('rental_status_id', 1)->count(), // pending approval
            'to_handover' => $rentals->where('rental_status_id', 3)->count(), // approved and paid
            'active' => $rentals->where('rental_status_id', 4)->count(), // ongoing rentals
            'completed' => $rentals->where('rental_status_id', 5)->count(), // completed rentals
        ];

        return Inertia::render('MyListings/Index', [
            'listings' => $listingsQuery->latest()->paginate(10),
            'rentals' => $user->receivedRentals()
                ->with(['listing.images', 'renter', 'status'])
                ->latest()
                ->paginate(10),
            'rentalStats' => $rentalStats,
            'listingStats' => $listingStats
        ]);
    }

    // Add new method to toggle listing availability
    public function toggleAvailability(Listing $listing)
    {
        try {
            $listing->updateAvailability(!$listing->is_available);
            return back()->with('success', 'Listing availability updated');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}