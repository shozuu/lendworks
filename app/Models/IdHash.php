<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdHash extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'primary_id_hash',
        'primary_id_type',
        'secondary_id_hash',
        'secondary_id_type',
        'verified_at',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'verified_at' => 'datetime',
    ];
    
    /**
     * Get the user that owns the ID hash.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}