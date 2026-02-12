<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Booking;
use App\Models\Activity;
use App\Models\Package;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Roles
        $this->call(RoleSeeder::class);

        // Site Settings
        $settings = [
            'site_name' => 'SILVER CLIFF RESORT',
            'site_tagline' => 'THE REAL JUNGLE EXPERIENCE',
            'site_logo' => 'images/logo.png',
            'tagline' => 'THE REAL JUNGLE EXPERIENCE',
            'hero_text' => 'Wander into the heart<br />of the rainforest.',
            'whatsapp_number' => '+66 84 845 3550',
            'email' => 'silvercliff_resort@hotmail.com',
            'map_location' => 'Khao Sok, Surat Thani, Thailand',
            'intro_title' => 'Thailand’s natural wonder experience',
            'intro_text' => 'Silver Cliff Resort offers an authentic jungle experience surrounded by ancient limestone cliffs and lush rainforest.',
            'amenities' => 'Breakfast included, Free Wi-Fi, Tour Desk, Jungle Restaurant, On-site Parking',
            'policies' => 'Check-in: 14:00, Check-out: 11:00, ID Required, Cash / Wise / Bank Transfer',
            'admin_notifications_email' => 'admin@silvercliffresort.com',
        ];

        foreach ($settings as $key => $value) {
            \App\Models\SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        // Activities
        $activities = [
            ['title' => 'Jungle Trekking', 'description' => 'Guided tour through the ancient forests.', 'price_thb' => 800],
            ['title' => 'River Canoeing', 'description' => 'Paddle down the calm Sok river.', 'price_thb' => 500],
            ['title' => 'Night Safari', 'description' => 'Spot nocturnal wildlife with a guide.', 'price_thb' => 1200],
        ];

        foreach ($activities as $act) {
            Activity::create($act);
        }

        // Packages
        $packages = [
            [
                'code' => 'JUNGLE-01',
                'title' => 'The Complete Jungle Quest',
                'subtitle' => '3 Days 2 Nights of Adventure',
                'price_thb' => 5500,
                'duration_days' => 3,
                'duration_nights' => 2,
                'description' => 'Deep dive into the heart of the jungle with our most popular package.',
                'includes' => ['Meals', 'Guide', 'Transport', 'Equipment'],
                'is_best_offer' => true,
            ],
        ];

        foreach ($packages as $pkgData) {
            $package = Package::create($pkgData);
            for ($i = 1; $i <= $package->duration_days; $i++) {
                $package->itineraries()->create([
                    'day_no' => $i,
                    'title' => "Day $i Exploration",
                    'description' => "Deep jungle activities for day $i",
                ]);
            }
        }

        // Gallery
        $categories = ['resort', 'jungle', 'lake', 'accommodation', 'elephant', 'survival'];
        for ($i = 1; $i <= 10; $i++) {
            \App\Models\GalleryImage::create([
                'category' => $categories[array_rand($categories)],
                'image_path' => "gallery/sample-$i.jpg",
                'caption' => "Nature View " . $i,
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }

        // Rooms
        $this->call(RoomSeeder::class);

        // Seed Bookings
        
        // 1. Room Bookings
        $b1 = $this->createBooking('room', 'website');
        $b1->roomDetail()->create([
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(7)->toDateString(),
            'guests_adults' => 2,
            'guests_children' => 0,
        ]);
        // Assign room
        $b1->roomAssignments()->create([
            'room_id' => 1, // D1
            'assigned_from' => now()->addDays(5)->toDateString(),
            'assigned_to' => now()->addDays(7)->toDateString(),
        ]);

        $b2 = $this->createBooking('room', 'bookingcom');
        $b2->roomDetail()->create([
            'check_in' => now()->addDays(10)->toDateString(),
            'check_out' => now()->addDays(12)->toDateString(),
            'guests_adults' => 1,
            'guests_children' => 1,
        ]);
        // Assign room
        $b2->roomAssignments()->create([
            'room_id' => 2, // D2
            'assigned_from' => now()->addDays(10)->toDateString(),
            'assigned_to' => now()->addDays(12)->toDateString(),
        ]);

        // 2. Tour Bookings
        $act = Activity::first();
        $b3 = $this->createBooking('tour', 'website');
        $b3->tourDetail()->create([
            'activity_id' => $act->id,
            'tour_date' => now()->toDateString(), // Today!
            'tour_time' => '09:00:00',
            'guests_adults' => 4,
            'guests_children' => 2,
        ]);
        $b3->scheduleItems()->create([
            'title' => $act->title,
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '09:00:00',
        ]);

        // 3. Package Bookings
        $pkg = Package::first();
        $b4 = $this->createBooking('package', 'website');
        $b4->packageDetail()->create([
            'package_id' => $pkg->id,
            'check_in' => now()->toDateString(), // Today!
            'check_out' => now()->addDays(3)->toDateString(),
            'guests_adults' => 2,
            'guests_children' => 0,
        ]);
        $b4->roomAssignments()->create([
            'room_id' => 3, // D3
            'assigned_from' => now()->toDateString(),
            'assigned_to' => now()->addDays(3)->toDateString(),
        ]);
        foreach ($pkg->itineraries as $it) {
            $b4->scheduleItems()->create([
                'title' => $it->title,
                'scheduled_date' => now()->addDays($it->day_no - 1)->toDateString(),
                'scheduled_time' => '10:00:00',
            ]);
        }
    }

    private function createBooking($type, $source = 'website')
    {
        return Booking::create([
            'booking_code' => 'SC-' . date('Ymd') . '-' . strtoupper(Str::random(4)),
            'booking_type' => $type,
            'status' => 'pending',
            'full_name' => 'Demo User ' . rand(1, 100),
            'whatsapp' => '+668' . rand(10000000, 99999999),
            'email' => 'user' . rand(1, 100) . '@example.com',
            'source' => $source,
        ]);
    }
}
