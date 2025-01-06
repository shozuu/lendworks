<?php

namespace Database\Seeders;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Seeder;

class ListingSeeder extends Seeder
{
    public function run(): void
    {
        User::where('email', 'not like', '%@example.com')->each(function ($user) {
            // Create 2-5 listings per user
            $listingsCount = rand(2, 5);
            
            // 70% chance of approved listings
            $approvedCount = ceil($listingsCount * 0.7);
            $pendingCount = $listingsCount - $approvedCount;

            // Create approved listings
            Listing::factory()
                ->count($approvedCount)
                ->approved()
                ->create([
                    'user_id' => $user->id
                ]);

            // Create pending listings
            Listing::factory()
                ->count($pendingCount)
                ->pending()
                ->create([
                    'user_id' => $user->id
                ]);
        });
    }
}