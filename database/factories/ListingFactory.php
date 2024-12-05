<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ListingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'desc' => fake()->paragraph(),
            'value' => fake()->numberBetween(3000, 50000),
            'price' => fake()->numberBetween(100, 1000),
            'approved' => true,
            'is_available' => true,
            'category_id' => Category::inRandomOrder()->first()->id,
        ];
    }

    public function forUser(User $user)
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'location_id' => $user->locations->first()->id,
        ]);
    }
}