<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnSchedule extends Model
{
    protected $fillable = [
        'rental_request_id',
        'return_datetime',
        'is_selected',
        'is_confirmed',
        'start_time',
        'end_time',
        'is_suggested',
        'lender_pickup_schedule_id'
    ];

    protected $casts = [
        'return_datetime' => 'datetime',
        'is_selected' => 'boolean',
        'is_confirmed' => 'boolean',
        'is_suggested' => 'boolean'
    ];

    public function rental_request()
    {
        return $this->belongsTo(RentalRequest::class);
    }
}
