<?php

namespace App\Policies;

use App\Models\Rental;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RentalPolicy
{
    use HandlesAuthorization;

    public function approve(User $user, Rental $rental): bool
    {
        return $rental->listing->user_id === $user->id && $rental->isPending();
    }

    public function start(User $user, Rental $rental): bool
    {
        return $rental->listing->user_id === $user->id && $rental->isApproved();
    }

    public function return(User $user, Rental $rental): bool
    {
        return $rental->renter_id === $user->id && $rental->isActive();
    }

    public function complete(User $user, Rental $rental): bool
    {
        return $rental->listing->user_id === $user->id && 
               $rental->return_at !== null &&
               $rental->rental_status_id === 4;
    }

    public function handover(User $user, Rental $rental): bool 
    {
        return $rental->listing->user_id === $user->id && 
               $rental->rental_status_id === 3 && // Must be in "ready for handover" state
               $rental->payment_status === 'paid' && 
               !$rental->handover_at; // Not already handed over
    }
}
