<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisputeImage extends Model
{
    protected $fillable = [
        'dispute_id',
        'image_path'
    ];

    public function dispute()
    {
        return $this->belongsTo(RentalDispute::class);
    }
}
