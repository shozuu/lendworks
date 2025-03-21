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
        $takedown = $this->listing->latestTakedown;
        $reason = $takedown->takedownReason;
        
        return (new MailMessage)
            ->subject('Important: Your Listing Has Been Taken Down')
            ->greeting('Hi ' . $notifiable->name)
            ->line('We need to inform you that your listing "' . $this->listing->title . '" has been taken down from LendWorks.')
            ->line('Here\'s why this action was taken:')
            ->line($reason->description)
            ->when($takedown->custom_feedback, fn ($mail) => 
                $mail->line('Specific feedback:')
                     ->line($takedown->custom_feedback)
            )
            ->line('What this means:')
            ->line('• Your listing is no longer visible to other users')
            ->line('• The listing has been marked as unavailable')
            ->line('Next steps:')
            ->line('• Review our listing guidelines')
            ->line('• Consider creating a new listing that follows our policies')
            ->action('View Listing Details', route('listing.show', $this->listing));
    }

    public function toArray($notifiable)
    {
        $takedown = $this->listing->latestTakedown;
        
        return [
            'listing_id' => $this->listing->id,
            'title' => $this->listing->title,
            'type' => 'error',
            'icon' => '🚫',
            'message' => 'Listing taken down: ' . $takedown->takedownReason->label,
            'reason' => $takedown->takedownReason->description,
            'custom_feedback' => $takedown->custom_feedback
        ];
    }
}
