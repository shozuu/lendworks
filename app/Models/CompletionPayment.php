<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompletionPayment extends Model
{
    protected $fillable = [
        'rental_request_id',
        'type', // 'lender_payment' or 'deposit_refund'
        'amount',
        'proof_path',
        'admin_id',
        'reference_number',
        'notes',
        'processed_at'
    ];

    protected $casts = [
        'amount' => 'integer',
        'processed_at' => 'datetime'
    ];

    public function rental_request()
    {
        return $this->belongsTo(RentalRequest::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
