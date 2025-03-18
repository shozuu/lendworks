<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'first_name',
        'middle_name',
        'last_name',
        'birthdate',
        'gender',
        'civil_status',
        'mobile_number',
        'street_address',
        'barangay',
        'city',
        'province',
        'postal_code',
        'nationality',
        'primary_id_type',
        'secondary_id_type',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}