<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\RentalDispute;

class DisputeStatusUpdated extends Notification
{
    use Queueable;

    public $dispute;

    public function __construct(RentalDispute $dispute)
    {
        $this->dispute = $dispute;
    }

    public function via(): array
    {
        return ['database'];
    }

    public function toArray(): array
    {
        return [
            'dispute_id' => $this->dispute->id,
            'rental_id' => $this->dispute->rental_id,
            'status' => $this->dispute->status,
            'message' => "Dispute status has been updated to {$this->dispute->status}."
        ];
    }
}
