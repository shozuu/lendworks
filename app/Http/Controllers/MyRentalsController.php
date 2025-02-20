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
                'latestCancellation.cancellationReason',
                'payment_request'
            ])
            ->latest()
            ->get();

        // Group rentals by their display status
        $groupedRentals = $rentals->groupBy('status_for_display')
            ->map(function ($group) {
                return $group->values();
            })
            ->toArray();

        // Stats for renter's view - ensure stats match the actual groups
        $stats = [
            'pending' => $rentals->where('status', 'pending')->count(),
            'approved' => $rentals->where('status', 'approved')
                ->filter(function ($rental) {
                    return !$rental->payment_request;
                })->count(),
            'payments' => $rentals->whereIn('status_for_display', ['payment_pending', 'payment_rejected'])->count(),
            'to_receive' => $rentals->where('status', 'to_handover')->count(),
            'active' => $rentals->where('status', 'active')->count(),
            'completed' => $rentals->where('status', 'completed')->count(),
            'rejected' => $rentals->where('status', 'rejected')->count(),
            'cancelled' => $rentals->where('status', 'cancelled')->count(),
        ];

        // Get only cancellation reasons for renters
        $cancellationReasons = RentalCancellationReason::select('id', 'label', 'code', 'description')
            ->whereIn('role', ['renter', 'both'])
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
            'rentals' => $groupedRentals,
            'stats' => $stats,
            'cancellationReasons' => $cancellationReasons
        ]);
    }
}