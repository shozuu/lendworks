<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentalReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_id',
        'reviewer_id', 
        'rating',
        'comment'
    ];

    protected $casts = [
        'rating' => 'integer'
    ];

    // Validation rules
    public static $rules = [
        'rating' => 'required|integer|min:1|max:5',
        'comment' => 'nullable|string|max:500'
    ];

    public function rental()
    {
        return $this->belongsTo(Rental::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    // Get the reviewed user (either renter or lender)
    public function reviewedUser()
    {
        // If reviewer is renter, return lender, otherwise return renter
        return $this->reviewer_id === $this->rental->renter_id 
            ? $this->rental->listing->user
            : $this->rental->renter;
    }

    // Check if review is from renter
    public function isRenterReview(): bool
    {
        return $this->reviewer_id === $this->rental->renter_id;
    }

    // Check if review is from lender
    public function isLenderReview(): bool
    {
        return $this->reviewer_id === $this->rental->listing->user_id;
    }

    // After a review is created/updated, update the reviewed user's rating
    protected static function booted()
    {
        static::created(function ($review) {
            $review->reviewedUser()->updateRating();
        });

        static::updated(function ($review) {
            $review->reviewedUser()->updateRating();
        });
    }

    // Get all reviews for a specific user (as either renter or lender)
    public static function getUserReviews(User $user)
    {
        return static::whereHas('rental', function ($query) use ($user) {
            $query->where('renter_id', $user->id)
                ->orWhereHas('listing', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
        });
    }
}