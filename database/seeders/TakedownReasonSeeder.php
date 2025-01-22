<?php

namespace Database\Seeders;

use App\Models\TakedownReason;
use Illuminate\Database\Seeder;

class TakedownReasonSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = [
            [
                'code' => 'policy_violation',
                'label' => 'Policy Violation',
                'description' => 'The listing violates one or more platform policies or terms of service.'
            ],
            [
                'code' => 'fraudulent_activity',
                'label' => 'Fraudulent Activity',
                'description' => 'The listing is suspected of being fraudulent or misleading.'
            ],
            [
                'code' => 'inappropriate_content',
                'label' => 'Inappropriate Content',
                'description' => 'The listing contains prohibited or inappropriate language, images, or items.'
            ],
            [
                'code' => 'safety_risks',
                'label' => 'Safety Risks',
                'description' => 'The listing raises safety concerns for renters or others.'
            ],
            [
                'code' => 'prohibited_item',
                'label' => 'Prohibited Item',
                'description' => 'The item is not allowed on the platform as per the terms of service.'
            ],
            [
                'code' => 'other',
                'label' => 'Other',
                'description' => 'The listing requires attention for reasons not covered by other categories.'
            ],
        ];

        foreach ($reasons as $reason) {
            TakedownReason::create($reason);
        }
    }
}
