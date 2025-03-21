<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\PaymentRequest;

class PaymentVerified extends Notification
{
    use Queueable;

    public function __construct(public PaymentRequest $payment)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $rental = $this->payment->rental_request;
        return (new MailMessage)
            ->subject('Payment Verified - Ready for Pickup')
            ->greeting('Hi ' . $notifiable->name . '!')
            ->line('Your payment has been verified successfully.')
            ->line('Next Steps:')
            ->line('1. Choose a pickup schedule from the owner\'s availability')
            ->line('2. Meet with the owner at the agreed time and location')
            ->line('3. Verify the item condition before accepting')
            ->action('View Rental Details', route('rental.show', $rental->id))
            ->line('Thank you for using LendWorks!');
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
