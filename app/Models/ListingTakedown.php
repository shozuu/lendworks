<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ListingTakedown extends Pivot
{
    protected $table = 'listing_takedowns';

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function takedownReason()
    {
        return $this->belongsTo(TakedownReason::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
