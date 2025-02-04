<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class RentalRequestCancellation extends Pivot
{
    protected $table = 'rental_request_cancellations';

    protected $with = ['cancellationReason']; 

    public function rentalRequest()
    {
        return $this->belongsTo(RentalRequest::class);
    }

    public function cancellationReason()
    {
        return $this->belongsTo(RentalCancellationReason::class, 'rental_cancellation_reason_id');
    }
}
