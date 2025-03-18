<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HandoverDispute extends Model
{
    // Dispute Types
    const TYPE_LENDER_NO_SHOW = 'lender_no_show';
    const TYPE_RENTER_NO_SHOW = 'renter_no_show';

    // Resolution Types
    const RESOLUTION_APPROVED = 'approved';
    const RESOLUTION_RESCHEDULE = 'reschedule'; 
    const RESOLUTION_REJECTED = 'rejected';

    protected $fillable = [
        'rental_request_id',
        'type',
        'description',
        'proof_path',
        'schedule_id',
        'status',
        'resolved_at',
        'resolved_by',
        'resolution_type',
        'resolution_notes'
    ];

    protected $casts = [
        'resolved_at' => 'datetime'
    ];

    // Add resolution details helper method
    public static function getResolutionDetails(string $type): array 
    {
        if ($type === self::TYPE_LENDER_NO_SHOW) {
            return [
                'title' => 'Lender No-Show Dispute Resolution',
                'conditions' => [
                    'If approved:' => 'Full refund and rental cancellation',
                    'If rescheduled:' => 'Rental period extends by 1 day',
                ],
            ];
        }

        return [
            'title' => 'Renter No-Show Dispute Resolution',
            'conditions' => [
                'If approved:' => 'Rental cancels, 1-day payment kept',
                'If rescheduled:' => 'Original rental duration maintained',
            ],
        ];
    }

    // Add helper method to get resolution message
    public function getResolutionMessage(): string
    {
        if ($this->type === self::TYPE_LENDER_NO_SHOW) {
            return $this->resolution_type === self::RESOLUTION_APPROVED
                ? 'Dispute approved - Rental cancelled with full refund'
                : 'Dispute rejected - New schedule may be selected';
        }

        return $this->resolution_type === self::RESOLUTION_APPROVED
            ? 'Dispute approved - Rental cancelled with partial payment'
            : 'Dispute rejected - New schedule may be selected';
    }

    // Add helper method to handle dispute resolution
    public function handleResolution(string $resolutionType): void 
    {
        if ($resolutionType === self::RESOLUTION_RESCHEDULE) {
            if ($this->type === self::TYPE_LENDER_NO_SHOW) {
                // Extend rental period by 1 day
                $this->rental->extendRentalPeriod(1);
            }
            // Reset pickup schedules for both cases
            $this->rental->resetPickupSchedules();
        }
    }

    // Relationships
    public function rental(): BelongsTo
    {
        return $this->belongsTo(RentalRequest::class, 'rental_request_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(PickupSchedule::class, 'schedule_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
