<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\RentalRejectionReason;
use App\Models\RentalCancellationReason;
use App\Models\RentalRequest;
use App\Notifications\NewRentalRequest;
use App\Notifications\RentalRequestApproved;
use App\Notifications\RentalRequestRejected;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\LenderPickupSchedule;

class RentalRequestController extends Controller
{
    public function show(RentalRequest $rental)
    {
        $rental->load([
            'listing.user',
            'listing.images',
            'listing.category',
            'listing.location',
            'renter',
            'timelineEvents.actor',
            'payment_request',
            'return_schedules' => function($query) {
                $query->orderBy('created_at', 'desc');
            },
            'pickup_schedules',
            'completion_payments',  // Add this line
            'dispute' => function($query) {
                $query->select([
                    'id',
                    'rental_request_id',
                    'reason',
                    'description',
                    'status',
                    'resolution_type',  // Add this line
                    'verdict',
                    'verdict_notes',
                    'deposit_deduction',
                    'deposit_deduction_reason',
                    'resolved_at',  // Add this
                    'resolved_by'   // Add this
                ]);
            },
            'dispute.resolvedBy',  // Add this to load resolver info
        ]);

        // Check if the authenticated user is either the renter or the lender
        if (Auth::id() !== $rental->renter_id && Auth::id() !== $rental->listing->user_id) {
            abort(403, 'Unauthorized action.');
        }

        $rental->load([
            'listing' => fn($q) => $q->with([
                'images', 
                'category', 
                'location', 
                'user' => fn($q) => $q->withCount('listings')
            ]),
            'renter' => fn($q) => $q->withCount('listings'),
            'latestRejection.rejectionReason',
            'latestCancellation.cancellationReason',
            'payment_request',
            'timelineEvents'
        ]);

        // Determine user role
        $userRole = Auth::id() === $rental->renter_id ? 'renter' : 'lender';
        
        // Format reasons for select options with role-based filtering
        $reasons = [
            'rejectionReasons' => RentalRejectionReason::all()->map(function($reason) {
                return [
                    'value' => $reason->id,
                    'label' => $reason->label,
                ];
            }),
            'cancellationReasons' => RentalCancellationReason::whereIn('role', [$userRole, 'both'])
                ->get()
                ->map(function($reason) {
                    return [
                        'value' => $reason->id,
                        'label' => $reason->label,
                    ];
                })
        ];

        // Add lender schedules for the rental
        $lenderSchedules = LenderPickupSchedule::where('user_id', $rental->listing->user_id)
            ->where('is_active', true)
            ->get();

        return Inertia::render('RentalDetails', [
            'rental' => $rental,
            'userRole' => $userRole,
            'lenderSchedules' => $lenderSchedules,
            ...$reasons
        ]);        
    }

    public function store(Request $request)
    {
        try {
            // Set timezone to Manila for validation
            date_default_timezone_set('Asia/Manila');
            $today = Carbon::today('Asia/Manila')->startOfDay();

            $validated = $request->validate([
                'listing_id' => ['required', 'exists:listings,id'],
                'start_date' => ['required', 'date', 'after_or_equal:'.$today->format('Y-m-d')],
                'end_date' => ['required', 'date', 'after_or_equal:start_date'],
                'base_price' => ['required', 'numeric', 'min:0'],
                'discount' => ['required', 'numeric', 'min:0'],
                'service_fee' => ['required', 'numeric', 'min:0'],
                'deposit_fee' => ['required', 'numeric', 'min:0'], 
                'total_price' => ['required', 'numeric', 'min:0'],
                'quantity_requested' => ['required', 'integer', 'min:1'], // Add this line
            ]);

            $listing = Listing::with('user')->findOrFail($validated['listing_id']);

            // Validate quantity
            if ($validated['quantity_requested'] > $listing->quantity) {
                throw new \Exception('Requested quantity exceeds available units.');
            }

            // logic checks for duplicates
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

            DB::beginTransaction();
            try {
                $rentalRequest = new RentalRequest([
                    ...$validated,
                    'listing_id' => $listing->id,
                    'renter_id' => Auth::id(),
                    'start_date' => $dates['start'],
                    'end_date' => $dates['end'],
                    'status' => 'pending',
                    'quantity_approved' => null // Add this line
                ]);

                $rentalRequest->save();
                $rentalRequest->recordTimelineEvent('created', Auth::id());
                DB::commit();

                // notify owner
                $rentalRequest->load(['renter', 'listing.user']);
                $listing->user->notify(new NewRentalRequest($rentalRequest));

                return redirect()
                    ->route('my-rentals')
                    ->with('success', 'Rental request sent successfully!');

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Let validation exceptions bubble up to be handled by Inertia
            throw $e;
        } catch (\Exception $e) {
            report($e);
            // Return a generic error for other exceptions
            return back()->with('error', 'An error occurred while processing your request. Please try again.');
        }
    }

    private function parseDates($startDate, $endDate)
    {
        date_default_timezone_set('Asia/Manila');
        
        return [
            'start' => Carbon::createFromFormat('Y-m-d', $startDate, 'Asia/Manila')
                ->startOfDay(), // 00:00:00
            'end' => Carbon::createFromFormat('Y-m-d', $endDate, 'Asia/Manila')
                ->endOfDay(), // 23:59:59
        ];
    }

    public function approve(Request $request, RentalRequest $rentalRequest)
    {
        if ($rentalRequest->listing->user_id !== Auth::id()) {
            abort(403);
        }

        // Validate the approved quantity
        $validated = $request->validate([
            'quantity_approved' => [
                'required',
                'integer',
                'min:1',
                'max:' . min($rentalRequest->quantity_requested, $rentalRequest->listing->available_quantity)
            ]
        ]);

        try {
            DB::transaction(function () use ($rentalRequest, $validated) {
                // Update approved quantity and recalculate prices
                $rentalRequest->quantity_approved = $validated['quantity_approved'];
                $rentalRequest->recalculatePrices()->save();
                
                // Update status and create timeline event
                $rentalRequest->update(['status' => 'approved']);
                $rentalRequest->recordTimelineEvent('approved', Auth::id(), [
                    'quantity_requested' => $rentalRequest->quantity_requested,
                    'quantity_approved' => $rentalRequest->quantity_approved,
                    'original_total' => $rentalRequest->getOriginal('total_price'),
                    'adjusted_total' => $rentalRequest->total_price
                ]);
                
                // Update listing's available quantity 
                $rentalRequest->listing->decrement('available_quantity', $validated['quantity_approved']);

                // Update listing's rental status based on available_quantity
                if ($rentalRequest->listing->available_quantity <= 0) {
                    $rentalRequest->listing->update(['is_rented' => true]);
                    
                    // Only reject overlapping requests when no quantities are left
                    $overlappingRequests = $rentalRequest->getOverlappingRequests();
                    $unavailableReason = RentalRejectionReason::where('code', 'unavailable')->first();
                    
                    foreach ($overlappingRequests as $request) {
                        $request->update(['status' => 'rejected']);
                        
                        $periodMessage = 'All units of this item have been rented out for your requested period.';
                        
                        $request->rejectionReasons()->attach($unavailableReason->id, [
                            'custom_feedback' => $periodMessage,
                            'lender_id' => Auth::id()
                        ]);

                        $request->recordTimelineEvent('rejected', Auth::id(), [
                            'reason' => $unavailableReason->description,
                            'action_needed' => $unavailableReason->action_needed,
                            'label' => $unavailableReason->label,
                            'feedback' => $periodMessage,
                            'auto_rejected' => true,
                            'approved_request_id' => $rentalRequest->id
                        ]);
                        
                        $request->renter->notify(new RentalRequestRejected($request));
                    }
                }
            });

            // Notify approved renter
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
                'min:10',
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

                $rentalRequest->recordTimelineEvent('rejected', Auth::id());
            });

            // Notify renter
            $rentalRequest->load('latestRejection.rejectionReason');
            $rentalRequest->renter->notify(new RentalRequestRejected($rentalRequest));

            return back()->with('success', 'Rental request rejected successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reject rental request.');
        }
    }

    public function cancel(Request $request, RentalRequest $rentalRequest)
    {
        $user = Auth::user();
        $isRenter = $user->id === $rentalRequest->renter_id;
        $isLender = $user->id === $rentalRequest->listing->user_id;

        // Check if user is either the renter or lender
        if (!$isRenter && !$isLender) {
            abort(403);
        }

        // check if request can be cancelled
        if (!$rentalRequest->canCancel()) { 
            return back()->with('error', 'This rental request cannot be cancelled.');
        }

        $validated = $request->validate([
            'cancellation_reason_id' => ['required', 'exists:rental_cancellation_reasons,id'],
            'custom_feedback' => [
                'required_if:cancellation_reason_id,other',
                'nullable',
                'string',
                'min:10', 
                'max:1000'
            ],
        ]);

        try {
            DB::transaction(function () use ($rentalRequest, $validated, $isRenter, $isLender, $user) {
                // Get the cancellation reason
                $cancellationReason = RentalCancellationReason::find($validated['cancellation_reason_id']);
                
                // Update rental request status
                $rentalRequest->update(['status' => 'cancelled']);
                
                // Create cancellation record
                $rentalRequest->cancellationReasons()->attach($validated['cancellation_reason_id'], [
                    'custom_feedback' => $validated['custom_feedback']
                ]);

                // Restore listing available_quantity if this was an approved request
                if ($rentalRequest->quantity_approved) {
                    $listing = $rentalRequest->listing;
                    
                    // Increment available quantity
                    $listing->increment('available_quantity', $rentalRequest->quantity_approved);

                    // set back to false since we now have available quantity
                    $listing->update(['is_rented' => false]);
                }

                // Record timeline event
                $rentalRequest->recordTimelineEvent('cancelled', $user->id, [
                    'reason' => $cancellationReason->description,
                    'label' => $cancellationReason->label,
                    'feedback' => $validated['custom_feedback'],
                    'cancelled_by' => $isRenter ? 'renter' : 'lender',
                    'canceller_name' => $user->name,
                    'role' => $cancellationReason->role,
                    'restored_quantity' => $rentalRequest->quantity_approved
                ]);
            });

            return back()->with('success', 'Rental request cancelled successfully.');
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Failed to cancel rental request.');
        }
    }

    // Add this method to handle dispute creation
    public function raiseDispute(Request $request, RentalRequest $rental)
    {
        $validated = $request->validate([
            'reason' => 'required|string',
            'issue_description' => 'required|string',
            'proof_image' => 'required|image|max:5120'
        ]);

        // Ensure user is lender and can raise dispute
        if (auth()->id() !== $rental->listing->user_id || !$rental->available_actions['canRaiseDispute']) {
            abort(403);
        }

        DB::transaction(function () use ($rental, $validated, $request) {
            // Store proof image
            $proofPath = $request->file('proof_image')->store('dispute-proofs', 'public');

            // Create new dispute record - keep old records
            $dispute = $rental->disputes()->create([
                'reason' => $validated['reason'],
                'description' => $validated['issue_description'],
                'proof_path' => $proofPath,
                'status' => 'pending',
                'raised_by' => auth()->id()
            ]);

            // Update rental status
            $rental->update(['status' => 'disputed']);

            // Record timeline event
            $rental->recordTimelineEvent('dispute_raised', auth()->id(), [
                'dispute_id' => $dispute->id,
                'reason' => $validated['reason']
            ]);
        });

        return back()->with('success', 'Dispute has been raised successfully.');
    }
}
