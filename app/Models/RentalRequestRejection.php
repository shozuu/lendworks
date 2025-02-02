<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class RentalRequestRejection extends Pivot
{
    protected $table = 'rental_request_rejections';
    
    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function rentalRequest()
    {
        return $this->belongsTo(RentalRequest::class);
    }

    public function rejectionReason()
    {
        return $this->belongsTo(RentalRejectionReason::class, 'rental_rejection_reason_id');
    }

    public function lender()
    {
        return $this->belongsTo(User::class, 'lender_id');
    }

    // Helper method to get formatted rejection details
    public function getFormattedDetails()
    {
        return [
            'reason' => $this->rejectionReason->label,
            'description' => $this->rejectionReason->description,
            'feedback' => $this->custom_feedback,
            'lender_name' => $this->lender->name,
            'rejected_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
