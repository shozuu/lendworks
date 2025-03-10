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

    protected static $metadataStructure = [
        'return_initiated' => [
            'rental_end_date',
            'is_early_return',
            'initiated_by',
            'days_from_end',
            'return_reason'
        ],
        'return_schedule_selected' => [
            'datetime',
            'day_of_week',
            'date',
            'start_time',
            'end_time',
            'selected_by',
            'days_from_end',
            'is_early_return'
        ],
        'return_schedule_confirmed' => [
            'datetime',
            'day_of_week',
            'date',
            'start_time',
            'end_time',
            'confirmed_by',
            'confirmation_datetime',
            'is_early_return'
        ],
        'return_submitted' => [
            'proof_path',
            'submitted_by',
            'submission_datetime'
        ],
        'return_receipt_confirmed' => [
            'proof_path',
            'confirmed_by',
            'confirmation_datetime'
        ],
        'rental_completed' => [
            'completed_by',
            'completion_datetime',
            'rental_duration',
            'actual_return_date'
        ],
        'lender_payment_processed' => [
            'amount',
            'reference_number',
            'proof_path',
            'processed_by',
            'processed_at'
        ],
        'deposit_refund_processed' => [
            'amount',
            'reference_number',
            'proof_path',
            'processed_by',
            'processed_at'
        ]
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
