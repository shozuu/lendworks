<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\PaymentRequest;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentRejected extends Notification
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
            ->subject('Action Required: Payment Rejected')
            ->greeting('Hi ' . $notifiable->name . '!')
            ->line('Your payment proof has been rejected.')
            ->line('Reason: ' . $this->payment->rejection_feedback)
            ->line('Please submit a new payment with the correct details.')
            ->action('Submit New Payment', route('rental.show', $rental->id))
            ->line('If you need assistance, please contact our support team.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'payment_rejected',
            'payment_id' => $this->payment->id,
            'rental_id' => $this->payment->rental_request_id,
            'message' => 'Your payment proof has been rejected.',
            'feedback' => $this->payment->rejection_feedback
        ];
    }
}
