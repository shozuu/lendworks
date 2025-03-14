<?php

namespace App\Notifications;

use App\Models\RentalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewRentalRequest extends Notification
{
    use Queueable;

    public function __construct(protected RentalRequest $rentalRequest)
    {
        // Ensure the rental request has all required relationships loaded
        if (!$rentalRequest->relationLoaded('renter') || !$rentalRequest->relationLoaded('listing')) {
            $rentalRequest->load(['renter', 'listing']);
        }
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    // public function toMail($notifiable)
    // {
    //     $renter = $this->rentalRequest->renter;
    //     $listing = $this->rentalRequest->listing;
    //     $startDate = $this->rentalRequest->start_date->format('M d, Y');
    //     $endDate = $this->rentalRequest->end_date->format('M d, Y');
        
    //     return (new MailMessage)
    //         ->subject('📋 New Rental Request Received')
    //         ->greeting('Hello ' . $notifiable->name . '!')
    //         ->line("You have received a new rental request for your listing \"{$listing->title}\"")
    //         ->line("Request Details:")
    //         ->line("• Renter: {$renter->name}")
    //         ->line("• Duration: {$startDate} to {$endDate}")
    //         ->line("• Total Amount: ₱" . number_format($this->rentalRequest->total_price, 2))
    //         ->action('Review Request', route('lender-dashboard'))
    //         ->line('Please review and respond to this request as soon as possible.')
    //         ->salutation('Thank you for using LendWorks! 🛠️');
    // }

    public function toArray($notifiable)
    {
        return [
            'type' => 'rental_request',
            'icon' => '📋',
            'title' => 'New Rental Request',
            'message' => "New request from {$this->rentalRequest->renter->name} for {$this->rentalRequest->listing->title}",
            'rental_request_id' => $this->rentalRequest->id,
            'listing_id' => $this->rentalRequest->listing_id,
            'renter_id' => $this->rentalRequest->renter_id,
            'created_at' => now()->toISOString()
        ];
    }
}
