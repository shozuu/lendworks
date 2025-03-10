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
        $rentals = RentalRequest::where('renter_id', Auth::id())
            ->with(['listing.images', 'listing.user', 'payment_request'])
            ->get();

        $groupedListings = $rentals->groupBy(function ($rental) {
            if ($rental->status === 'active' && $rental->end_date < now()) {
                $hasVerifiedOverduePayment = $rental->payment_request()
                    ->where('type', 'overdue')
                    ->where('status', 'verified')
                    ->exists();

                return $hasVerifiedOverduePayment ? 'paid_overdue' : 'overdue';
            }

            if ($rental->status === 'approved' && $rental->payment_request) {
                return 'payments';
            }

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
            'to_handover' => $rentals->whereIn('status', ['to_handover', 'pending_proof'])->count(),
            'active' => $rentals->where('status', 'active')
                ->filter(function ($rental) {
                    return $rental->end_date >= now();
                })->count(),
            'overdue' => $rentals->where('status', 'active')
                ->filter(function ($rental) {
                    return $rental->end_date < now() && 
                        !$rental->payment_request()
                            ->where('type', 'overdue')
                            ->where('status', 'verified')
                            ->exists();
                })->count(),
            'paid_overdue' => $rentals->where('status', 'active')
                ->filter(function ($rental) {
                    return $rental->end_date < now() && 
                        $rental->payment_request()
                            ->where('type', 'overdue')
                            ->where('status', 'verified')
                            ->exists();
                })->count(),
            'pending_return' => $rentals->where('status', 'pending_return')->count(),
            'return_scheduled' => $rentals->where('status', 'return_scheduled')->count(),
            'pending_return_confirmation' => $rentals->where('status', 'pending_return_confirmation')->count(),
            'completed' => $rentals->where('status', 'completed')->count(),
            'rejected' => $rentals->where('status', 'rejected')->count(),
            'cancelled' => $rentals->where('status', 'cancelled')->count(),
        ];

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

        return Inertia::render('MyRentals', [
            'groupedListings' => $groupedListings,
            'rentalStats' => $rentalStats,
            'cancellationReasons' => $cancellationReasons,
        ]);
    }
}