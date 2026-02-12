<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SiteSetting;

class SiteSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // HERO
            ['key' => 'hero_title', 'group' => 'hero', 'type' => 'text', 'label' => 'Hero Title', 'value' => 'Wander into the heart<br />of the rainforest.'],
            ['key' => 'hero_tagline', 'group' => 'hero', 'type' => 'text', 'label' => 'Hero Tagline', 'value' => 'The real jungle experience awaits you.'],
            ['key' => 'hero_bg', 'group' => 'hero', 'type' => 'file', 'label' => 'Hero Background (Image/Video)', 'value' => 'settings/jjk.mp4'],
            ['key' => 'hero_cta_text', 'group' => 'hero', 'type' => 'text', 'label' => 'Hero CTA Text', 'value' => 'Book Now'],

            // INTRO
            ['key' => 'intro_title', 'group' => 'intro', 'type' => 'text', 'label' => 'Intro Title', 'value' => 'Thailand’s natural wonder experience'],
            ['key' => 'intro_text', 'group' => 'intro', 'type' => 'textarea', 'label' => 'Intro Description', 'value' => 'Use this space to describe your resort: vibe, location, what makes it special.'],

            // ACTIVITIES
            ['key' => 'activities_title', 'group' => 'activities', 'type' => 'text', 'label' => 'Activities Title', 'value' => 'Activities & Tours'],
            ['key' => 'activities_subtitle', 'group' => 'activities', 'type' => 'text', 'label' => 'Activities Subtitle', 'value' => 'Immerse yourself in nature with our curated experiences.'],
            ['key' => 'activities_limit', 'group' => 'activities', 'type' => 'number', 'label' => 'Max Activities Shown', 'value' => '6'],
            ['key' => 'activities_cta', 'group' => 'activities', 'type' => 'text', 'label' => 'Activities CTA Text', 'value' => 'Book'],

            // PACKAGES
            ['key' => 'packages_title', 'group' => 'packages', 'type' => 'text', 'label' => 'Packages Title', 'value' => 'Packages'],
            ['key' => 'packages_subtitle', 'group' => 'packages', 'type' => 'text', 'label' => 'Packages Subtitle', 'value' => '“Best offer” style packages + CTA buttons.'],

            // GALLERY
            ['key' => 'gallery_title', 'group' => 'gallery', 'type' => 'text', 'label' => 'Gallery Title', 'value' => 'Gallery'],
            ['key' => 'gallery_subtitle', 'group' => 'gallery', 'type' => 'text', 'label' => 'Gallery Subtitle', 'value' => 'Tap images to open lightbox.'],
            ['key' => 'gallery_limit', 'group' => 'gallery', 'type' => 'number', 'label' => 'Max Images Shown', 'value' => '6'],
            ['key' => 'gallery_cta', 'group' => 'gallery', 'type' => 'text', 'label' => 'Gallery CTA Text', 'value' => 'View Full Gallery'],

            // CONTACT
            ['key' => 'contact_phone', 'group' => 'contact', 'type' => 'text', 'label' => 'Phone Number', 'value' => '+66 (0) 000 000 000'],
            ['key' => 'contact_whatsapp', 'group' => 'contact', 'type' => 'text', 'label' => 'WhatsApp Number', 'value' => '+66 (0) 000 000 000'],
            ['key' => 'contact_email', 'group' => 'contact', 'type' => 'email', 'label' => 'Email Address', 'value' => 'info@silvercliffresort.com'],
            ['key' => 'contact_address', 'group' => 'contact', 'type' => 'textarea', 'label' => 'Address', 'value' => 'Khao Sok, Surat Thani'],
            ['key' => 'contact_map_url', 'group' => 'contact', 'type' => 'text', 'label' => 'Google Map URL', 'value' => ''],
            ['key' => 'contact_facebook', 'group' => 'contact', 'type' => 'text', 'label' => 'Facebook URL', 'value' => ''],
            ['key' => 'contact_instagram', 'group' => 'contact', 'type' => 'text', 'label' => 'Instagram URL', 'value' => ''],

            // GLOBAL
            ['key' => 'global_name', 'group' => 'global', 'type' => 'text', 'label' => 'Website Name', 'value' => 'Silver Cliff'],
            ['key' => 'global_tagline', 'group' => 'global', 'type' => 'text', 'label' => 'Tagline under logo', 'value' => 'The Real Jungle Experience'],
            ['key' => 'global_logo', 'group' => 'global', 'type' => 'file', 'label' => 'Logo image', 'value' => 'settings/logo.png'],
            ['key' => 'global_favicon', 'group' => 'global', 'type' => 'file', 'label' => 'Favicon image', 'value' => 'settings/favicon.ico'],
        ];

        foreach ($settings as $s) {
            SiteSetting::updateOrCreate(
                ['key' => $s['key']],
                [
                    'group' => $s['group'],
                    'type' => $s['type'],
                    'label' => $s['label'],
                    'value' => $s['value'],
                ]
            );
        }
    }
}
