<?php

namespace Database\Seeders;

use App\Models\RejectionReason;
use Illuminate\Database\Seeder;

class RejectionReasonSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = [
            [
                'code' => 'insufficient_details',
                'label' => 'Insufficient Details',
                'description' => 'The listing lacks important details that renters need to make informed decisions',
                'action_needed' => 'Please add more specific information about the item, including its condition, specifications, and any usage requirements or restrictions.'
            ],
            [
                'code' => 'poor_quality_images',
                'label' => 'Poor Quality Images',
                'description' => 'The images are unclear, too dark, blurry, or don\'t adequately show the item',
                'action_needed' => 'Please upload clear, well-lit photos that show the item from multiple angles. Ensure the main image clearly shows the item.'
            ],
            [
                'code' => 'misleading_information',
                'label' => 'Misleading Information',
                'description' => 'The listing contains information that appears to be inaccurate or misleading',
                'action_needed' => 'Please review and correct any inaccuracies in the title, description, or specifications to ensure they match the actual item.'
            ],
            [
                'code' => 'inappropriate_pricing',
                'label' => 'Inappropriate Pricing',
                'description' => 'The rental price or item value seems unreasonable or inconsistent with similar items',
                'action_needed' => 'Please adjust the pricing to better reflect market rates for similar items. Consider researching comparable listings for guidance.'
            ],
            [
                'code' => 'inappropriate_content',
                'label' => 'Inappropriate Content',
                'description' => 'The listing contains inappropriate language, images, or prohibited content',
                'action_needed' => 'Please remove any inappropriate content and ensure the listing follows our community guidelines.'
            ],
            [
                'code' => 'duplicate_listing',
                'label' => 'Duplicate Listing',
                'description' => 'This appears to be a duplicate of an existing listing',
                'action_needed' => 'Please remove duplicate listings or combine them into a single listing. Multiple listings of the same item are not allowed.'
            ],
            [
                'code' => 'prohibited_item',
                'label' => 'Prohibited Item',
                'description' => 'This item is not allowed on our platform as per our terms of service',
                'action_needed' => 'Please review our prohibited items list in our terms of service. This type of item cannot be listed on our platform.'
            ],
            [
                'code' => 'safety_concerns',
                'label' => 'Safety Concerns',
                'description' => 'The item or listing raises safety concerns',
                'action_needed' => 'Please ensure the item meets all safety requirements and include any necessary safety instructions or certifications.'
            ],
            [
                'code' => 'other',
                'label' => 'Other',
                'description' => 'The listing requires attention for reasons not covered by other categories',
                'action_needed' => 'Please review the specific feedback provided below and make the necessary changes.'
            ],
        ];

        foreach ($reasons as $reason) {
            RejectionReason::create($reason);
        }
    }
}
