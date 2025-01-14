<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Run seeders with predefined data first
        $this->call([
            CategorySeeder::class,
        ]);

        // Then run factory-based seeders in order
        $this->call([
            UserSeeder::class,     // Creates users with locations
            ListingSeeder::class,  // Creates listings for users
            RejectionReasonSeeder::class,  
        ]);
    }
}
