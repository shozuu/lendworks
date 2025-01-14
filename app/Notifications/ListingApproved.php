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
            ->subject('🎉 Your listing has been approved!')
            ->greeting('Hi ' . $notifiable->name . '!')
            ->line('Great news! Your listing "' . $this->listing->title . '" has been approved and is now live on LendWorks. 🚀')
            ->line('Your item is now visible to all users in your area who might be interested in renting it.')
            ->action('View Your Listing', route('listing.show', $this->listing))
            ->line('Keep an eye on your notifications for any rental requests!')
            ->salutation('Happy lending! 🎊');
    }

    public function toArray($notifiable)
    {
        return [
            'listing_id' => $this->listing->id,
            'title' => $this->listing->title,
            'type' => 'success',
            'icon' => '🎉',
            'message' => 'Your listing is now live!'
        ];
    }
}
