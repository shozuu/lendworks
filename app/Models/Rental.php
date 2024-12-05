<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class Rental extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'renter_id',
        'rental_status_id',
        'start_date',
        'end_date',
        'base_price',
        'discount',
        'service_fee',
        'total_price',
        'payment_status',
        'payment_received_at',
        'started_at',
        'completed_at'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'payment_received_at' => 'datetime',
        'payment_released_at' => 'datetime',
        'handover_at' => 'datetime',
        'return_at' => 'datetime',
        'overdue_paid_at' => 'datetime'
    ];

    protected static function booted()
    {
        // Check and update listing availability on both create and update
        static::created(function ($rental) {
            if ($rental->isActive()) {
                $rental->listing->update(['is_available' => false]);
            }
        });

        static::updated(function ($rental) {
            if ($rental->isActive() && $rental->listing->is_available) {
                $rental->listing->update(['is_available' => false]);
            } elseif (!$rental->isActive() && !$rental->listing->is_available) {
                // Only make available if there are no other active rentals
                if (!$rental->listing->isRented()) {
                    $rental->listing->update(['is_available' => true]);
                }
            }
        });
    }

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function renter()
    {
        return $this->belongsTo(User::class, 'renter_id');
    }

    public function status()
    {
        return $this->belongsTo(RentalStatus::class, 'rental_status_id');
    }

    public function reviews()
    {
        return $this->hasMany(RentalReview::class);
    }

    public function disputes()
    {
        return $this->hasMany(Dispute::class);
    }

    // Simplified status checks
    public function isPending(): bool { return $this->rental_status_id === 1; }
    public function isApproved(): bool { return $this->rental_status_id === 2; }
    public function isPaid(): bool { return $this->rental_status_id === 3; }
    public function isActive(): bool { return $this->rental_status_id === 4; } // Add this back
    public function isCompleted(): bool { return $this->rental_status_id === 5; }
    public function isCancelled(): bool { return $this->rental_status_id === 6; }

    // Payment status checks
    public function isPaymentEmpty(): bool
    {
        return $this->payment_status === 'empty';
    }

    public function isPendingPayment(): bool
    {
        return $this->payment_status === 'pending';
    }

    public function isPaymentVerified(): bool
    {
        return $this->payment_status === 'in_escrow';
    }

    public function isPaymentReleased(): bool
    {
        return $this->payment_status === 'released_to_lender';
    }

    // Calculate rental duration in days
    public function getDurationInDays(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    // Check if rental period has started
    public function hasStarted(): bool
    {
        return now()->gte($this->start_date);
    }

    // Check if rental period has ended
    public function hasEnded(): bool
    {
        return now()->gte($this->end_date);
    }

    public function hasActiveDispute(): bool
    {
        return $this->disputes()
            ->whereNotIn('status', ['resolved', 'cancelled'])
            ->exists();
    }

    public function createDispute(array $data): Dispute
    {
        return $this->disputes()->create($data);
    }

    // Simplified lifecycle methods
    public function approve(): void
    {
        if (!$this->isPending()) {
            throw new \Exception('Only pending rentals can be approved');
        }
        $this->update(['rental_status_id' => 2]);
    }

    public function markAsPaid(): void
    {
        if (!$this->isApproved()) {
            throw new \Exception('Rental must be approved first');
        }
        $this->update(['rental_status_id' => 3]);
    }

    public function startRental(): void
    {
        if (!$this->isPaid()) {
            throw new \Exception('Payment must be verified first');
        }
        $this->update([
            'rental_status_id' => 4,
            'started_at' => now()
        ]);
    }

    public function completeRental(): void
    {
        if (!$this->isOngoing()) {
            throw new \Exception('Rental must be ongoing to complete');
        }
        $this->update([
            'rental_status_id' => 5,
            'completed_at' => now()
        ]);
    }

    public function reject(): void
    {
        if (!$this->isPending()) {
            throw new \Exception('Only pending rentals can be rejected');
        }

        DB::transaction(function () {
            $this->update([
                'rental_status_id' => 6,
                'payment_status' => 'cancelled'
            ]);
        });
    }

    public function cancel(): void
    {
        if (!in_array($this->rental_status_id, [1, 2])) {
            throw new \Exception('Cannot cancel rental after payment verification');
        }
        $this->update([
            'rental_status_id' => 6,
            'payment_status' => 'cancelled'
        ]);
    }

    public function submitPayment(): void
    {
        if (!$this->isApproved() || !$this->isPaymentEmpty()) {
            throw new \Exception('Cannot submit payment at this stage');
        }
        $this->update([
            'payment_status' => 'pending',
            'payment_received_at' => now()
        ]);
    }

    public function verifyPayment(): void
    {
        if (!$this->isApproved() || !$this->isPendingPayment()) {
            throw new \Exception('Cannot verify payment at this stage');
        }
        
        DB::transaction(function () {
            $this->update([
                'rental_status_id' => 3,
                'payment_status' => 'paid'
            ]);
            
            // Make listing unavailable and cancel other requests
            $this->listing->update(['is_available' => false]);
            $this->listing->rentals()
                ->where('id', '!=', $this->id)
                ->whereIn('rental_status_id', [1, 2])
                ->update([
                    'rental_status_id' => 6,
                    'payment_status' => 'empty'
                ]);
        });
    }

    public function confirmHandover(): void
    {
        if (!$this->isPaid()) {
            throw new \Exception('Payment must be verified first');
        }
        
        if ($this->handover_at) {
            throw new \Exception('Tool has already been handed over');
        }

        $this->update([
            'rental_status_id' => 4, // Set to active
            'handover_at' => now(),
            'started_at' => now()
        ]);
    }

    public function handover(): void
    {
        if (!$this->isActive() || $this->payment_status !== 'paid') {
            throw new \Exception('Cannot handover at this stage');
        }
        $this->update([
            'rental_status_id' => 4,
            'started_at' => now()
        ]);
    }

    public function confirmReturn(): void
    {
        if (!$this->return_at) {
            throw new \Exception('Return must be initiated by renter first');
        }

        if (!$this->started_at) {
            throw new \Exception('Rental must have started before it can be returned');
        }
        
        DB::transaction(function () {
            $this->update([
                'rental_status_id' => 5, // Set to completed
                'completed_at' => now(),
                'payment_status' => 'pending_release'
            ]);
            
            // Make listing available again if no other active rentals
            if (!$this->listing->isRented()) {
                $this->listing->update(['is_available' => true]);
            }
        });
    }

    public function releasePayment(): void
    {
        if (!$this->isCompleted() || $this->payment_status !== 'pending_release') {
            throw new \Exception('Cannot release payment at this stage');
        }
        $this->update([
            'payment_status' => 'released',
            'payment_released_at' => now()
        ]);
        
        // Only make listing available after payment is released
        $this->listing->update(['is_available' => true]);
    }

    public function rejectPayment(): void
    {
        if (!$this->isApproved() || !$this->isPendingPayment()) {
            throw new \Exception('Cannot reject payment at this stage');
        }
        
        $this->update([
            'payment_status' => 'empty',  // Reset to empty to allow repayment
            'payment_received_at' => null
        ]);
    }

    public function initiateReturn(): void
    {
        if (!$this->started_at || $this->return_at) {
            throw new \Exception('Cannot initiate return at this stage');
        }
        
        $this->update([
            'return_at' => now()
        ]);
    }

    // Helper methods to check state
    public function canBeCancelled(): bool
    {
        return in_array($this->rental_status_id, [1, 2]);
    }

    public function canBeViewed(?User $user): bool
    {
        return $user && (
            $user->id === $this->renter_id || 
            $user->id === $this->listing->user_id || 
            $user->isAdmin()
        );
    }
}