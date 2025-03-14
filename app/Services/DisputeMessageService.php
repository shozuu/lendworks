<?php

namespace App\Services;

class DisputeMessageService
{
    public static function getFormattedAmount($amount)
    {
        return '₱' . number_format($amount, 2);
    }

    public static function getDeductionMessage($dispute)
    {
        $amount = self::getFormattedAmount($dispute->deposit_deduction);
        $remaining = self::getFormattedAmount($dispute->rental->remaining_deposit);
        
        return [
            'deduction_details' => [
                'amount' => $amount,
                'reason' => $dispute->deposit_deduction_reason,
                'original_deposit' => self::getFormattedAmount($dispute->rental->deposit_fee),
                'remaining_deposit' => $remaining
            ],
            'financial_impact' => [
                'lender' => "You will receive {$amount} from the security deposit.",
                'renter' => "A deduction of {$amount} has been applied. Remaining deposit: {$remaining}"
            ]
        ];
    }

    public static function getRejectionMessage()
    {
        return [
            'lender' => "Your dispute claim has been rejected. The security deposit will be returned to the renter in full.",
            'renter' => "The dispute has been resolved in your favor. Your security deposit will be refunded in full."
        ];
    }
}
