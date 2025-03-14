<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepositDeduction extends Model
{
    protected $fillable = [
        'rental_request_id',
        'dispute_id',
        'amount',
        'reason',
        'admin_id'
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function dispute()
    {
        return $this->belongsTo(RentalDispute::class);
    }

    public function rental()
    {
        return $this->belongsTo(RentalRequest::class, 'rental_request_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
