<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompletionPayment extends Model
{
    protected $fillable = [
        'rental_request_id',
        'type',
        'amount',
        'includes_overdue_fee',
        'total_amount',
        'reference_number',
        'proof_path',
        'processed_by',
        'processed_at',
        'notes'
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'includes_overdue_fee' => 'boolean'
    ];

    // Add an accessor to get payment breakdown
    public function getPaymentBreakdownAttribute()
    {
        if ($this->type !== 'lender_payment') {
            return null;
        }

        $rental = $this->rental_request;
        return [
            'base_earnings' => $rental->base_price - $rental->discount - $rental->service_fee,
            'overdue_fee' => $rental->overdue_payment ? $rental->overdue_fee : 0,
            'total' => $this->amount
        ];
    }

    public function rental_request()
    {
        return $this->belongsTo(RentalRequest::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
