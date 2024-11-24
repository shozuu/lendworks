<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Listing>
 */
class ListingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->first()->id,
            'title' => fake()->sentence(10),
            'desc' => fake()->paragraph(12),
            'category_id' => Category::inRandomOrder()->first()->id,
            'value' => $this->faker->randomFloat(2, 1000, 10000),
            'price' => $this->faker->randomFloat(2, 500, 5000),
            'approved' => 1
        ];
    }
}
