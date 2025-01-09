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
        'location_id'
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'status' => 'string'
    ];

    private static array $rejectionReasons = [
        'inappropriate_content' => 'Inappropriate Content',
        'insufficient_details' => 'Insufficient Details',
        'misleading_information' => 'Misleading Information',
        'incorrect_pricing' => 'Incorrect Pricing',
        'poor_image_quality' => 'Poor Image Quality',
        'prohibited_item' => 'Prohibited Item',
        'other' => 'Other',
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

    public static function getRejectionReasons(): array
    {
        return collect(self::$rejectionReasons)
            ->map(fn ($label, $value) => [
                'value' => $value,
                'label' => $label
            ])
            ->values()
            ->toArray();
    }

    // This helper method can be used for validation
    public static function getValidRejectionReasons(): array
    {
        return array_keys(self::$rejectionReasons);
    }
}
