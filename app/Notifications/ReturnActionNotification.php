<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\RentalRequest;

class ReturnActionNotification extends Notification
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
            'return_initiated' => 'Return process has been initiated',
            'return_submitted' => 'Item has been returned - Awaiting confirmation',
            'return_receipt_confirmed' => 'Return receipt has been confirmed - Pending final inspection',
            'return_finalized' => 'Return has been finalized - Awaiting final payments'
        ];

        return [
            'type' => 'handover',
            'rental_id' => $this->rental->id,
            'message' => $messages[$this->action] ?? 'Return status updated',
            'rental_status' => $this->rental->status
        ];
    }
}
