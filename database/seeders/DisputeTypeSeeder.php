<?php

namespace Database\Seeders;

use App\Models\DisputeType;
use Illuminate\Database\Seeder;

class DisputeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Damaged Item',
                'description' => 'The rented item was returned with damage'
            ],
            [
                'name' => 'Late Return',
                'description' => 'The item was not returned on the agreed date'
            ],
            [
                'name' => 'Item Not As Described',
                'description' => 'The item condition/features did not match the listing'
            ],
            [
                'name' => 'No Show',
                'description' => 'One party did not show up for pickup/return'
            ],
            [
                'name' => 'Payment Issue',
                'description' => 'Issues related to payment or refunds'
            ]
        ];

        foreach ($types as $type) {
            DisputeType::create($type);
        }
    }
}
