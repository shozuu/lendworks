<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;


class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'selfie_image_path',
        'liveness_image_path',
        'primary_id_image_path',
        'primary_id_type',
        'secondary_id_image_path',
        'secondary_id_type',
        'liveness_verified_at',
        'id_verified_at'
    ];

      protected $casts = [
        'email_verified_at' => 'datetime',
        'id_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }
     public function hasProfile(): bool
    {
        return $this->profile()->exists();
    }

    public function markIdAsVerified(): bool
    {
        return $this->forceFill([
            'id_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    public function hasVerifiedId()
    {
        return !is_null($this->id_verified_at);
    }

    public function getVerificationWarning(): ?string
    {
        if (!$this->hasVerifiedEmail()) {
            return "Your email address is not verified. Some features are limited.";
        }
        
        if (!$this->hasVerifiedId()) {
            return "Your ID is not verified. You cannot rent or list items.";
        }
        
        if (!$this->hasProfile()) {
            return "Please complete your profile to use all features.";
        }
        
        return null;
    }

    public function isFullyVerified()
    {
        return $this->hasVerifiedEmail() && $this->hasVerifiedId();
    }
    
    // Tools listed by user (as lender)
    public function listings() {
        return $this->hasMany(Listing::class);
    }

    // Helper methods
    public function isAdmin(): bool 
    {
        return $this->role === 'admin';
    }

    public function isActive(): bool {
        return $this->status === 'active';
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    public function defaultLocation()
    {
        return $this->locations()->where('is_default', true)->first();
    }

    public function sentRentalRequests() {
        return $this->hasMany(RentalRequest::class, 'renter_id');
    }

    public function receivedRentalRequests() {
        return $this->hasManyThrough(
            RentalRequest::class,
            Listing::class,
            'user_id',
            'listing_id'
        );
    }
}
