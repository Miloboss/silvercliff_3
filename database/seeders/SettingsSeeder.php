<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'tagline' => 'THE REAL JUNGLE EXPERIENCE',
            'hero_text' => 'Wander into the heart<br />of the rainforest.',
            'whatsapp_number' => '+66 84 845 3550',
            'email' => 'silvercliff_resort@hotmail.com',
            'map_location' => 'Khao Sok, Surat Thani, Thailand',
            'intro_title' => 'Thailand’s natural wonder experience',
            'intro_text' => 'Silver Cliff Resort offers an authentic jungle experience surrounded by ancient limestone cliffs and lush rainforest.',
            'amenities' => 'Breakfast included, Free Wi-Fi, Tour Desk, Jungle Restaurant, On-site Parking',
            'policies' => 'Check-in: 14:00, Check-out: 11:00, ID Required, Cash / Wise / Bank Transfer',
        ];

        foreach ($settings as $key => $value) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
