<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisputeType extends Model
{
    protected $fillable = ['name', 'description'];

    public function disputes()
    {
        return $this->hasMany(Dispute::class);
    }
}
