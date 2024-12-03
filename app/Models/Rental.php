<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rental extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'renter_id',
        'rental_status_id',
        'start_date',
        'end_date',
        'total_price',
        'service_fee',
        'discount',
        'renter_notes',
        'lender_notes',
        'payment_received_at',
        'payment_released_at',
        'handover_at',
        'return_at',
        'overdue_amount',
        'overdue_paid_at'
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

    // Helper methods for status checks
    public function isPending(): bool
    {
        return $this->rental_status_id === 1; // pending approval
    }

    public function isApproved(): bool
    {
        return $this->rental_status_id === 2; // approved, awaiting payment
    }

    public function isPaid(): bool
    {
        return $this->rental_status_id === 3; // paid, ready for pickup
    }

    public function isActive(): bool
    {
        return $this->rental_status_id === 4; // tool is being rented
    }

    public function isCompleted(): bool
    {
        return $this->rental_status_id === 5; // returned and payment released
    }

    public function isCancelled(): bool
    {
        return $this->rental_status_id === 5;
    }

    public function isDeclined(): bool
    {
        return $this->rental_status_id === 6;
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

    // Helper methods for transitions
    public function markAsPaid(): void
    {
        $this->update([
            'rental_status_id' => 3,
            'payment_received_at' => now()
        ]);
    }

    public function markAsHandedOver(): void
    {
        $this->update([
            'rental_status_id' => 4,
            'handover_at' => now()
        ]);
    }

    public function markAsReturned(): void
    {
        if (!$this->canBeReturned()) {
            throw new \Exception('Cannot return item until overdue fees are paid');
        }

        $this->update([
            'rental_status_id' => 5,
            'return_at' => now(),
            'payment_released_at' => now()
        ]);
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

    public function calculateOverdueDays(): int
    {
        if (!$this->hasEnded() || !$this->isActive()) {
            return 0;
        }
        return now()->diffInDays($this->end_date);
    }

    public function calculateOverdueFee(): float
    {
        $overdueDays = $this->calculateOverdueDays();
        if ($overdueDays <= 0) {
            return 0;
        }

        // Use daily rental rate for overdue fee
        return $overdueDays * $this->listing->price;
    }

    public function updateOverdueAmount(): void
    {
        $amount = $this->calculateOverdueFee();
        
        if ($amount > 0 && $this->overdue_amount === 0) {
            // Create dispute when item becomes overdue
            $this->createDispute([
                'dispute_type_id' => 2, // Late Return dispute type
                'reported_by' => $this->listing->user_id,
                'status' => 'pending',
                'description' => "Item was not returned by the agreed return date. Overdue fee is being charged at ₱{$this->listing->price} per day."
            ]);
            
            // Update rental status to overdue
            $this->update(['rental_status_id' => 9]); // New overdue status
        }

        $this->update(['overdue_amount' => $amount]);
    }

    public function markOverduePaid(): void
    {
        $this->update([
            'overdue_paid_at' => now(),
            'overdue_amount' => 0
        ]);
    }

    public function canBeReturned(): bool
    {
        return $this->overdue_amount == 0 || $this->overdue_paid_at !== null;
    }

    public function isOverdue(): bool
    {
        return $this->rental_status_id === 9;
    }
}