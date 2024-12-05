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
        $this->createLocationForUser($admin);

        // Create regular users with random locations
        User::factory()
            ->count(20)
            ->create()
            ->each(fn($user) => $this->createLocationForUser($user));
    }
}
// // Create test users with verified email but no associations
// collect([
//     ['name' => 'Test User A', 'email' => 'testa@example.com'],
//     ['name' => 'Test User B', 'email' => 'testb@example.com'],
// ])->map(fn($data) => User::create([
//     ...$data,
//     'password' => bcrypt('password'),
//     'role' => 'user',
//     'email_verified_at' => now() 
// ]));