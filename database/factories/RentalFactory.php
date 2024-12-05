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
        $basePrice = fake()->numberBetween(500, 1000);
        $discount = fake()->numberBetween(0, 50);
        $serviceFee = (int)(($basePrice - $discount) * 0.25);
        $totalPrice = $basePrice - $discount + $serviceFee;
        
        return [
            'listing_id' => Listing::factory(),
            'renter_id' => User::factory(),
            'rental_status_id' => fake()->randomElement([1, 2, 3, 4, 5]),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'base_price' => $basePrice,
            'discount' => $discount,
            'service_fee' => $serviceFee,
            'total_price' => $totalPrice,
        ];
    }
}