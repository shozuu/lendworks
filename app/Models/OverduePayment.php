<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OverduePayment extends Model
{
    protected $fillable = [
        'rental_request_id',
        'amount',
        'reference_number',
        'proof_path',
        'verified_at',
        'verified_by'
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'amount' => 'decimal:2'
    ];

    public function rental_request()
    {
        return $this->belongsTo(RentalRequest::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
