<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Listing extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'desc',
        'value',
        'price',
        'status',
        'rejection_reason',
        'is_available',
        'category_id',
        'user_id',
        'location_id',
        'deposit_fee'
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'status' => 'string'
    ];

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

    public function rejectionReasons()
    {
        return $this->belongsToMany(RejectionReason::class, 'listing_rejections')
            ->using(ListingRejection::class)
            ->withPivot(['custom_feedback', 'admin_id', 'created_at'])
            ->withTimestamps();
    }

    public function latestRejection()
    {
        return $this->hasOne(ListingRejection::class)
            ->latest()
            ->with('rejectionReason');
    }

    public function takedownReasons()
    {
        return $this->belongsToMany(TakedownReason::class, 'listing_takedowns')
            ->using(ListingTakedown::class)
            ->withPivot(['custom_feedback', 'admin_id', 'created_at'])
            ->withTimestamps();
    }

    public function latestTakedown()
    {
        return $this->hasOne(ListingTakedown::class)
            ->latest()
            ->with('takedownReason');
    }

    public function rentalRequests() {
        return $this->hasMany(RentalRequest::class);
    }

    public function getRejectionDetailsAttribute()
    {
        if ($this->status !== 'rejected') {
            return null;
        }

        $rejection = $this->latestRejection()->with('rejectionReason')->first();
        
        if (!$rejection) {
            return null;
        }

        return [
            'reason' => $rejection->rejectionReason->label,
            'description' => $rejection->rejectionReason->description,
            'action_needed' => $rejection->rejectionReason->action_needed,
            'feedback' => $rejection->custom_feedback,
            'date' => $rejection->created_at
        ];
    }

    public function getTakedownDetailsAttribute()
    {
        if ($this->status !== 'taken_down') {
            return null;
        }

        $takedown = $this->latestTakedown()->with('takedownReason')->first();
        
        if (!$takedown) {
            return null;
        }

        return [
            'reason' => $takedown->takedownReason->label,
            'description' => $takedown->takedownReason->description,
            'feedback' => $takedown->custom_feedback,
            'date' => $takedown->created_at
        ];
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
}
