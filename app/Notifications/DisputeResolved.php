<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\RentalDispute;

class DisputeResolved extends Notification
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
        $message = $this->dispute->resolution_type === 'deposit_deducted'
            ? "Your dispute has been resolved. A deduction of {$this->dispute->deposit_deduction} has been applied to the deposit."
            : "Your dispute has been resolved. No deposit deduction was necessary.";

        return [
            'dispute_id' => $this->dispute->id,
            'rental_id' => $this->dispute->rental_id,
            'resolution_type' => $this->dispute->resolution_type,
            'verdict' => $this->dispute->verdict,
            'deposit_deduction' => $this->dispute->deposit_deduction,
            'message' => $message
        ];
    }
}
