<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\RentalRequest;

class ScheduleActionNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected RentalRequest $rental,
        protected string $action,
        protected array $scheduleData
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $messages = [
            'pickup_suggested' => 'A new pickup schedule has been suggested - Please review and confirm',
            'pickup_confirmed' => 'Pickup schedule has been confirmed - Proceed with item handover',
            'return_suggested' => 'A new return schedule has been suggested - Please review and confirm',
            'return_confirmed' => 'Return schedule has been confirmed - Please proceed with item return'
        ];

        return [
            'type' => 'schedule',
            'rental_id' => $this->rental->id,
            'message' => $messages[$this->action] ?? 'Schedule updated',
            'schedule_data' => $this->scheduleData,
            'rental_status' => $this->rental->status // Add rental status
        ];
    }
}
