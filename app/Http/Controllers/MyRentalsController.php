<?php

namespace App\Http\Controllers;

use App\Models\RentalRequest;
use App\Models\RentalCancellationReason;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MyRentalsController extends Controller
{
    public function index()
    {
        $renter = Auth::user();
        
        // Get rentals where user is the renter
        $rentals = RentalRequest::where('renter_id', $renter->id)
            ->with(['listing.images', 'listing.user', 'payment_request'])
            ->get();

        // Group rentals by status
        $groupedRentals = $rentals->groupBy(function ($rental) {
            // Special handling for payments tab
            if ($rental->status === 'approved' && $rental->payment_request) {
                return 'payments';
            }

            // Special handling for to_handover tab
            if (in_array($rental->status, ['to_handover', 'pending_proof'])) {
                return 'to_handover';
            }
            
            return $rental->status;
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
            'to_receive' => $rentals->whereIn('status', ['to_handover', 'pending_proof'])->count(),
            'active' => $rentals->where('status', 'active')->count(),
            'completed' => $rentals->where('status', 'completed')->count(),
            'rejected' => $rentals->where('status', 'rejected')->count(),
            'cancelled' => $rentals->where('status', 'cancelled')->count(),
        ];

        return Inertia::render('MyRentals/MyRentals', [
            'groupedRentals' => $groupedRentals,
            'rentalStats' => $rentalStats,
            'cancellationReasons' => RentalCancellationReason::whereIn('role', ['renter', 'both'])->get(),
        ]);
    }
}