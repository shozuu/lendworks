<?php

namespace App\Notifications;

use App\Models\Listing;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ListingRejected extends Notification
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
            ->subject('Your Listing Was Not Approved')
            ->line('Your listing could not be approved.')
            ->line('Title: ' . $this->listing->title)
            ->line('Reason: ' . $this->listing->rejection_reason)
            ->action('Edit Listing', route('listing.edit', $this->listing))
            ->line('Please update your listing according to the feedback and submit it again for review.');
    }

    public function toArray($notifiable)
    {
        return [
            'listing_id' => $this->listing->id,
            'title' => $this->listing->title,
            'message' => 'Your listing was not approved.',
            'reason' => $this->listing->rejection_reason
        ];
    }
}
