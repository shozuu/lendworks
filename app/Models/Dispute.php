<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dispute extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_id',
        'dispute_type_id',
        'reported_by',
        'status',
        'description',
        'resolution',
        'resolved_at'
    ];

    protected $casts = [
        'resolved_at' => 'datetime'
    ];

    public function rental()
    {
        return $this->belongsTo(Rental::class);
    }

    public function disputeType()
    {
        return $this->belongsTo(DisputeType::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function resolve($resolution)
    {
        $this->update([
            'status' => 'resolved',
            'resolution' => $resolution,
            'resolved_at' => now()
        ]);
    }

    public function cancel()
    {
        $this->update(['status' => 'cancelled']);
    }
}
