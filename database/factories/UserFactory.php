<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'), // default password for all test users
            'role' => 'user',
            'status' => 'active',
            'about' => fake()->paragraph(2),
            'phone' => fake()->phoneNumber(),
            'rating' => fake()->randomFloat(2, 3, 5),
            'successful_rentals' => fake()->numberBetween(0, 20),
            'successful_lendings' => fake()->numberBetween(0, 20),
            'remember_token' => Str::random(10),
        ];
    }
}