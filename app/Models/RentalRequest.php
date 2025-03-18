<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RentalRequest extends Model
{
    protected $fillable = [
        'listing_id',
        'renter_id',
        'start_date',
        'end_date',
        'base_price',
        'discount',
        'service_fee',
        'deposit_fee',
        'total_price',
        'status',
        'handover_at',
        'return_at',
        'quantity_requested',  
        'quantity_approved'    
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',    
        'base_price' => 'integer',
        'discount' => 'integer',
        'service_fee' => 'integer',
        'deposit_fee' => 'integer',
        'total_price' => 'integer',
        'handover_at' => 'datetime',
        'return_at' => 'datetime'
    ];

    protected $appends = [
        'available_actions',
        'rental_duration',
        'remaining_days',
        'overdue_days',
        'is_overdue',
        'overdue_fee',
        'total_lender_earnings',
        'deposit_status'  // Add this line
    ];

    // Define core rental status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_ACTIVE = 'active';
    const STATUS_TO_HANDOVER = 'to_handover';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_RENTER_PAID = 'renter_paid';
    const STATUS_PENDING_PROOF = 'pending_proof';
    const STATUS_DISPUTED = 'disputed';  

    // Update the status display logic
    public function getStatusForDisplayAttribute(): string 
    {
        // Show payment status if approved and has payment
        if ($this->status === self::STATUS_APPROVED && $this->payment_request) {
            switch ($this->payment_request->status) {
                case 'pending':
                    return 'payment_pending';
                case 'rejected':
                    return 'payment_rejected';
                case 'verified':
                    return self::STATUS_RENTER_PAID;
            }
        }
        return $this->status;
    }

    // Relationships
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function renter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renter_id');
    }

    public function latestRejection()
    {
        return $this->hasOne(RentalRequestRejection::class)
            ->latest()
            ->with('rejectionReason'); 
    }

    public function rejectionReasons()
    {
        return $this->belongsToMany(RentalRejectionReason::class, 'rental_request_rejections')
            ->using(RentalRequestRejection::class)
            ->withPivot(['custom_feedback'])
            ->withTimestamps();
    }

    public function latestCancellation()
    {
        return $this->hasOne(RentalRequestCancellation::class)
            ->latest()
            ->with('cancellationReason'); 
    }

    public function cancellationReasons()
    {
        return $this->belongsToMany(RentalCancellationReason::class, 'rental_request_cancellations')
            ->using(RentalRequestCancellation::class)
            ->withPivot(['custom_feedback'])
            ->withTimestamps();
    }

    public function timelineEvents()
    {
        return $this->hasMany(RentalTimelineEvent::class)->with('actor')->orderBy('created_at', 'desc');
    }

    public function recordTimelineEvent($eventType, $actorId, $metadata = null)
    {
        return $this->timelineEvents()->create([
            'actor_id' => $actorId,
            'event_type' => $eventType,
            'status' => $this->status,
            'metadata' => $metadata
        ]);
    }

    public function payment_request()
    {
        return $this->hasOne(PaymentRequest::class)->latest();
    }

    public function handoverProofs()
    {
        return $this->hasMany(HandoverProof::class);
    }

    public function pickup_schedules()
    {
        return $this->hasMany(PickupSchedule::class);
    }

    public function return_schedules()
    {
        return $this->hasMany(ReturnSchedule::class);
    }

    public function returnProofs()
    {
        return $this->hasMany(ReturnProof::class);
    }

    /**
     * Get the timeline events for the rental request.
     */
    public function timeline_events()
    {
        return $this->hasMany(RentalTimelineEvent::class, 'rental_request_id');
    }

    // Add this relationship after the other relationship methods
    public function overdue_payment()
    {
        return $this->hasOne(OverduePayment::class)
            ->select(['id', 'rental_request_id', 'amount', 'verified_at', 'reference_number'])
            ->latest();
    }

    // Add this relationship
    public function completion_payments()
    {
        return $this->hasMany(CompletionPayment::class);
    }

    // Add this relationship method
    public function dispute()
    {
        return $this->hasOne(RentalDispute::class, 'rental_request_id')
            ->with(['resolvedBy']);  // Always eager load resolvedBy
    }

    public function depositDeductions()
    {
        return $this->hasMany(DepositDeduction::class);
    }

    public function lenderEarningsAdjustments()
    {
        return $this->hasMany(LenderEarningsAdjustment::class);
    }

    // Accessors
    public function getHasStartedAttribute(): bool
    {
        return $this->handover_at !== null;
    }

    public function getHasEndedAttribute(): bool
    {
        return $this->return_at !== null;
    }

    public function getIsOverdueAttribute(): bool
    {
        if ($this->status !== 'active' || !$this->end_date) {
            return false;
        }
        
        return Carbon::now()->startOfDay()->gt(Carbon::parse($this->end_date)->startOfDay());
    }

    public function getHasHandoverProofAttribute(): bool
    {
        return $this->handoverProofs()->where('type', 'handover')->exists();
    }

    public function getHasReceiveProofAttribute(): bool
    {
        return $this->handoverProofs()->where('type', 'receive')->exists();
    }

    public function getAvailableActionsAttribute(): array 
    {
        $user = Auth::user();
        $isRenter = $user && $user->id === $this->renter_id;
        $isLender = $user && $user->id === $this->listing->user_id;

        $actions = [
            'canApprove' => !$isRenter && $this->canApprove(),
            'canReject' => !$isRenter && $this->canReject(),
            'canCancel' => $this->canCancel(),
            'canPayNow' => $isRenter && $this->canPayNow(),
            'canHandover' => false,
            'canReceive' => false,
            'canInitiateReturn' => $isRenter && 
                $this->status === 'active' && 
                (!$this->is_overdue || $this->hasVerifiedOverduePayment()),
            'canSubmitReturn' => $isRenter && $this->status === 'return_scheduled',
            'canConfirmReturn' => $isLender && $this->status === 'pending_return_confirmation',
            'canFinalizeReturn' => $isLender && (
                // Allow finalization when status is pending_final_confirmation regardless of dispute
                $this->status === 'pending_final_confirmation' ||
                // Or when dispute is resolved (either accepted or rejected)
                ($this->status === 'disputed' && $this->dispute && $this->dispute->status === 'resolved')
            ),
            'canRaiseDispute' => $isLender && (
                // Can raise dispute on pending_final_confirmation if no dispute exists
                ($this->status === 'pending_final_confirmation' && !$this->dispute) ||
                // Or after previous dispute was rejected
                ($this->status === 'pending_final_confirmation' && 
                 $this->dispute && 
                 $this->dispute->resolution_type === 'rejected')
            ),
            'canChoosePickupSchedule' => $isRenter && 
                $this->status === 'to_handover' && 
                !$this->pickup_schedules()->where('is_selected', true)->exists()
        ];

        if (!$user) return $actions;

        // Lender can handover when status is to_handover AND has confirmed schedule
        $actions['canHandover'] = 
            $this->status === 'to_handover' && 
            $this->listing->user_id === $user->id &&
            $this->hasConfirmedPickupSchedule(); // Use the helper method we already have

        // Renter can receive when status is pending_proof
        $actions['canReceive'] = 
            $this->status === 'pending_proof' && 
            $this->renter_id === $user->id;

        $actions['canProcessLenderPayment'] = !$this->completion_payments()
            ->where('type', 'lender_payment')
            ->exists();
        
        $actions['canProcessDepositRefund'] = !$this->completion_payments()
            ->where('type', 'deposit_refund')
            ->exists();

        $actions['hasLenderPayment'] = $this->completion_payments()
            ->where('type', 'lender_payment')
            ->exists();

        $actions['hasDepositRefund'] = $this->completion_payments()
            ->where('type', 'deposit_refund')
            ->exists();

        return $actions;
    }

    // Add this new helper method
    public function hasDisputeInProgress(): bool
    {
        return (bool) $this->dispute;
    }

    public function getRentalDurationAttribute()
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }
        
        // Add one day to include both start and end dates
        return Carbon::parse($this->start_date)->startOfDay()
            ->diffInDays(Carbon::parse($this->end_date)->startOfDay()) + 1;
    }

    public function getRemainingDaysAttribute()
    {
        if ($this->status !== 'active' || !$this->end_date || !$this->start_date) {
            return 0;
        }
        
        $now = Carbon::now()->startOfDay();
        $start = Carbon::parse($this->start_date)->startOfDay();
        $end = Carbon::parse($this->end_date)->startOfDay();
        
        // Return the total duration before rental starts
        if ($now->lt($start)) {
            return $start->diffInDays($end) + 1;
        }
        
        // Return 0 if we're past the end date
        if ($now->gt($end)) {
            return 0;
        }
        
        // Return remaining days from today
        return $now->diffInDays($end) + 1;
    }

    public function getOverdueDaysAttribute()
    {
        if ($this->status !== 'active' || !$this->end_date) {
            return 0;
        }
        
        $now = Carbon::now()->startOfDay();
        $end = Carbon::parse($this->end_date)->startOfDay();
        
        if ($now->gt($end)) {
            return $now->diffInDays($end);
        }
        
        return 0;
    }

    public function getOverdueFeeAttribute()
    {
        // Always use verified payment amount if it exists
        $verifiedPayment = $this->overdue_payment()
            ->whereNotNull('verified_at')
            ->first();
            
        if ($verifiedPayment) {
            return (float) $verifiedPayment->amount;
        }

        // Otherwise calculate current overdue fee
        if (!$this->is_overdue || !$this->listing) {
            return 0;
        }
        
        // Get daily rate per unit and multiply by quantity
        $dailyRate = abs((float) $this->listing->price);
        $quantity = $this->quantity_approved ?: $this->quantity_requested;
        $overdueDays = abs($this->overdue_days);
        
        return $dailyRate * $overdueDays * $quantity;
    }

    // Add these new methods
    // Update this method to ensure overdue fee is included
    public function getLenderEarningsAttribute()
    {
        // Force load the relationships if not already loaded
        if (!$this->relationLoaded('overdue_payment')) {
            $this->load('overdue_payment');
        }

        // Cast values to integers and ensure they're not null
        $basePrice = (int) ($this->base_price ?? 0);
        $discount = (int) ($this->discount ?? 0);
        $serviceFee = (int) ($this->service_fee ?? 0);

        // Calculate base earnings
        $baseEarnings = $basePrice - $discount - $serviceFee;

        // Get verified overdue payment
        $verifiedPayment = $this->overdue_payment()
            ->whereNotNull('verified_at')
            ->first();

        // Cast overdue fee to integer
        $overdueFee = $verifiedPayment ? (int) $verifiedPayment->amount : 0;

        // Calculate total
        $totalEarnings = $baseEarnings + $overdueFee;

        return [
            'base' => max(0, $baseEarnings), // Ensure no negative values
            'overdue' => $overdueFee,
            'total' => max(0, $totalEarnings), // Ensure no negative values
            'hasOverdue' => $overdueFee > 0
        ];
    }

    // Fix the hasVerifiedOverduePayment method
    public function hasVerifiedOverduePayment(): bool
    {
        return $this->overdue_payment()
            ->whereNotNull('verified_at')
            ->exists();
    }

    // Add new attribute for overdue status information
    public function getOverdueStatusAttribute()
    {
        if (!$this->is_overdue && !$this->overdue_payment) {
            return null;
        }

        $verifiedPayment = $this->overdue_payment()
            ->whereNotNull('verified_at')
            ->first();

        return [
            'is_overdue' => $this->is_overdue,
            'days_overdue' => $this->overdue_days,
            'fee_amount' => $this->overdue_fee,
            'is_paid' => (bool) $verifiedPayment,
            'payment_details' => $verifiedPayment ? [
                'verified_at' => $verifiedPayment->verified_at,
                'reference_number' => $verifiedPayment->reference_number
            ] : null
        ];
    }

    // Add this new method
    public function getTotalLenderEarningsAttribute()
    {
        $baseEarnings = $this->base_price - $this->discount - $this->service_fee;
        $overdueFee = $this->overdue_payment ? $this->overdue_payment->amount : 0;
        $depositDeduction = $this->dispute && $this->dispute->resolution_type === 'deposit_deducted'
            ? $this->dispute->deposit_deduction
            : 0;

        return $baseEarnings + $overdueFee + $depositDeduction;
    }

    public function getRemainingDepositAttribute()
    {
        $depositFee = $this->deposit_fee;
        $deduction = $this->dispute && $this->dispute->resolution_type === 'deposit_deducted' 
            ? $this->dispute->deposit_deduction 
            : 0;
            
        return max(0, $depositFee - $deduction);
    }

    public function getDepositStatusAttribute()
    {
        $originalAmount = $this->deposit_fee;
        $deductionAmount = $this->dispute && $this->dispute->resolution_type === 'deposit_deducted' 
            ? $this->dispute->deposit_deduction 
            : 0;
            
        $perUnitDeposit = $this->quantity_approved 
            ? ($this->deposit_fee / $this->quantity_approved)
            : ($this->deposit_fee / $this->quantity_requested);

        return [
            'original_amount' => $originalAmount,
            'per_unit_amount' => $perUnitDeposit,
            'deducted_amount' => $deductionAmount,
            'remaining_amount' => max(0, $originalAmount - $deductionAmount),
            'has_deductions' => $this->dispute && $this->dispute->resolution_type === 'deposit_deducted',
            'deduction_reason' => $this->dispute?->deposit_deduction_reason,
            'is_refunded' => $this->completion_payments()->where('type', 'deposit_refund')->exists(),
            'quantity' => $this->quantity_approved ?: $this->quantity_requested
        ];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    // get all rental requests created within the last 7 days
    public function scopeWithinPeriod(Builder $query, $days)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    // Helper methods
    public function isStatus(string $status): bool
    {
        return $this->status === $status;
    }

    public function canApprove(): bool
    {
        return $this->isStatus(self::STATUS_PENDING) && 
               !$this->listing->is_rented;
    }

    public function canReject(): bool
    {
        return $this->isStatus(self::STATUS_PENDING);
    }

    public function canCancel(): bool
    {
        // Get the authenticated user
        $user = Auth::user();
        
        // If user is the renter
        if ($user->id === $this->renter_id) {
            if ($this->status === self::STATUS_PENDING) {
                return true;
            }

            if ($this->status === self::STATUS_APPROVED) {
                // Check if there's a payment request and its status
                if ($this->payment_request) {
                    return $this->payment_request->status === 'rejected';
                }
                return true; // Can cancel if no payment request exists
            }
            
            return false;
        }
        
        // If user is the lender
        if ($user->id === $this->listing->user_id) {
            // Can only cancel if status is approved and either:
            // 1. No payment request exists, or
            // 2. Payment request exists and is rejected
            if ($this->status === self::STATUS_APPROVED) {
                return !$this->payment_request || $this->payment_request->status === 'rejected';
            }
            
            return false;
        }
        
        return false;
    }

    public function canPayNow(): bool 
    {
        return $this->status === self::STATUS_APPROVED && 
            (!$this->payment_request || $this->payment_request->status === 'rejected');
    }

    public function canViewPayment(): bool 
    {
        return $this->payment_request !== null;
    }

    public function canInitiateReturn(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        // Can't initiate return if overdue and hasn't paid
        if ($this->is_overdue && !$this->hasVerifiedOverduePayment()) {
            return false;
        }

        return true;
    }

    public function hasPendingOverduePayment(): bool
    {
        return $this->payment_request()
            ->where('type', 'overdue')
            ->where('status', 'pending')
            ->exists();
    }

    public function isPaidOverdue(): bool
    {
        return $this->is_overdue && $this->hasVerifiedOverduePayment();
    }

    public function getOverlappingRequests()
    {
        return static::where('listing_id', $this->listing_id)
            ->where('id', '!=', $this->id)  // Exclude current request
            ->where('status', 'pending')     // Only get pending requests
            ->where(function ($query) {
                $query->where(function ($q) {
                    // Two rentals overlap if:
                    // Request A's end date is >= Request B's start date AND
                    // Request A's start date is <= Request B's end date
                    $q->where('start_date', '<=', $this->end_date)
                      ->where('end_date', '>=', $this->start_date);
                });
            })
            ->get();
    }

    public static function hasExistingRequest($listingId, $renterId): bool
    {
        return static::where('listing_id', $listingId)
            ->where('renter_id', $renterId)
            ->whereIn('status', ['pending', 'approved', 'active'])
            // consider other status
            ->exists();
    }

    public static function getExistingRequest($listingId, $renterId)
    {
        return static::where('listing_id', $listingId)
            ->where('renter_id', $renterId)
            ->whereIn('status', ['pending', 'approved', 'active'])
            ->first();
    }

    public function recalculatePrices()
    {
        // Get price per unit
        $perUnitBasePrice = $this->base_price / $this->quantity_requested;
        $perUnitDiscount = $this->discount / $this->quantity_requested;
        $perUnitServiceFee = $this->service_fee / $this->quantity_requested;
        $perUnitDepositFee = $this->deposit_fee / $this->quantity_requested;
        
        // Calculate new totals based on approved quantity
        $this->base_price = $perUnitBasePrice * $this->quantity_approved;
        $this->discount = $perUnitDiscount * $this->quantity_approved;
        $this->service_fee = $perUnitServiceFee * $this->quantity_approved;
        $this->deposit_fee = $perUnitDepositFee * $this->quantity_approved;
        $this->total_price = ($this->base_price - $this->discount + $this->service_fee + $this->deposit_fee);
        
        return $this;
    }

    /**
     * Check and update completion payment status
     */
    public function checkCompletionPaymentStatus()
    {
        if ($this->status !== 'completed_pending_payments') {
            return;
        }

        $hasLenderPayment = $this->completion_payments()
            ->where('type', 'lender_payment')
            ->exists();
            
        $hasDepositRefund = $this->completion_payments()
            ->where('type', 'deposit_refund')
            ->exists();

        if ($hasLenderPayment && $hasDepositRefund) {
            $this->update(['status' => 'completed_with_payments']);
        }
    }
}
