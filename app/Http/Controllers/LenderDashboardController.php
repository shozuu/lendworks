<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\RentalCancellationReason;
use App\Models\RentalRejectionReason;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LenderDashboardController extends Controller
{
    public function index()
    {
        $listings = Listing::where('user_id', Auth::id())
            ->with([
                'rentalRequests',
                'rentalRequests.renter',
                'rentalRequests.latestRejection.rejectionReason',
                'rentalRequests.latestCancellation.cancellationReason',
                'rentalRequests.payment_request',
                'images',
                'category',
                'location'
            ])
            ->get();

        // Group listings by rental request status
        $groupedListings = [
            'pending' => $listings->flatMap(function($listing) {
                return $listing->rentalRequests
                    ->where('status', 'pending')
                    ->sortBy('created_at') // First requested first
                    ->map(fn($request) => ['listing' => $listing, 'rental_request' => $request]);
            })->values(),
            'approved' => $listings->flatMap(function($listing) {
                return $listing->rentalRequests
                    ->filter(fn($req) => $req->status === 'approved' && !$req->payment_request)
                    ->sortBy('created_at') // First approved first
                    ->map(fn($request) => ['listing' => $listing, 'rental_request' => $request]);
            })->values(),
            'to_handover' => $listings->flatMap(function($listing) {
                return $listing->rentalRequests
                    ->where('status', 'to_handover')
                    ->sortBy('created_at') // First payment verified first
                    ->map(fn($request) => ['listing' => $listing, 'rental_request' => $request]);
            })->values(),
            'payments' => $listings->flatMap(function($listing) {
                return $listing->rentalRequests
                    ->filter(fn($req) => 
                        $req->status === 'approved' && 
                        $req->payment_request && 
                        in_array($req->status_for_display, ['payment_pending', 'payment_rejected'])
                    )
                    ->sortBy('created_at') // First payment submitted first
                    ->map(fn($request) => ['listing' => $listing, 'rental_request' => $request]);
            })->values(),
            'active' => $listings->flatMap(function($listing) {
                return $listing->rentalRequests
                    ->filter(fn($req) => $req->status === 'active' && !$req->return_at)
                    ->sortBy('created_at') // First activated first
                    ->map(fn($request) => ['listing' => $listing, 'rental_request' => $request]);
            })->values(),
            'pending_returns' => $listings->flatMap(function($listing) {
                return $listing->rentalRequests
                    ->filter(fn($req) => $req->status === 'active' && $req->return_at)
                    ->sortBy('created_at') // First return request first
                    ->map(fn($request) => ['listing' => $listing, 'rental_request' => $request]);
            })->values(),
            'completed' => $listings->flatMap(function($listing) {
                return $listing->rentalRequests
                    ->where('status', 'completed')
                    ->sortByDesc('updated_at') // Most recently completed first
                    ->map(fn($request) => ['listing' => $listing, 'rental_request' => $request]);
            })->values(),
            'rejected' => $listings->flatMap(function($listing) {
                return $listing->rentalRequests
                    ->where('status', 'rejected')
                    ->sortByDesc('updated_at') // Most recently rejected first
                    ->map(fn($request) => ['listing' => $listing, 'rental_request' => $request]);
            })->values(),
            'cancelled' => $listings->flatMap(function($listing) {
                return $listing->rentalRequests
                    ->where('status', 'cancelled')
                    ->sortByDesc('updated_at') // Most recently cancelled first
                    ->map(fn($request) => ['listing' => $listing, 'rental_request' => $request]);
            })->values(),
        ];

        $rentalStats = [
            'pending' => $listings->flatMap->rentalRequests->where('status', 'pending')->count(),
            'approved' => $listings->flatMap->rentalRequests
                ->filter(fn($req) => $req->status === 'approved' && !$req->payment_request)
                ->count(),
            'payments' => $listings->flatMap->rentalRequests
                ->whereIn('status_for_display', ['payment_pending', 'payment_rejected'])
                ->count(),
            'to_handover' => $listings->flatMap->rentalRequests->where('status', 'to_handover')->count(),
            'active' => $listings->flatMap->rentalRequests
                ->filter(fn($req) => $req->status === 'active' && !$req->return_at)
                ->count(),
            'pending_returns' => $listings->flatMap->rentalRequests
                ->filter(fn($req) => $req->status === 'active' && $req->return_at)
                ->count(),
            'completed' => $listings->flatMap->rentalRequests->where('status', 'completed')->count(),
            'rejected' => $listings->flatMap->rentalRequests->where('status', 'rejected')->count(),
            'cancelled' => $listings->flatMap->rentalRequests->where('status', 'cancelled')->count(),
        ];

        $rejectionReasons = RentalRejectionReason::select('id', 'label', 'code', 'description')
            ->get()
            ->map(fn($reason) => [
                'value' => (string) $reason->id,
                'label' => $reason->label,
                'code' => $reason->code,
                'description' => $reason->description
            ])
            ->values()
            ->all();

        $cancellationReasons = RentalCancellationReason::select('id', 'label', 'code', 'description')
            ->whereIn('role', ['lender', 'both'])
            ->get()
            ->map(fn($reason) => [
                'value' => (string) $reason->id,
                'label' => $reason->label,
                'code' => $reason->code,
                'description' => $reason->description
            ])
            ->values()
            ->all();

        return Inertia::render('LenderDashboard/LenderDashboard', [
            'groupedListings' => $groupedListings,
            'rentalStats' => $rentalStats,
            'rejectionReasons' => $rejectionReasons,
            'cancellationReasons' => $cancellationReasons
        ]);
    }
}