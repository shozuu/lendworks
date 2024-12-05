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
                    'renter_id' => $renter->id,
                    'rental_status_id' => fake()->randomElement([1, 2, 3, 4, 5, 6]) // Mix of all
                ]);

                // Update listing availability if rental is active
                if (in_array($rental->rental_status_id, [3, 4])) {
                    $listing->update(['is_available' => false]);
                }

                // 70% chance to have a review for completed rentals
                if ($rental->rental_status_id === 5 && fake()->boolean(70)) {
                    RentalReview::factory()->create([
                        'rental_id' => $rental->id,
                        'reviewer_id' => $renter->id
                    ]);
                }
            }
        });
    }
}