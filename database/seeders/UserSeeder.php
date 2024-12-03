<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Location;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()
            ->count(20)
            ->create()
            ->each(function ($user) {
                $locations = Location::factory()
                    ->count(rand(1, 2))
                    ->make(['user_id' => $user->id]);
                
                $locations->first()->is_default = true;
                
                foreach ($locations as $location) {
                    $user->locations()->save($location);
                }
            });
    }
}