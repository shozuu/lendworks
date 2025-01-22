<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TakedownReason extends Model
{
    protected $fillable = ['code', 'label', 'description'];

    public function listings()
    {
        return $this->belongsToMany(Listing::class, 'listing_takedowns')
            ->using(ListingTakedown::class)
            ->withPivot(['custom_feedback', 'admin_id', 'created_at'])
            ->withTimestamps();
    }
}
