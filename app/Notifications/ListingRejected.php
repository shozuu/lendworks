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
        return ['database'];
    }

//     public function toMail($notifiable)
//     {
//         $rejection = $this->listing->latestRejection;
//         $reason = $rejection->rejectionReason;

//         return (new MailMessage)
//             ->subject('Update needed for your listing')
//             ->greeting('Hi ' . $notifiable->name)
//             ->line('Thank you for listing your item on LendWorks. We\'ve reviewed "' . $this->listing->title . '" and noticed some areas that need attention.')
//             ->line('Here\'s what needs to be addressed:')
//             ->line($reason->description)
//             ->when($rejection->custom_feedback, fn ($mail) => 
//                 $mail->line('Specific feedback:')
//                      ->line($rejection->custom_feedback)
//             )
//             ->line('What you need to do:')
//             ->line($reason->action_needed)
//             ->action('Update Your Listing', route('listing.edit', $this->listing))
//             ->line('Once you\'ve made the necessary changes, your listing will be reviewed again automatically.')
//             ->line('If you need any clarification or assistance, feel free to reach out to our support team.')
//             ->salutation('Best regards,
// The LendWorks Team');
//     }

    public function toArray($notifiable)
    {
        $rejection = $this->listing->latestRejection;
        
        return [
            'listing_id' => $this->listing->id,
            'title' => $this->listing->title,
            'type' => 'error',
            'icon' => '⚠️',
            'message' => 'Action required: ' . $rejection->rejectionReason->label,
            'reason' => $rejection->rejectionReason->description,
            'custom_feedback' => $rejection->custom_feedback
        ];
    }
}
