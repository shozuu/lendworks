<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Rental;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'totalUsers' => User::count(),
            'totalListings' => Listing::count(),
            'pendingListings' => Listing::where('approved', false)->count(),
            'pendingPayments' => Rental::where('payment_status', 'pending')->count()
        ];

        $revenueStats = [
            'monthly' => Rental::where('payment_status', 'released')
                ->whereMonth('payment_released_at', Carbon::now()->month)
                ->sum('service_fee')
        ];

        $pendingListings = Listing::with(['user', 'images', 'category'])
            ->where('approved', false)
            ->latest()
            ->take(5)
            ->get();

        $recentPayments = Rental::with(['listing', 'renter'])
            ->where('payment_status', 'pending')
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'revenueStats' => $revenueStats,
            'pendingListings' => $pendingListings,
            'recentPayments' => $recentPayments
        ]);
    }

    public function listings()
    {
        $listings = Listing::with(['user', 'images', 'category'])
            ->where('approved', false)
            ->latest()
            ->get();

        return Inertia::render('Admin/Listings', [
            'listings' => $listings
        ]);
    }

    public function approveListing(Listing $listing)
    {
        $listing->update(['approved' => true]);
        return back()->with('success', 'Listing approved successfully');
    }

    public function rejectListing(Listing $listing)
    {
        $listing->update(['approved' => false]);
        return back()->with('success', 'Listing rejected');
    }

    public function payments()
    {
        $rentals = Rental::where('payment_status', 'pending')
            ->with(['listing', 'renter'])
            ->latest()
            ->get();
            
        return Inertia::render('Admin/Payments', [
            'rentals' => $rentals
        ]);
    }

    public function verifyPayment(Rental $rental)
    {
        try {
            $rental->verifyPayment();
            return back()->with('success', 'Payment verified successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Failed to verify payment']);
        }
    }

    public function rejectPayment(Rental $rental)
    {
        try {
            $rental->rejectPayment();
            return back()->with('success', 'Payment rejected successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Failed to reject payment']);
        }
    }

    public function releasePayment(Rental $rental)
    {
        try {
            $rental->releasePayment();
            return back()->with('success', 'Payment released to lender successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Failed to release payment']);
        }
    }
}