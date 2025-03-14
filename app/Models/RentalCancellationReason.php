<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentalCancellationReason extends Model
{
    protected $fillable = [
        'code',
        'label',
        'description',
        'role'
    ];

    protected $casts = [
        'role' => 'string'
    ];

    public function rentalRequests()
    {
        return $this->belongsToMany(RentalRequest::class, 'rental_request_cancellations')
            ->using(RentalRequestCancellation::class)
            ->withPivot(['custom_feedback'])
            ->withTimestamps();
    }

    public function rentalRequestCancellations()
    {
        return $this->hasMany(RentalRequestCancellation::class);
    }
}
