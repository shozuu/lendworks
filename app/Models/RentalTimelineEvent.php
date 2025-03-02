<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentalTimelineEvent extends Model
{
    protected $fillable = [
        'rental_request_id',
        'actor_id',
        'event_type',
        'status',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    public function rental_request()
    {
        return $this->belongsTo(RentalRequest::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
