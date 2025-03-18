<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PickupSchedule extends Model
{
    protected $fillable = [
        'rental_request_id',
        'lender_pickup_schedule_id',
        'pickup_datetime',
        'is_selected',
        'is_confirmed',
        'start_time',
        'end_time'
    ];

    protected $casts = [
        'pickup_datetime' => 'datetime',
        'is_selected' => 'boolean'
    ];

    public function rental_request()
    {
        return $this->belongsTo(RentalRequest::class);
    }

    public function lender_pickup_schedule()
    {
        return $this->belongsTo(LenderPickupSchedule::class);
    }
}
