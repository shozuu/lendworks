<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RentalCancellationReasonSeeder extends Seeder
{
    public function run()
    {
        $reasons = [
            [
                'code' => 'found_alternative',
                'label' => 'Found Alternative Item',
                'description' => 'Found a better or more suitable item elsewhere'
            ],
            [
                'code' => 'changed_plans',
                'label' => 'Change of Plans',
                'description' => 'Project or plans have changed'
            ],
            [
                'code' => 'cost_concerns',
                'label' => 'Cost Concerns',
                'description' => 'Budget constraints or found better pricing'
            ],
            [
                'code' => 'timing_issues',
                'label' => 'Timing Issues',
                'description' => 'Schedule conflicts or timing no longer works'
            ],
            [
                'code' => 'location_distance',
                'label' => 'Location/Distance',
                'description' => 'Item location is too far or inconvenient'
            ],
            [
                'code' => 'communication_issues',
                'label' => 'Communication Issues',
                'description' => 'Difficulties in communicating with the lender'
            ],
            [
                'code' => 'other',
                'label' => 'Other',
                'description' => 'Other reasons not listed'
            ],
        ];

        DB::table('rental_cancellation_reasons')->insert($reasons);
    }
}
