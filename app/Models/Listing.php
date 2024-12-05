<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'desc',
        'category_id',
        'value',
        'price',
        'approved',
        'is_available',
        'location_id'
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'approved' => 'boolean'
    ];

    protected static function booted()
    {
        // Update is_available based on rental status whenever rentals are updated
        static::updated(function ($listing) {
            if ($listing->isRented() && $listing->is_available) {
                $listing->update(['is_available' => false]);
            }
        });
    }

    // Relationships
    public function user() {
        return $this->belongsTo(User::class);
    }

    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function location() {
        return $this->belongsTo(Location::class);
    }

    public function images() {
        return $this->hasMany(ListingImage::class);
    }

    public function rentals() {
        return $this->hasMany(Rental::class)->with('renter');
    }

    // Search scope
    public function scopeFilter($query, array $filters) {
        if ($filters['search'] ?? false) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . request('search') . '%')
                  ->orWhere('desc', 'like', '%' . request('search') . '%');
            });
        }

        if ($filters['user_id'] ?? false) {
            $query->where('user_id', request('user_id'));
        }
    }

    // Helper method to check if tool is currently rented
    public function isRented(): bool
    {
        return $this->rentals()
            ->whereIn('rental_status_id', [3, 4, 9]) // Only when paid, active, or overdue
            ->exists();
    }

    // Helper method to check if tool can be rented
    public function canBeRented(): bool
    {
        // Only available if:
        // 1. Marked as available by owner
        // 2. Approved by admin
        // 3. Not currently rented
        // 4. Not owned by the current user
        return $this->is_available 
            && $this->approved 
            && !$this->isRented();
    }

    public function hasActiveRequests(): bool
    {
        return $this->rentals()
            ->whereIn('rental_status_id', [1, 2]) // pending or approved
            ->exists();
    }

    // Method to safely update availability
    public function updateAvailability(bool $status): void
    {
        if ($status && $this->isRented()) {
            throw new \Exception('Cannot make listing available while it is being rented');
        }
        $this->update(['is_available' => $status]);
    }

    // Add accessor for real availability state
    public function getRealAvailabilityAttribute(): bool
    {
        return $this->canBeRented();
    }

    // Add method to check if user can view listing details
    public function canBeViewedBy(?User $user): bool 
    {
        // Public listing OR owner/admin can always view
        if ($this->canBeRented() || 
            ($user && ($this->user_id === $user->id || $user->isAdmin()))
        ) {
            return true;
        }

        // If rented, only current renter can view
        return $user && $this->currentRental?->renter_id === $user->id;
    }

    // Add relationship to get current rental
    public function currentRental()
    {
        return $this->hasOne(Rental::class)
            ->whereIn('rental_status_id', [2, 3, 4, 9]); // approved, paid, active, or overdue
    }
}
