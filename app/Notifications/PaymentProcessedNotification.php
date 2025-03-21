<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\RentalRequest;

class PaymentProcessedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected RentalRequest $rental,
        protected string $type,
        protected float $amount
    ) {}

    public function via($notifiable): array
    {
        return $this->type === 'deposit_refund' ? ['mail', 'database'] : ['database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Security Deposit Refund Processed')
            ->greeting('Hi ' . $notifiable->name . '!')
            ->line('Good news! Your security deposit has been refunded.')
            ->line('Amount: ₱' . number_format($this->amount, 2))
            ->action('View Details', route('rental.show', $this->rental->id))
            ->line('The refund should reflect in your account within 1-3 business days.');
    }

    public function toArray($notifiable): array
    {
        $messages = [
            'deposit_refund' => 'Your security deposit has been refunded',
            'lender_payment' => 'Your rental payment has been processed'
        ];

        return [
            'type' => 'payment',
            'rental_id' => $this->rental->id,
            'message' => $messages[$this->type] ?? 'Payment processed',
            'amount' => $this->amount
        ];
    }
}
