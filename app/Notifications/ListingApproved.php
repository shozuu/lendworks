<?php

namespace App\Notifications;

use App\Models\Listing;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ListingApproved extends Notification
{
    use Queueable;

    public function __construct(public Listing $listing)
    {}

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('Your listing has been approved!')
            ->line('Title: ' . $this->listing->title)
            ->action('View Listing', route('listing.show', $this->listing))
            ->line('Your listing is now visible to all users.');
    }

    public function toArray($notifiable)
    {
        return [
            'listing_id' => $this->listing->id,
            'title' => $this->listing->title,
            'message' => 'Your listing has been approved!'
        ];
    }
}
