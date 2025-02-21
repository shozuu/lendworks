<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RentalRequest;
use App\Models\RentalRejectionReason;
use App\Models\RentalCancellationReason;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LenderDashboardController extends Controller
{
    public function index()
    {
        $lender = Auth::user();
        
        // Get rentals where user is the lender
        $rentals = RentalRequest::whereHas('listing', function ($query) use ($lender) {
            $query->where('user_id', $lender->id);
        })->with(['listing.images', 'renter', 'payment_request'])->get();

        // Group rentals by status
        $groupedListings = $rentals->groupBy(function ($rental) {
            // Special handling for payments tab
            if ($rental->status === 'approved' && $rental->payment_request) {
                return 'payments';
            }

            // Special handling for to_handover tab
            if (in_array($rental->status, ['to_handover', 'pending_proof'])) {
                return 'to_handover';
            }
            
            return $rental->status;
        })->map(function ($group) {
            return $group->map(function ($rental) {
                return [
                    'listing' => $rental->listing,
                    'rental_request' => $rental
                ];
            });
        });

  
        $rentalStats = [
            'pending' => $rentals->where('status', 'pending')->count(),
            'approved' => $rentals->where('status', 'approved')
                ->filter(function ($rental) {
                    return !$rental->payment_request;
                })->count(),
            'payments' => $rentals->where('status', 'approved')
                ->filter(function ($rental) {
                    return $rental->payment_request !== null;
                })->count(),
            'to_handover' => $rentals->whereIn('status', ['to_handover', 'pending_proof'])->count(),
            'active' => $rentals->where('status', 'active')->count(),
            'completed' => $rentals->where('status', 'completed')->count(),
            'rejected' => $rentals->where('status', 'rejected')->count(),
            'cancelled' => $rentals->where('status', 'cancelled')->count(),
        ];

        return Inertia::render('LenderDashboard/LenderDashboard', [
            'groupedListings' => $groupedListings,
            'rentalStats' => $rentalStats,
            'rejectionReasons' => RentalRejectionReason::all(),
            'cancellationReasons' => RentalCancellationReason::whereIn('role', ['lender', 'both'])->get(),
        ]);
    }
}