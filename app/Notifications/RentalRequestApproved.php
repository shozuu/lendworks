<?php

namespace App\Notifications;

use App\Models\RentalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class RentalRequestApproved extends Notification
{
    use Queueable;

    public function __construct(public RentalRequest $rentalRequest)
    {}

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your rental request has been approved!')
            ->line('Great news! Your rental request for "' . $this->rentalRequest->listing->title . '" has been approved.')
            ->line('Please proceed with the payment to confirm your booking.')
            ->action('View Rental Details', route('my-rentals'))
            ->line('Thank you for using LendWorks!');
    }

    public function toArray($notifiable)
    {
        return [
            'rental_request_id' => $this->rentalRequest->id,
            'listing_id' => $this->rentalRequest->listing_id,
            'message' => 'Your rental request has been approved'
        ];
    }
}
