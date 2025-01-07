<?php

namespace App\Notifications;

use App\Models\Listing;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ListingTakenDown extends Notification
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
        $reason = ListingRejectionReason::from($this->listing->rejection_reason);
        
        return (new MailMessage)
            ->subject('Important: Your Listing Has Been Taken Down')
            ->line('Your listing has been taken down by our moderation team.')
            ->line("Listing: {$this->listing->title}")
            ->line('Reason for takedown:')
            ->line($reason->label())
            ->line('What you need to do:')
            ->line($reason->message())
            ->line('• Your listing is no longer visible to other users')
            ->line('• The listing has been marked as unavailable')
            ->line('What you can do:')
            ->line('• Review our listing guidelines')
            ->line('• Make the necessary changes to comply with our policies')
            ->line('• Create a new listing that follows our guidelines')
            ->action('View Listing Details', route('listing.show', $this->listing))
            ->line('If you believe this was done in error, please contact our support team for assistance.');
    }

    public function toArray($notifiable)
    {
        return [
            'listing_id' => $this->listing->id,
            'title' => 'Listing Taken Down',
            'message' => "Your listing \"{$this->listing->title}\" has been taken down. Reason: {$this->listing->rejection_reason}"
        ];
    }
}
