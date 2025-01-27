<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LenderDashboardController extends Controller
{
    public function index()
    {
        $listings = Listing::where('user_id', Auth::id())
            ->with(['rentalRequests.renter', 'images'])
            ->get();

            
        // group listings by their rental status
        $groupedListings = [
            'pending_requests' => $listings->filter(fn($listing) => 
                $listing->rentalRequests->contains('status', 'pending')),
            'to_handover' => $listings->filter(fn($listing) => 
                $listing->rentalRequests->contains(fn($req) => 
                    $req->status === 'approved' && !$req->handover_at)),
            'active_rentals' => $listings->filter(fn($listing) => 
                $listing->rentalRequests->contains(fn($req) => 
                    $req->status === 'active' && !$req->return_at)),
            'pending_returns' => $listings->filter(fn($listing) => 
                $listing->rentalRequests->contains(fn($req) => 
                    $req->status === 'active' && $req->return_at)),
            'completed' => $listings->filter(fn($listing) => 
                $listing->rentalRequests->contains('status', 'completed'))
        ];

        // counts how many listings are in each group
        $rentalStats = collect($groupedListings)->map->count();

        return Inertia::render('LenderDashboard/LenderDashboard', [
            'groupedListings' => $groupedListings,
            'rentalStats' => $rentalStats
        ]);
    }
}
