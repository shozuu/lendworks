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
        'old_proof_path', // Changed from proof_path to old_proof_path
        'status',
        'resolution_type',
        'verdict',
        'verdict_notes',
        'deposit_deduction',
        'deposit_deduction_reason',
        'resolved_at',
        'resolved_by',
        'raised_by',
        'proof_photos'  // Add this new field
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

    public function images()
    {
        return $this->hasMany(DisputeImage::class, 'dispute_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'resolved');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }
}
