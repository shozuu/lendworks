<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RentalRejectionReasonSeeder extends Seeder
{
    public function run()
    {
        $reasons = [
            [
                'code' => 'unavailable',
                'label' => 'Item Currently Unavailable',
                'description' => 'The item is temporarily unavailable for the requested dates.',
                'action_needed' => 'Please try requesting different dates when the item becomes available again.'
            ],
            [
                'code' => 'dates_conflict',
                'label' => 'Schedule Conflict',
                'description' => 'The requested dates conflict with existing commitments.',
                'action_needed' => 'Please select alternative dates when the item is not already reserved.'
            ],
            [
                'code' => 'rental_period',
                'label' => 'Rental Period Issues',
                'description' => 'The requested rental period is not suitable.',
                'action_needed' => 'Please adjust the rental duration to meet our minimum/maximum rental period requirements.'
            ],
            [
                'code' => 'location_distance',
                'label' => 'Location Distance',
                'description' => 'The delivery/pickup location is too far.',
                'action_needed' => 'Please check if there are similar items available closer to your location.'
            ],
            [
                'code' => 'other',
                'label' => 'Other Reason',
                'description' => 'Other reason (requires explanation)',
                'action_needed' => 'Please review the specific feedback provided and address the concerns mentioned.'
            ],
        ];

        DB::table('rental_rejection_reasons')->insert($reasons);
    }
}