<?php

namespace App\Notifications;

use App\Models\RentalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class RentalRequestRejected extends Notification
{
    use Queueable;

    public function __construct(public RentalRequest $rentalRequest)
    {}

    public function via($notifiable)
    {
        return ['database'];
    }

    // public function toMail($notifiable)
    // {
    //     return (new MailMessage)
    //         ->subject('Your rental request has been rejected')
    //         ->line('We regret to inform you that your rental request for "' . $this->rentalRequest->listing->title . '" has been rejected.')
    //         ->line('You can try requesting another item or contact the owner for more information.')
    //         ->action('View Rental Details', route('my-rentals'))
    //         ->line('Thank you for using LendWorks!');
    // }

    public function toArray($notifiable)
    {
        return [
            'rental_request_id' => $this->rentalRequest->id,
            'listing_id' => $this->rentalRequest->listing_id,
            'message' => 'Your rental request has been rejected'
        ];
    }
}
