<?php

namespace Database\Seeders;

use App\Models\RentalStatus;
use Illuminate\Database\Seeder;

class RentalStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'pending', 'description' => 'Pending approval from lender'],
            ['name' => 'approved', 'description' => 'Approved, awaiting payment'],
            ['name' => 'paid', 'description' => 'Payment received, ready for pickup'],
            ['name' => 'active', 'description' => 'Tool is currently being rented'],
            ['name' => 'completed', 'description' => 'Rental completed successfully'],
            ['name' => 'cancelled', 'description' => 'Rental was cancelled'],
            ['name' => 'declined', 'description' => 'Rental request was declined'],
            ['name' => 'disputed', 'description' => 'Rental is under dispute'],
            ['name' => 'overdue', 'description' => 'Item return is overdue'],
        ];

        foreach ($statuses as $status) {
            RentalStatus::create($status);
        }
    }
}