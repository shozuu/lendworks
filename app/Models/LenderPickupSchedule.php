<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LenderPickupSchedule extends Model
{
    protected $fillable = [
        'user_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pickup_schedules()
    {
        return $this->hasMany(PickupSchedule::class);
    }

    public function getFormattedTimeSlotAttribute()
    {
        try {
            // Handle cases where times might be stored differently
            $startTime = $this->start_time;
            $endTime = $this->end_time;

            // If the times don't include seconds, add them
            if (strlen($startTime) === 5) $startTime .= ':00';
            if (strlen($endTime) === 5) $endTime .= ':00';

            $start = Carbon::createFromFormat('H:i:s', $startTime)->format('g:i A');
            $end = Carbon::createFromFormat('H:i:s', $endTime)->format('g:i A');
            
            return "{$this->day_of_week} - {$start} to {$end}";
        } catch (\Exception $e) {
            report($e);
            return "{$this->day_of_week} - (Invalid time format)";
        }
    }
}
