<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'phone', // gcash associated number
        'about',
        'profile_image',
        'rating',
        'successful_rentals',
        'successful_lendings'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'rating' => 'decimal:2'
    ];

    // Tools listed by user (as lender)
    public function listings() {
        return $this->hasMany(Listing::class);
    }

    // Tools rented by user (as renter)
    public function rentals() {
        return $this->hasMany(Rental::class, 'renter_id');
    }

    // Get all reviews given by user
    public function givenReviews() {
        return $this->hasMany(RentalReview::class, 'reviewer_id');
    }

    // Get rentals made on user's listings
    public function receivedRentals() {
        return $this->hasManyThrough(Rental::class, Listing::class);
    }

    // Helper methods
    public function isAdmin(): bool {
        return $this->role === 'admin';
    }

    public function isActive(): bool {
        return $this->status === 'active';
    }

    // Calculate and update user rating
    public function updateRating(): void
    {
        // Get reviews from rentals where user is renter
        $renterReviews = RentalReview::whereHas('rental', function ($query) {
            $query->where('renter_id', $this->id);
        })->where('reviewer_id', '!=', $this->id);

        // Get reviews from rentals where user is lender
        $lenderReviews = RentalReview::whereHas('rental.listing', function ($query) {
            $query->where('user_id', $this->id);
        })->where('reviewer_id', '!=', $this->id);

        // Calculate average rating from both roles
        $allReviews = $renterReviews->union($lenderReviews)->get();
        $this->rating = $allReviews->avg('rating') ?? 0;
        $this->save();
    }

    // Update rental success counts
    public function incrementRentalSuccess(string $type = 'rental'): void
    {
        if ($type === 'rental') {
            $this->increment('successful_rentals');
        } else {
            $this->increment('successful_lendings');
        }
    }

    // Get user trust score
    public function getTrustScore(): int
    {
        return (int) (
            ($this->rating * 20) + // Rating converted to 0-100 scale
            ($this->successful_rentals * 2) + // Points per successful rental
            ($this->successful_lendings * 2) // Points per successful lending
        );
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    public function defaultLocation()
    {
        return $this->locations()->where('is_default', true)->first();
    }

    // Get all reviews received (as both renter and lender)
    public function receivedReviews()
    {
        return RentalReview::getUserReviews($this)->where('reviewer_id', '!=', $this->id);
    }
}
