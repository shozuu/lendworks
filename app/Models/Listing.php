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
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ListingImage::class);
    }

    // always include scope in func name to trigger laravel query builder
    // to use the function in controller, use it as defined but after the word scope

    public function scopeFilter($query, array $filters) {

        // search input query
        if ($filters['search'] ?? false) {
            // wrap in a cb func to treat and return them as one query if chaining where is needed
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . request('search') . '%')
                ->orWhere('desc', 'like', '%' . request('search') . '%');
                // add more search params 
            });
        }

        // username click query
        // for user's listings
        if ($filters['user_id'] ?? false) {
            $query->where('user_id', request('user_id'));
        }
    } 
}
