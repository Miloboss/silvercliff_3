<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Models\SiteSetting;

class SettingController extends Controller
{
    public function index()
    {
        $settings = SiteSetting::all()->pluck('value', 'key');

        $canonicalBrandName = trim((string) (
            $settings->get('brand_name')
            ?? $settings->get('site_name')
            ?? $settings->get('resort_name')
            ?? 'Silver Cliff Resort'
        ));

        if ($canonicalBrandName === '') {
            $canonicalBrandName = 'Silver Cliff Resort';
        }
        
        // Helper to get publicly reachable URL from DB path
        $getStorageUrl = function ($path, $fallback = null) {
            if (!$path) return null;
            if (str_starts_with($path, 'http')) return $path;
            if (str_starts_with($path, 'simple_web_ui/')) return url($path);

            $normalizedPath = ltrim(str_replace('\\', '/', $path), '/');

            if (Storage::disk('public')->exists($normalizedPath)) {
                return Storage::url($normalizedPath);
            }

            if (is_file(public_path($normalizedPath))) {
                return url($normalizedPath);
            }

            if ($fallback) {
                return url(ltrim($fallback, '/'));
            }

            return null;
        };

        $response = [
            'branding' => [
                'brand_name'     => $canonicalBrandName,
                'site_name'      => $canonicalBrandName,
                'resort_name'    => $canonicalBrandName,
                'tagline'        => $settings->get('site_tagline', 'The Real Jungle Experience'),
                'logo_url'       => $getStorageUrl($settings->get('site_logo', 'simple_web_ui/logo.png'), 'simple_web_ui/logo.png'),
                'navbar_logo_url'=> $getStorageUrl($settings->get('site_logo', 'simple_web_ui/logo.png'), 'simple_web_ui/logo.png'),
            ],
            'contact' => [
                'phone'        => $settings->get('contact_phone', $settings->get('whatsapp_number', '')),
                'whatsapp'     => $settings->get('whatsapp_number', $settings->get('contact_phone', '')),
                'whatsapp_url' => 'https://wa.me/' . preg_replace('/[^\d]/', '', $settings->get('whatsapp_number', $settings->get('contact_phone', ''))),
                'email'        => $settings->get('contact_email', $settings->get('email', 'info@silvercliffresort.com')),
                'address'      => $settings->get('contact_address', $settings->get('map_location', 'Khao Sok, Thailand')),
                'facebook'     => $settings->get('facebook_url', ''),
                'instagram'    => $settings->get('instagram_url', ''),
                'map_url'      => $getStorageUrl($settings->get('map_image', '')),
                'google_maps_iframe_url' => $settings->get('contact_map_url', $settings->get('google_map_iframe_url', '')),
                'google_maps_url' => 'https://maps.app.goo.gl/gCZ2pg8xa2QtnP3i9', // Default if dynamic one lacks

            ],
            // Keep legacy groups for compatibility with existing JS if needed
            'hero' => [
                'title' => $settings->get('hero_text', 'Wander into the heart<br />of the rainforest.'),
                'tagline' => $settings->get('tagline', 'The real jungle experience awaits you.'),
                'cta_text' => 'Book Now',
                'bg_url' => url('simple_web_ui/jjk.mp4'),
            ],
            'intro' => [
                'title' => $settings->get('intro_title', ''),
                'text' => $settings->get('intro_text', ''),
            ],
            'activities' => [
                'title' => 'Activities & Tours',
                'limit' => 6,
            ],
            'gallery' => [
                'title' => 'Gallery',
                'limit' => 6,
            ],
        ];

        return response()->json($response);
    }
}
