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
        'status'
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
        'available_actions'
    ];

    // Define core rental status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_RENTER_PAID = 'renter_paid';

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
        if (!$this->hasStarted || $this->hasEnded) {
            return false;
        }
        return now()->greaterThan($this->end_date);
    }

    public function getAvailableActionsAttribute(): array 
    {
        // Get the authenticated user
        $user = Auth::user();
        $isRenter = $user && $user->id === $this->renter_id;
        
        return [
            'canApprove' => !$isRenter && $this->canApprove(),
            'canReject' => !$isRenter && $this->canReject(),
            'canCancel' => $this->canCancel(),
            'canPayNow' => $isRenter && $this->canPayNow(),
            'canViewPayment' => $isRenter && $this->canViewPayment(),
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
                if ($this->payment_request?->status === 'rejected') {
                    return true;
                }
                else if ($this->payment_request?->status === 'pending' || $this->payment_request?->status === 'verified') {
                    return false;
                }

                return true;
            }
            
            return false;
        }
        
        // If user is the lender
        if ($user->id === $this->listing->user_id) {
            // Can only cancel if status is approved and no payment has been made yet
            if ($this->status === self::STATUS_APPROVED && !$this->payment_request) {
                return true;
            }
            
            return false;
        }
        
        return false;
    }

    public function canPayNow(): bool 
    {
        return $this->status === self::STATUS_APPROVED && !$this->payment_request;
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
