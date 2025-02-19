<?php

namespace App\Policies;

use App\Models\PaymentRequest;
use App\Models\User;

class PaymentRequestPolicy
{
    public function verify(User $user): bool
    {
        return $user->is_admin;
    }

    public function reject(User $user): bool
    {
        return $user->is_admin;
    }

    public function submit(User $user, PaymentRequest $paymentRequest): bool
    {
        return $user->id === $paymentRequest->rentalRequest->renter_id;
    }

    public function view(User $user, PaymentRequest $paymentRequest): bool
    {
        return $user->is_admin || 
               $user->id === $paymentRequest->rentalRequest->renter_id ||
               $user->id === $paymentRequest->rentalRequest->listing->user_id;
    }
}
