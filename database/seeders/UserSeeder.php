<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Profile;
use App\Models\Location;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    private function createLocationForUser($user)
    {
        $locations = Location::factory()
            ->count(rand(1, 2))
            ->make(['user_id' => $user->id]);
        
        $locations->first()->is_default = true;
        
        foreach ($locations as $location) {
            $user->locations()->save($location);
        }
    }

    private function createProfileForUser($user, array $override = [])
    {
        $profile = Profile::create(array_merge([
            'user_id' => $user->id,
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->boolean(70) ? fake()->firstName() : null,
            'last_name' => fake()->lastName(),
            'birthdate' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['male', 'female']),
            'civil_status' => fake()->randomElement(['single', 'married', 'widowed', 'separated']),
            'mobile_number' => fake()->numerify('09#########'),
            'street_address' => fake()->streetAddress(),
            'barangay' => fake()->words(2, true),
            'city' => fake()->city(),
            'province' => fake()->state(),
            'postal_code' => fake()->numberBetween(1000, 9999),
            'nationality' => 'Filipino',
            'primary_id_type' => fake()->randomElement(['philsys', 'drivers', 'passport', 'sss']),
            'secondary_id_type' => fake()->randomElement(['postal', 'voters', 'umid', 'gsis']),
        ], $override));

        return $profile;
    }

    public function run(): void
    {
        // Create admin user first
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
            'id_verified_at' => now()
        ]);
        $this->createProfileForUser($admin, [
            'first_name' => 'Admin',
            'last_name' => 'User'
        ]);

        // Create extra users for testing
        $user1 = User::create([
            'name' => 'Jaydee Ballaho',
            'email' => 'jaydee@example.com',
            'password' => bcrypt('password'),
            'role' => 'user',
            'email_verified_at' => now(),
            'id_verified_at' => now()
        ]);
        $this->createProfileForUser($user1, [
            'first_name' => 'Jaydee',
            'last_name' => 'Ballaho'
        ]);
        
        $user2 = User::create([
            'name' => 'Allen Tan',
            'email' => 'allen@example.com', 
            'password' => bcrypt('password'),
            'role' => 'user',
            'email_verified_at' => now(),
            'id_verified_at' => now()
        ]);
        $this->createProfileForUser($user2, [
            'first_name' => 'Allen',
            'last_name' => 'Tan'
        ]);

        // Create regular users with profiles and locations
        User::factory()
            ->count(20)
            ->state(function () {
                return [
                    'email_verified_at' => now(),
                    'id_verified_at' => now()
                ];
            })
            ->create()
            ->each(function($user) {
                $this->createProfileForUser($user);
                $this->createLocationForUser($user);
            });
    }
}