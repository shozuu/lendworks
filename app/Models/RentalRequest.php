<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

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
        'return_at'
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
        'is_overdue'
    ];

    // Define core rental status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_RENTER_PAID = 'renter_paid';
    const STATUS_PENDING_PROOF = 'pending_proof';

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

    /**
     * Get the timeline events for the rental request.
     */
    public function timeline_events()
    {
        return $this->hasMany(RentalTimelineEvent::class, 'rental_request_id');
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
            'canInitiateReturn' => $isRenter && $this->status === 'active',
            'canSubmitReturn' => $isRenter && $this->status === 'return_scheduled',
            'canConfirmReturn' => $isLender && $this->status === 'pending_return_confirmation',
        ];

        if (!$user) return $actions;

        // Lender can handover when status is to_handover
        $actions['canHandover'] = 
            $this->status === 'to_handover' && 
            $this->listing->user_id === $user->id;

        // Renter can receive when status is pending_proof
        $actions['canReceive'] = 
            $this->status === 'pending_proof' && 
            $this->renter_id === $user->id;

        return $actions;
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
}
