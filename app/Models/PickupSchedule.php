<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PickupSchedule extends Model
{
    protected $fillable = [
        'rental_request_id',
        'pickup_datetime',
        'is_selected'
    ];

    protected $casts = [
        'pickup_datetime' => 'datetime',
        'is_selected' => 'boolean'
    ];

    public function rental_request()
    {
        return $this->belongsTo(RentalRequest::class);
    }
}
