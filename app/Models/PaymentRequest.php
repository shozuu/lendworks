<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentRequest extends Model
{
    protected $fillable = [
        'rental_request_id',
        'reference_number',
        'payment_proof_path',
        'status',
        'admin_feedback',
        'verified_by',
        'verified_at',
        'type',    // 'regular' or 'overdue'
        'amount'   // stores the payment amount
    ];

    protected $casts = [
        'verified_at' => 'datetime'
    ];

    public function rentalRequest()
    {
        return $this->belongsTo(RentalRequest::class);
    }

    public function verifiedByAdmin()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
