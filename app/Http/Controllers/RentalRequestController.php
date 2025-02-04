<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\RentalRejectionReason;
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
            // Eager load the listing with its owner and rejection data
            $listing = Listing::with('user')->findOrFail($validated['listing_id']);

            // Check for existing rental request
            if (RentalRequest::hasExistingRequest($listing->id, Auth::id())) {
                $existingRequest = RentalRequest::getExistingRequest($listing->id, Auth::id());
                $status = $existingRequest->status;
                
                $message = match($status) {
                    'pending' => 'You already have a pending request for this item.',
                    'approved' => 'Your request for this item has already been approved.',
                    'active' => 'You are currently renting this item.',
                    default => 'You cannot create multiple requests for the same item.'
                };
                
                return back()->with('error', $message)->withoutScrolling(false);
            }

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
            $rentalRequest->load(['renter', 'listing.user', 'latestRejection.rejectionReason']);
            $listing->user->notify(new NewRentalRequest($rentalRequest));

            return redirect()
                ->route('my-rentals')
                ->with('success', 'Rental request sent successfully!');
            
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to submit rental request. ' . $e->getMessage())->withoutScrolling(false); 
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
        // First, verify ownership and availability
        if ($rentalRequest->listing->user_id !== Auth::id()) {
            abort(403);
        }
        if ($rentalRequest->listing->is_rented) {
            return back()->with('error', 'This item is currently rented.');
        }

        try {
            DB::transaction(function () use ($rentalRequest) {
                // 1. Approve the current request
                $rentalRequest->update(['status' => 'approved']);
                
                // 2. Mark listing as rented
                $rentalRequest->listing->update(['is_rented' => true]);
                
                // 3. Find all overlapping pending requests
                $overlappingRequests = $rentalRequest->getOverlappingRequests();
                
                // 4. Get the rejection reason for unavailability
                $unavailableReason = RentalRejectionReason::where('code', 'unavailable')->first();
                
                // 5. Reject all overlapping requests
                foreach ($overlappingRequests as $request) {
                    // Mark as rejected
                    $request->update(['status' => 'rejected']);
                    
                    // Add rejection reason with custom message
                    $request->rejectionReasons()->attach($unavailableReason->id, [
                        'custom_feedback' => sprintf(
                            'This item has been rented to another user for the period of %s to %s.',
                            // if they don't settle the payment, the item will be available again
                            $rentalRequest->start_date->format('M d, Y'),
                            $rentalRequest->end_date->format('M d, Y')
                        ),
                        'lender_id' => Auth::id()
                    ]);
                    
                    // Notify rejected renters
                    $request->load('latestRejection.rejectionReason');
                    $request->renter->notify(new RentalRequestRejected($request));
                }
            });

            // 6. Notify approved renter
            $rentalRequest->renter->notify(new RentalRequestApproved($rentalRequest));

            return back()->with('success', 'Rental request approved successfully.');
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Failed to approve rental request.');
        }
    }

    public function reject(Request $request, RentalRequest $rentalRequest)
    {
        if ($rentalRequest->listing->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'rejection_reason_id' => ['required', 'exists:rental_rejection_reasons,id'],
            'custom_feedback' => [
                'required_if:rejection_reason_id,other',
                'nullable',
                'string',
                'max:1000'
            ],
        ]);

        try {
            DB::transaction(function () use ($rentalRequest, $validated) {
                // Update rental request status
                $rentalRequest->update(['status' => 'rejected']);

                // Create rejection record
                $rentalRequest->rejectionReasons()->attach($validated['rejection_reason_id'], [
                    'custom_feedback' => $validated['custom_feedback'],
                    'lender_id' => Auth::id()
                ]);
            });

            // Notify renter
            $rentalRequest->load('latestRejection.rejectionReason');
            $rentalRequest->renter->notify(new RentalRequestRejected($rentalRequest));

            return back()->with('success', 'Rental request rejected successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reject rental request.');
        }
    }

    public function cancel(RentalRequest $rentalRequest)
    {
        // abort if user is not the renter
        if ($rentalRequest->renter_id !== Auth::id()) {
            abort(403);
        }

        // check if request can be cancelled
        if (!$rentalRequest->canBeCancelled()) {
            return back()->with('error', 'This rental request cannot be cancelled.');
        }

        // update request status
        $rentalRequest->update(['status' => 'cancelled']);

        // if the request was approved, update listing status
        if ($rentalRequest->status === 'approved') {
            $rentalRequest->listing->update(['is_rented' => false]);
        }

        return back()->with('success', 'Rental request cancelled successfully.');
    }
}
