<?php

namespace Database\Seeders;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Seeder;

class ListingSeeder extends Seeder
{
    public function run(): void
    {
        // create listings for randomly generated users
        User::where('email', 'not like', '%@example.com')
            ->each(function ($user) {
                Listing::factory()
                    ->count(rand(2, 5))
                    ->forUser($user)
                    ->create();
            });
    }
}