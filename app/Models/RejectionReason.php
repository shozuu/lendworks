<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RejectionReason extends Model
{
    protected $fillable = ['code', 'label', 'description', 'action_needed'];

    public function listings()
    {
        return $this->belongsToMany(Listing::class, 'listing_rejections')
            ->withPivot('custom_feedback')
            ->withTimestamps();
    }
}
