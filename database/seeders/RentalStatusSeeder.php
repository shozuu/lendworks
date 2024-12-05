<?php

namespace Database\Seeders;

use App\Models\RentalStatus;
use Illuminate\Database\Seeder;

class RentalStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['id' => 1, 'name' => 'Pending', 'description' => 'Awaiting lender approval'],
            ['id' => 2, 'name' => 'To Pay', 'description' => 'Approved, waiting for payment'],
            ['id' => 3, 'name' => 'To Pickup', 'description' => 'Paid, ready for pickup'],
            ['id' => 4, 'name' => 'Ongoing', 'description' => 'Currently being rented'],
            ['id' => 5, 'name' => 'Completed', 'description' => 'Rental completed'],
            ['id' => 6, 'name' => 'Cancelled', 'description' => 'Rental cancelled']
        ];

        foreach ($statuses as $status) {
            RentalStatus::create($status);
        }
    }
}