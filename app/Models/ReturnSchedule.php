<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnSchedule extends Model
{
    protected $fillable = [
        'rental_request_id',
        'return_datetime',
        'is_selected',
        'is_confirmed'
    ];

    protected $casts = [
        'return_datetime' => 'datetime',
        'is_selected' => 'boolean',
        'is_confirmed' => 'boolean'
    ];

    public function rental_request()
    {
        return $this->belongsTo(RentalRequest::class);
    }
}
