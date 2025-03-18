<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\PaymentRequest;

class PaymentVerified extends Notification
{
    use Queueable;

    public function __construct(public PaymentRequest $payment)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'payment_verified',
            'payment_id' => $this->payment->id,
            'rental_id' => $this->payment->rental_request_id,
            'message' => 'Your payment has been verified.',
        ];
    }
}
