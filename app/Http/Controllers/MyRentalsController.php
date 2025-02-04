<?php

namespace App\Http\Controllers;

use App\Models\RentalRequest;
use App\Models\RentalCancellationReason;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MyRentalsController extends Controller
{
    public function index(Request $request)
    {
        // Get rentals where user is the renter
        $rentals = RentalRequest::where('renter_id', Auth::id())
            ->with([
                'listing.user', 
                'listing.images',
                'listing.category', 
                'listing.location',
                'latestRejection.rejectionReason',
                'latestCancellation.cancellationReason'
            ])
            ->latest()
            ->get()
            ->groupBy('status');

        // Stats for renter's view
        $stats = [
            'pending' => $rentals->get('pending', collect())->count(),
            'approved' => $rentals->get('approved', collect())->count(),
            'active' => $rentals->get('active', collect())->count(),
            'completed' => $rentals->get('completed', collect())->count(),
            'rejected' => $rentals->get('rejected', collect())->count(),
            'cancelled' => $rentals->get('cancelled', collect())->count(),
        ];

        $cancellationReasons = RentalCancellationReason::select('id', 'label', 'code', 'description')
            ->get()
            ->map(fn($reason) => [
                'value' => (string) $reason->id,
                'label' => $reason->label,
                'code' => $reason->code,
                'description' => $reason->description
            ])
            ->values()
            ->all();

        return Inertia::render('MyRentals/MyRentals', [
            'rentals' => $rentals,
            'stats' => $stats,
            'cancellationReasons' => $cancellationReasons
        ]);
    }
}