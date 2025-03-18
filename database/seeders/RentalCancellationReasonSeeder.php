<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RentalCancellationReasonSeeder extends Seeder
{
    public function run()
    {
        $reasons = [
            // Renter reasons
            [
                'code' => 'found_alternative',
                'label' => 'Found Alternative Item',
                'description' => 'Found a better or more suitable item elsewhere',
                'role' => 'renter'
            ],
            [
                'code' => 'changed_plans',
                'label' => 'Change of Plans',
                'description' => 'Project or plans have changed',
                'role' => 'renter'
            ],
            [
                'code' => 'cost_concerns',
                'label' => 'Cost Concerns',
                'description' => 'Budget constraints or found better pricing',
                'role' => 'renter'
            ],
            [
                'code' => 'timing_issues',
                'label' => 'Timing Issues',
                'description' => 'Schedule conflicts or timing no longer works',
                'role' => 'renter'
            ],
            [
                'code' => 'location_distance',
                'label' => 'Location/Distance',
                'description' => 'Item location is too far or inconvenient',
                'role' => 'renter'
            ],
            // Lender reasons
            [
                'code' => 'item_unavailable',
                'label' => 'Item No Longer Available',
                'description' => 'Item is damaged, lost, or otherwise unavailable',
                'role' => 'lender'
            ],
            [
                'code' => 'maintenance_required',
                'label' => 'Maintenance Required',
                'description' => 'Item requires unexpected maintenance or repairs',
                'role' => 'lender'
            ],
            [
                'code' => 'schedule_conflict',
                'label' => 'Schedule Conflict',
                'description' => 'Unexpected scheduling conflict',
                'role' => 'lender'
            ],
            // Common reasons
            [
                'code' => 'communication_issues',
                'label' => 'Communication Issues',
                'description' => 'Difficulties in communication between parties',
                'role' => 'both'
            ],
            [
                'code' => 'other',
                'label' => 'Other',
                'description' => 'Other reasons not listed',
                'role' => 'both'
            ],
        ];

        DB::table('rental_cancellation_reasons')->insert($reasons);
    }
}
