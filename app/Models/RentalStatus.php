<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentalStatus extends Model
{
    protected $fillable = ['name', 'description'];

    public function rentals()
    {
        return $this->hasMany(Rental::class);
    }
}