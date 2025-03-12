<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\RentalRequest;

class RentalRequestApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public $rentalRequest;

    public function __construct(RentalRequest $rentalRequest)
    {
        $this->rentalRequest = $rentalRequest;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    // public function toMail($notifiable)
    // {
    //     $quantityMessage = $this->rentalRequest->quantity_approved < $this->rentalRequest->quantity_requested
    //         ? " (Note: {$this->rentalRequest->quantity_approved} out of {$this->rentalRequest->quantity_requested} requested units approved)"
    //         : "";

    //     return (new MailMessage)
    //         ->subject('Rental Request Approved')
    //         ->line("Your rental request has been approved{$quantityMessage}!")
    //         ->line("Total amount to pay: " . number_format($this->rentalRequest->total_price, 2))
    //         ->action('View Details', route('rental.show', $this->rentalRequest->id))
    //         ->line('Please proceed with the payment to confirm your rental.');
    // }

    public function toArray($notifiable)
    {
        return [
            'rental_request_id' => $this->rentalRequest->id,
            'message' => 'Your rental request has been approved',
            'quantity_approved' => $this->rentalRequest->quantity_approved,
            'quantity_requested' => $this->rentalRequest->quantity_requested,
            'adjusted_total' => $this->rentalRequest->total_price,
        ];
    }
}
