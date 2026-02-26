<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Package;
use App\Models\PackageOption;

class PackageOptionsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure "Ultimate Jungle Experience" exists
        $package = Package::updateOrCreate(
            ['code' => 'ULTIMATE-JUNGLE'],
            [
                'slug' => 'ultimate-jungle-experience',
                'title' => 'Ultimate Jungle Experience',
                'subtitle' => 'The Premium Adventure',
                'price_thb' => 6900,
                'duration_days' => 3,
                'duration_nights' => 2,
                'description' => 'Our most exclusive package, featuring customizable Day 1 activities. Immerse yourself in the heart of Khao Sok with luxury and adventure.',
                'includes' => ['Luxury Bungalow', 'Full Board Meals', 'Choice of 2 Day-1 Activities', 'Full Day Lake Tour', 'Roundtrip Transfers'],
                'is_best_offer' => true,
                'is_active' => true,
            ]
        );

        // Clear existing itineraries for this package to rebuild
        $package->itineraries()->delete();
        
        // Day 1: Arrival & Custom Activities
        $package->itineraries()->create([
            'day_no' => 1,
            'title' => 'Arrival & Your Choice of Adventure',
            'description' => 'Arrive and check in for your ultimate experience. Select 2 activities from our curated list for your first afternoon.',
        ]);

        // Day 2: Lake Tour
        $package->itineraries()->create([
            'day_no' => 2,
            'title' => 'Full Day Cheow Lan Lake Exploration',
            'description' => 'A breathtaking boat journey through limestone cliffs, swimming, and jungle hiking to a hidden cave.',
        ]);

        // Day 3: Morning Chill & Departure
        $package->itineraries()->create([
            'day_no' => 3,
            'title' => 'Mist-covered Mornings & Departure',
            'description' => 'Enjoy a slow morning with a panoramic view of the cliffs before your transfer back.',
        ]);

        // 2. Add Selectable Options for Day 1
        $options = [
            ['name' => 'Elephant Care (No Riding)', 'description' => 'Feed and bathe these gentle giants in a natural environment.'],
            ['name' => 'Guided Jungle Trekking', 'description' => 'Explore the ancient rainforest with our expert local guides.'],
            ['name' => 'Sok River Canoeing', 'description' => 'Relax as you paddle down the calm river under the limestone cliffs.'],
            ['name' => 'Thai Cooking Class', 'description' => 'Learn to cook authentic jungle dishes using fresh local ingredients.'],
        ];

        foreach ($options as $opt) {
            PackageOption::updateOrCreate(
                ['package_id' => $package->id, 'name' => $opt['name']],
                ['description' => $opt['description'], 'is_active' => true]
            );
        }
    }
}
