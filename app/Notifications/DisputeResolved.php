<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\RentalDispute;
use App\Services\DisputeMessageService;

class DisputeResolved extends Notification
{
    use Queueable;

    public $dispute;
    public $isLender;

    public function __construct(RentalDispute $dispute, bool $isLender)
    {
        $this->dispute = $dispute;
        $this->isLender = $isLender;
    }

    public function via(): array
    {
        return ['database'];
    }

    public function toArray(): array
    {
        $baseData = [
            'dispute_id' => $this->dispute->id,
            'rental_id' => $this->dispute->rental_id,
            'resolution_type' => $this->dispute->resolution_type,
            'verdict' => $this->dispute->verdict,
            'verdict_notes' => $this->dispute->verdict_notes,
            'resolved_at' => $this->dispute->resolved_at->format('Y-m-d H:i:s'),
            'resolved_by' => $this->dispute->resolvedBy->name,
        ];

        if ($this->dispute->resolution_type === 'deposit_deducted') {
            $deductionInfo = DisputeMessageService::getDeductionMessage($this->dispute);
            
            return array_merge($baseData, [
                'title' => $this->isLender ? 
                    'Dispute Resolution: Claim Approved' : 
                    'Dispute Resolution: Deposit Deduction Applied',
                'message' => $this->isLender ? 
                    $deductionInfo['financial_impact']['lender'] : 
                    $deductionInfo['financial_impact']['renter'],
                'deduction_details' => $deductionInfo['deduction_details'],
                'transaction_details' => [
                    'original_amount' => $this->dispute->rental->deposit_fee,
                    'deducted_amount' => $this->dispute->deposit_deduction,
                    'remaining_amount' => $this->dispute->rental->remaining_deposit,
                    'payment_status' => 'pending_admin_processing'
                ]
            ]);
        }

        $rejectionMessages = DisputeMessageService::getRejectionMessage();
        
        return array_merge($baseData, [
            'title' => $this->isLender ? 
                'Dispute Resolution: Claim Rejected' : 
                'Dispute Resolution: Deposit Protected',
            'message' => $this->isLender ? 
                $rejectionMessages['lender'] : 
                $rejectionMessages['renter'],
            'deposit_details' => [
                'amount' => $this->dispute->rental->deposit_fee,
                'status' => 'to_be_refunded'
            ]
        ]);
    }
}
