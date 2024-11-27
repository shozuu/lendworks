<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // define
        $categories = [
            ['name' => 'Power Tools', 'description' => 'Drills, saws, grinders, sanders, etc.'],
            ['name' => 'Hand Tools', 'description' => 'Hammers, wrenches, screwdrivers, pliers, etc.'],
            ['name' => 'Construction Equipment', 'description' => 'Cement mixers, jackhammers, scaffolding, etc.'],
            ['name' => 'Gardening Tools and Equipment', 'description' => 'Lawn mowers, hedge trimmers, shovels, rakes, etc.'],
            ['name' => 'Automotive Tools', 'description' => 'Car jacks, battery chargers, diagnostic tools, etc.'],
            ['name' => 'Painting & Decorating', 'description' => 'Paint sprayers, ladders, wallpaper steamers, etc.'],
            ['name' => 'Cleaning Equipment', 'description' => 'Pressure washers, carpet cleaners, vacuum cleaners, etc.'],
            ['name' => 'Measuring Tools', 'description' => 'Laser levels, tape measures, moisture meters, etc.'],
            ['name' => 'DIY & Craft Tools', 'description' => 'Heat guns, glue guns, rotary tools, etc.'],
            ['name' => 'Electrical & Plumbing Equipment', 'description' => 'Wire strippers, pipe cutters, crimping tools, etc.'],
            ['name' => 'Safety Gear', 'description' => 'Hard hats, safety harnesses, goggles, gloves, etc.'],
            ['name' => 'Specialty Tools', 'description' => 'Tile cutters, drywall lifters, floor sanders, etc.'],
        ];

        // insert to category table
        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
