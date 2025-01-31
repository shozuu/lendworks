<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\RentalRequest;
use App\Notifications\NewRentalRequest;
use App\Notifications\RentalRequestApproved;
use App\Notifications\RentalRequestRejected;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RentalRequestController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'listing_id' => ['required', 'exists:listings,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'discount' => ['required', 'numeric', 'min:0'],
            'service_fee' => ['required', 'numeric', 'min:0'],
            'total_price' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            // Eager load the listing with its owner
            $listing = Listing::with('user')->findOrFail($validated['listing_id']);

            // Validate listing status and ownership
            if (!$listing->is_available || $listing->is_rented || $listing->status !== 'approved') {
                throw new \Exception('This item is not available for rent.');
            }

            if ($listing->user_id === Auth::id()) {
                throw new \Exception('You cannot rent your own listing.');
            }

            $dates = $this->parseDates($validated['start_date'], $validated['end_date']);

            $rentalRequest = new RentalRequest([
                ...$validated,
                'listing_id' => $listing->id,
                'renter_id' => Auth::id(),
                'start_date' => $dates['start'],
                'end_date' => $dates['end'],
                'status' => 'pending'
            ]);

            $rentalRequest->save();

            // Explicitly load relationships needed for notification
            $rentalRequest->load(['renter', 'listing.user']);
            $listing->user->notify(new NewRentalRequest($rentalRequest));

            return redirect()
                ->route('my-rentals')
                ->with('success', 'Rental request sent successfully!');
            
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to submit rental request. ' . $e->getMessage());
        }
    }

    private function parseDates($startDate, $endDate)
    {
        date_default_timezone_set('Asia/Manila');

        return [
            'start' => Carbon::createFromFormat('Y-m-d', $startDate, 'Asia/Manila')->startOfDay(),
            'end' => Carbon::createFromFormat('Y-m-d', $endDate, 'Asia/Manila')->endOfDay(),
        ];
    }

    public function approve(RentalRequest $rentalRequest)
    {
        // Check if user owns the listing
        if ($rentalRequest->listing->user_id !== Auth::id()) {
            abort(403);
        }

        // Check if listing is already rented
        if ($rentalRequest->listing->is_rented) {
            return back()->with('error', 'This item is currently rented.');
        }

        // Start a database transaction
        DB::transaction(function () use ($rentalRequest) {
            // Update rental request status
            $rentalRequest->update(['status' => 'approved']);
            
            // Mark listing as rented
            $rentalRequest->listing->update(['is_rented' => true]);
            
            // Reject all other pending requests for this listing
            RentalRequest::where('listing_id', $rentalRequest->listing_id)
                ->where('id', '!=', $rentalRequest->id)
                ->where('status', 'pending')
                ->update(['status' => 'rejected', 'rejection_reason' => 'Item has been rented to another user.']);
        });
        
        // Notify renter
        $rentalRequest->renter->notify(new RentalRequestApproved($rentalRequest));

        return back()->with('success', 'Rental request approved successfully.');
    }

    public function reject(Request $request, RentalRequest $rentalRequest)
    {
        // Check if user owns the listing
        if ($rentalRequest->listing->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000']
        ]);

        $rentalRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason']
        ]);

        // Notify renter
        $rentalRequest->renter->notify(new RentalRequestRejected($rentalRequest));

        return back()->with('success', 'Rental request rejected.');
    }
}
