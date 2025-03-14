<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LenderEarningsAdjustment extends Model
{
    protected $fillable = [
        'rental_request_id',
        'type',
        'amount',
        'description',
        'reference_id'
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function rental()
    {
        return $this->belongsTo(RentalRequest::class, 'rental_request_id');
    }
}
