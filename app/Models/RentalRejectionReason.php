<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentalRejectionReason extends Model
{
    protected $fillable = ['code', 'label', 'description', 'action_needed'];

    public function rentalRequests()
    {
        return $this->belongsToMany(RentalRequest::class, 'rental_request_rejections')
            ->using(RentalRequestRejection::class)
            ->withPivot(['custom_feedback', 'lender_id'])
            ->withTimestamps();
    }

    public function rentalRequestRejections()
    {
        return $this->hasMany(RentalRequestRejection::class);
    }
}
