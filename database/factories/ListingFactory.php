<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ListingFactory extends Factory
{
    public function definition(): array
    {
        $value = fake()->numberBetween(5000, 200000);

        return [
            'title' => fake()->words(3, true),
            'desc' => fake()->paragraph(),
            'value' => $value,
            'price' => fake()->numberBetween(100, 1000),
            'status' => 'pending',
            'is_available' => true,
            'category_id' => Category::inRandomOrder()->first()?->id ?? 1,
            'location_id' => Location::inRandomOrder()->first()?->id ?? 1,
            'user_id' => User::factory(),
            'deposit_fee' => function() use ($value) {
                // Calculate deposit fee as 30% of the item's value
                return (int) ($value * 0.30);
            },
        ];
    }

    public function forUser(User $user)
    {
        return $this->state(function (array $attributes) use ($user) {
            return ['user_id' => $user->id];
        });
    }

    public function approved()
    {
        return $this->state(function (array $attributes) {
            return ['status' => 'approved'];
        });
    }

    public function pending()
    {
        return $this->state(function (array $attributes) {
            return ['status' => 'pending'];
        });
    }

    public function rejected()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'rejected',
                'rejection_reason' => fake()->sentence()
            ];
        });
    }
}