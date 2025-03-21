<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\RentalRequest;

class HandoverActionNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected RentalRequest $rental,
        protected string $action
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $messages = [
            'handover_submitted' => 'The lender has initiated item handover - Please confirm receipt',
            'handover_confirmed' => 'Item handover has been confirmed by both parties',
            'return_submitted' => 'The renter has initiated item return - Please confirm receipt',
            'return_confirmed' => 'Item return has been confirmed by both parties'
        ];

        return [
            'type' => 'handover',
            'rental_id' => $this->rental->id,
            'message' => $messages[$this->action] ?? 'Handover status updated',
            'rental_status' => $this->rental->status // Add rental status
        ];
    }
}
