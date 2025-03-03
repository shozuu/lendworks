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

    protected static $eventTypes = [
        'return_initiated',
        'return_schedule_proposed',
        'return_schedule_selected',
        'return_schedule_confirmed',
        'return_submitted',
        'return_confirmed'
    ];

    public static function isValidEventType($type)
    {
        return in_array($type, self::$eventTypes);
    }

    public function rental_request()
    {
        return $this->belongsTo(RentalRequest::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
