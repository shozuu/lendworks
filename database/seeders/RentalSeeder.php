<?php

namespace Database\Seeders;

use App\Models\Listing;
use App\Models\Rental;
use App\Models\RentalReview;
use App\Models\User;
use Illuminate\Database\Seeder;

class RentalSeeder extends Seeder
{
    public function run(): void
    {
        // Create 1-3 rentals for each listing
        Listing::all()->each(function ($listing) {
            $rentalCount = rand(1, 3);
            
            // Get random users except the listing owner
            $potentialRenters = User::where('id', '!=', $listing->user_id)
                                    ->inRandomOrder()
                                    ->take($rentalCount)
                                    ->get();

            foreach ($potentialRenters as $renter) {
                $rental = Rental::factory()->create([
                    'listing_id' => $listing->id,
                    'renter_id' => $renter->id
                ]);

                // 70% chance to have a review
                if (fake()->boolean(70)) {
                    RentalReview::factory()->create([
                        'rental_id' => $rental->id,
                        'reviewer_id' => $renter->id
                    ]);
                }
            }
        });
    }
}