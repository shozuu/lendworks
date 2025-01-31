<?php

namespace App\Notifications;

use App\Models\RentalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewRentalRequest extends Notification
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
        $renter = $this->rentalRequest->renter;
        
        return (new MailMessage)
            ->subject('New Rental Request Received')
            ->line("You've received a new rental request from {$renter->name} for your item \"{$this->rentalRequest->listing->title}\"")
            ->line("Rental Period: " . $this->rentalRequest->start_date->format('M d, Y') . " to " . $this->rentalRequest->end_date->format('M d, Y'))
            ->action('View Request', route('my-rentals'))
            ->line('Please respond to this request as soon as possible.');
    }

    public function toArray($notifiable)
    {
        return [
            'rental_request_id' => $this->rentalRequest->id,
            'listing_id' => $this->rentalRequest->listing_id,
            'renter_id' => $this->rentalRequest->renter_id,
            'message' => "New rental request from {$this->rentalRequest->renter->name}"
        ];
    }
}
