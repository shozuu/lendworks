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

    public function recordConfirmation($actorId)
    {
        $schedule = $this;
        $scheduleDate = $this->return_datetime;

        return $this->rental_request->timelineEvents()->create([
            'actor_id' => $actorId,
            'event_type' => 'return_schedule_confirmed',
            'status' => $this->rental_request->status,
            'metadata' => [
                'datetime' => $scheduleDate->format('Y-m-d H:i:s'),
                'day_of_week' => $scheduleDate->format('l'),
                'date' => $scheduleDate->format('Y-m-d'),
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'confirmed_by' => $actorId,
                'confirmation_datetime' => now()->format('Y-m-d H:i:s'),
                'selected_by' => $this->rental_request->renter_id,
                'is_early_return' => $scheduleDate->startOfDay()->lt($this->rental_request->end_date->startOfDay())
            ]
        ]);
    }
}
