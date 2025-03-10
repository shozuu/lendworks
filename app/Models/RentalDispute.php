<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalDispute extends Model
{
    protected $fillable = [
        'rental_request_id',
        'reason',
        'description',
        'proof_path',
        'status',
        'raised_by',
        'verdict',
        'verdict_notes',
        'resolved_at',
        'resolved_by',
        'deposit_deduction',  // Add this
        'deposit_deduction_reason',  // Add this
        'resolution_type'  // Add this: 'deposit_deducted' or 'rejected'
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'deposit_deduction' => 'integer',
    ];

    public function rental(): BelongsTo
    {
        return $this->belongsTo(RentalRequest::class, 'rental_request_id');
    }

    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
