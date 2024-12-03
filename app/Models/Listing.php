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
        return $this->hasMany(Rental::class);
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
            ->whereIn('rental_status_id', [2, 3]) // paid or active status
            ->exists();
    }
}
