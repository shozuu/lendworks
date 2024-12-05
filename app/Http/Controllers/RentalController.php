<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Rental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RentalController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'listing_id' => ['required', 'exists:listings,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'discount' => ['required', 'numeric', 'min:0'],
            'service_fee' => ['required', 'numeric', 'min:0'],
            'total_price' => ['required', 'numeric', 'min:0'],
        ]);

        // Check if listing is available
        $listing = Listing::findOrFail($validated['listing_id']);
        
        if (!$listing->canBeRented() || $listing->user_id === $request->user()->id) {
            return back()->withErrors(['message' => 'This listing is not available for rent.']);
        }

        // Create rental request
        $rental = $request->user()->rentals()->create([
            'listing_id' => $validated['listing_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'base_price' => $validated['base_price'],
            'discount' => $validated['discount'],
            'service_fee' => $validated['service_fee'], 
            'total_price' => $validated['total_price'],
            'payment_status' => 'empty',  // Start with empty payment status
            'rental_status_id' => 1, // Set to pending status
        ]);

        return redirect()->route('my-rentals')
            ->with('success', 'Rental request sent successfully.');
    }

    public function approve(Rental $rental)
    {
        if ($rental->listing->user_id !== auth()->id()) {
            return back()->withErrors(['message' => 'Unauthorized action']);
        }

        try {
            $rental->approve();
            return back()->with('success', 'Rental request approved');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function reject(Rental $rental)
    {
        if ($rental->listing->user_id !== auth()->id()) {
            return back()->withErrors(['message' => 'Unauthorized action']);
        }

        try {
            $rental->reject();
            return back()->with('success', 'Rental request rejected');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function decline(Rental $rental)
    {
        if ($rental->listing->user_id !== auth()->id() || !$rental->isPending()) {
            return back()->withErrors(['message' => 'Unauthorized or invalid action']);
        }

        try {
            $rental->reject();
            return back()->with('success', 'Rental request declined');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function startRental(Rental $rental)
    {
        if ($rental->listing->user_id !== auth()->id() || !$rental->isApproved()) {
            return back()->withErrors(['message' => 'Unauthorized or invalid action']);
        }

        $rental->markAsActive();
        return back()->with('success', 'Rental started');
    }

    public function initiateReturn(Rental $rental)
    {
        if (!auth()->user()->can('return', $rental)) {
            return back()->withErrors(['message' => 'Unauthorized action']);
        }

        try {
            $rental->initiateReturn();
            return back()->with('success', 'Return request initiated successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function completeRental(Rental $rental)
    {
        if (!auth()->user()->can('complete', $rental)) {
            return back()->withErrors(['message' => 'Unauthorized action']);
        }

        try {
            DB::transaction(function () use ($rental) {
                $rental->confirmReturn();
                // Optionally trigger payment release process here
                // $rental->releasePayment();
            });
            
            return back()->with('success', 'Return confirmed successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function cancel(Rental $rental)
    {
        if (!auth()->user()->can('cancel', $rental)) {
            return back()->withErrors(['message' => 'Unauthorized action']);
        }

        $rental->markAsCancelled();
        return back()->with('success', 'Rental cancelled');
    }

    public function showPaymentPage(Rental $rental)
    {
        if ($rental->renter_id !== auth()->id() || !$rental->isApproved()) {
            return back()->withErrors(['message' => 'Unauthorized or invalid action']);
        }

        return Inertia::render('Rental/Payment', [
            'rental' => $rental->load(['listing.images', 'listing.user'])
        ]);
    }

    public function submitPayment(Request $request, Rental $rental)
    {
        if ($rental->renter_id !== auth()->id() || !$rental->isApproved()) {
            return back()->withErrors(['message' => 'Unauthorized or invalid action']);
        }

        try {
            $rental->submitPayment();
            return redirect()
                ->route('my-rentals')
                ->with('success', 'Payment submitted successfully. Please wait for verification.');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function showPendingPayments()
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
        if (!auth()->user()->isAdmin()) {
            return back()->withErrors(['message' => 'Unauthorized action']);
        }

        try {
            $rental->verifyPayment();
            return back()->with('success', 'Payment verified successfully');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Failed to verify payment']);
        }
    }

    public function handover(Rental $rental)
    {
        if (!auth()->user()->can('handover', $rental)) {
            return back()->withErrors(['message' => 'Unauthorized action']);
        }

        try {
            DB::transaction(function () use ($rental) {
                // Update rental status and add handover timestamp
                $rental->confirmHandover();
                
                // Ensure listing is marked as unavailable
                $rental->listing->update(['is_available' => false]);
            });
            
            return back()->with('success', 'Tool handed over successfully. Rental period has started.');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }
    }
}