<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Home', 'Office', 'Workshop', 'Storage']),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'barangay' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'is_default' => false
        ];
    }
}