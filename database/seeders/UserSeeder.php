<?php

namespace Database\Seeders;

use App\Models\User;
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

    public function run(): void
    {
        // Create admin user first
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin'
        ]);

        // Create extra users for testing
        $user1 = User::create([
            'name' => 'Jaydee Ballaho',
            'email' => 'jaydee@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'role' => 'user'
        ]);
        
        $user2 = User::create([
            'name' => 'Allen Tan',
            'email' => 'allen@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
            'role' => 'user'
        ]);

        // Create regular users with random locations
        User::factory()
            ->count(20)
            ->create()
            ->each(fn($user) => $this->createLocationForUser($user));
    }
}