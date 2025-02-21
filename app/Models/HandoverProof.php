<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HandoverProof extends Model
{
    protected $fillable = [
        'rental_request_id',
        'type',
        'proof_path',
        'submitted_by'
    ];

    public function rentalRequest()
    {
        return $this->belongsTo(RentalRequest::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
