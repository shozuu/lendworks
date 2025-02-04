<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class RentalRequestCancellation extends Pivot
{
    protected $table = 'rental_request_cancellations';

    public function rentalRequest()
    {
        return $this->belongsTo(RentalRequest::class);
    }

    public function cancellationReason()
    {
        return $this->belongsTo(RentalCancellationReason::class);
    }
}
