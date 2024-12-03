<?php

namespace Database\Factories;

use App\Models\Listing;
use App\Models\RentalStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RentalFactory extends Factory
{
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+2 months');
        $endDate = fake()->dateTimeBetween($startDate, '+3 months');
        
        return [
            'listing_id' => Listing::factory(),
            'renter_id' => User::factory(),
            'rental_status_id' => fake()->randomElement([1, 2, 3, 4, 5]), // Use IDs from RentalStatusSeeder
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_price' => fake()->numberBetween(500, 1000),
            'service_fee' => fake()->numberBetween(50, 500),
            'discount' => fake()->numberBetween(0, 50),
            'renter_notes' => fake()->optional()->sentence(),
            'lender_notes' => fake()->optional()->sentence(),
        ];
    }
}