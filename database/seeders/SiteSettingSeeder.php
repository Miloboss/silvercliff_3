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
            ['key' => 'contact_map_url', 'group' => 'contact', 'type' => 'text', 'label' => 'Google Map Embed URL', 'value' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3941.938056599948!2d98.53302197352671!3d8.88535149122402!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3051264e92b2677b%3A0xea1b2e8b31958e99!2sKhao%20Sok%20Silver%20Cliff%20Resort!5e0!3m2!1sen!2sth!4v1771559137276!5m2!1sen!2sth'],
            ['key' => 'contact_facebook', 'group' => 'contact', 'type' => 'text', 'label' => 'Facebook URL', 'value' => 'https://facebook.com/silvercliffresort'],
            ['key' => 'contact_instagram', 'group' => 'contact', 'type' => 'text', 'label' => 'Instagram URL', 'value' => 'https://instagram.com/silvercliffresort'],

            // GLOBAL
            ['key' => 'global_name', 'group' => 'global', 'type' => 'text', 'label' => 'Website Name', 'value' => 'Silver Cliff'],
            ['key' => 'global_tagline', 'group' => 'global', 'type' => 'text', 'label' => 'Tagline under logo', 'value' => 'The Real Jungle Experience'],
            ['key' => 'global_logo', 'group' => 'global', 'type' => 'file', 'label' => 'Logo image', 'value' => 'settings/logo.png'],
            ['key' => 'global_favicon', 'group' => 'global', 'type' => 'file', 'label' => 'Favicon image', 'value' => 'settings/favicon.ico'],


            // ── BRAND & DOCUMENT SETTINGS ──────────────────────────────────────
            // Single source of truth for email + PDF + site branding
            ['key' => 'brand_name',           'group' => 'brand', 'type' => 'text',    'label' => 'Brand Name',                       'value' => 'Khao Sok Silver Cliff'],
            ['key' => 'brand_tagline',         'group' => 'brand', 'type' => 'text',    'label' => 'Tagline',                          'value' => 'The Real Jungle Experience'],
            ['key' => 'logo_main',             'group' => 'brand', 'type' => 'file',    'label' => 'Main Logo (website + email)',       'value' => 'simple_web_ui/logo.png'],
            ['key' => 'logo_pdf',              'group' => 'brand', 'type' => 'file',    'label' => 'PDF Logo (optional override)',      'value' => ''],

            // Colors
            ['key' => 'brand_primary_color',   'group' => 'brand', 'type' => 'text',    'label' => 'Primary Color (deep green)',       'value' => '#152a10'],
            ['key' => 'brand_accent_color',    'group' => 'brand', 'type' => 'text',    'label' => 'Accent Color (gold)',              'value' => '#C6A84B'],
            ['key' => 'brand_button_bg',       'group' => 'brand', 'type' => 'text',    'label' => 'Button Background Color',          'value' => '#C6A84B'],
            ['key' => 'brand_button_text',     'group' => 'brand', 'type' => 'text',    'label' => 'Button Text Color',                'value' => '#152a10'],

            // Layout tokens
            ['key' => 'brand_card_radius',     'group' => 'brand', 'type' => 'text',    'label' => 'Card Border Radius (8/12/16)',     'value' => '12'],
            ['key' => 'brand_card_padding',    'group' => 'brand', 'type' => 'text',    'label' => 'Card Padding (sm/md/lg)',          'value' => 'md'],
            ['key' => 'brand_heading_scale',   'group' => 'brand', 'type' => 'text',    'label' => 'Heading Scale (sm/md/lg)',         'value' => 'md'],
            ['key' => 'brand_logo_size',       'group' => 'brand', 'type' => 'text',    'label' => 'Logo Size in emails (sm/md/lg)',   'value' => 'md'],
            ['key' => 'brand_button_style',    'group' => 'brand', 'type' => 'text',    'label' => 'Button Style (solid/outline)',     'value' => 'solid'],

            // Header / background
            ['key' => 'brand_bg_style',        'group' => 'brand', 'type' => 'text',    'label' => 'Header Style (solid/gradient/jungle_image)', 'value' => 'gradient'],
            ['key' => 'brand_header_bg_image', 'group' => 'brand', 'type' => 'file',    'label' => 'Header BG Image (jungle_image style)', 'value' => ''],

            // PDF / Document
            ['key' => 'brand_watermark',       'group' => 'brand', 'type' => 'boolean', 'label' => 'Show Watermark on PDF',           'value' => '1'],
            ['key' => 'brand_watermark_opacity','group' => 'brand', 'type' => 'number',  'label' => 'Watermark Opacity (5–20)',        'value' => '8'],
            ['key' => 'brand_signature_name',  'group' => 'brand', 'type' => 'text',    'label' => 'Signature Name (on voucher)',      'value' => 'Reservations Team'],
            ['key' => 'brand_signature_image', 'group' => 'brand', 'type' => 'file',    'label' => 'Signature Image (optional)',       'value' => ''],

            // Legacy email_branding kept for backward compat (mapped to brand_* at runtime)
            ['key' => 'email_primary_color',   'group' => 'email_branding', 'type' => 'text', 'label' => 'Email Primary Color (legacy)', 'value' => '#152a10'],
            ['key' => 'email_secondary_color', 'group' => 'email_branding', 'type' => 'text', 'label' => 'Email Secondary Color (legacy)', 'value' => '#2a5220'],
            ['key' => 'email_button_bg',       'group' => 'email_branding', 'type' => 'text', 'label' => 'Email Button BG (legacy)', 'value' => '#C6A84B'],
            ['key' => 'email_button_text',     'group' => 'email_branding', 'type' => 'text', 'label' => 'Email Button Text (legacy)', 'value' => '#152a10'],
            ['key' => 'email_logo_size',       'group' => 'email_branding', 'type' => 'text', 'label' => 'Logo Size (legacy)', 'value' => 'md'],
            ['key' => 'email_card_padding',    'group' => 'email_branding', 'type' => 'text', 'label' => 'Card Padding (legacy)', 'value' => 'md'],
            ['key' => 'email_border_radius',   'group' => 'email_branding', 'type' => 'text', 'label' => 'Border Radius (legacy)', 'value' => '12'],
            ['key' => 'email_heading_scale',   'group' => 'email_branding', 'type' => 'text', 'label' => 'Heading Scale (legacy)', 'value' => 'md'],
            ['key' => 'email_header_style',    'group' => 'email_branding', 'type' => 'text', 'label' => 'Header Style (legacy)', 'value' => 'gradient'],
            ['key' => 'email_logo',            'group' => 'email_branding', 'type' => 'file', 'label' => 'Email Logo (legacy)', 'value' => ''],
            ['key' => 'email_header_bg_image', 'group' => 'email_branding', 'type' => 'file', 'label' => 'Header BG Image (legacy)', 'value' => ''],
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
