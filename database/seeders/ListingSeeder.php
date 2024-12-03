<?php

namespace Database\Seeders;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Seeder;

class ListingSeeder extends Seeder
{
    public function run(): void
    {
        User::all()->each(function ($user) {
            Listing::factory()
                ->count(rand(2, 5))
                ->forUser($user)
                ->create();
        });
    }
}