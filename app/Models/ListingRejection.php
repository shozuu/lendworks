<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ListingRejection extends Pivot
{
    protected $table = 'listing_rejections';

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function rejectionReason()
    {
        return $this->belongsTo(RejectionReason::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
