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
        'return_confirmed',
        'lender_payment_processed',
        'deposit_refund_processed'
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
            'selected_by',
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
        ],
        'overdue_payment_submitted' => [
            'amount',
            'reference_number',
            'proof_path',
            'submitted_by',
            'submission_datetime'
        ],
        'overdue_payment_verified' => [
            'amount',
            'reference_number',
            'proof_path',
            'verified_by',
            'verified_at'
        ],
        'overdue_payment_rejected' => [
            'amount',
            'reference_number',
            'feedback',
            'rejected_by',
            'rejected_at'
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

    public static function recordOverduePayment($rentalRequest, $paymentData, $eventType, $actorId)
    {
        return static::create([
            'rental_request_id' => $rentalRequest->id,
            'actor_id' => $actorId,
            'event_type' => $eventType,
            'status' => $rentalRequest->status,
            'metadata' => [
                'amount' => $rentalRequest->overdue_fee, // Use overdue_fee from rental request
                'reference_number' => $paymentData['reference_number'],
                'proof_path' => $paymentData['proof_path'] ?? null,
                'submitted_by' => $actorId,
                'submission_datetime' => now(),
            ]
        ]);
    }

    // Add these methods to help with log categorization
    public static function getSystemEvents()
    {
        return [
            'status_updated',
            'rental_approved',
            'rental_rejected',
            'dispute_raised',
            'rental_cancelled'
        ];
    }

    public static function getUserEvents()
    {
        return [
            'rental_created',
            'payment_submitted',
            'handover_completed',
            'return_initiated',
            'return_submitted',
            'return_schedule_selected',
            'return_schedule_confirmed'
        ];
    }

    public static function getAdminEvents()
    {
        return [
            'payment_verified',
            'payment_rejected',
            'dispute_resolved',
            'rental_completed',
            'deposit_refunded',
            'lender_paid'
        ];
    }

    public static function getErrorEvents()
    {
        return [
            'payment_failed',
            'verification_failed',
            'system_error'
        ];
    }

    public function scopeOfType($query, $type)
    {
        switch ($type) {
            case 'system':
                return $query->whereIn('event_type', self::getSystemEvents());
            case 'user':
                return $query->whereIn('event_type', self::getUserEvents());
            case 'admin':
                return $query->whereIn('event_type', self::getAdminEvents());
            case 'error':
                return $query->whereIn('event_type', self::getErrorEvents());
            default:
                return $query;
        }
    }
}
