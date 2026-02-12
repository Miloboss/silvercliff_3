<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\SiteSetting;

class SettingController extends Controller
{
    public function index()
    {
        $settings = SiteSetting::all()->pluck('value', 'key');
        
        // Helper to get storage URL
        $getStorageUrl = function ($path) {
            if (!$path) return null;
            if (str_starts_with($path, 'http')) return $path;
            if (str_starts_with($path, 'simple_web_ui/')) return url($path);
            return \Illuminate\Support\Facades\Storage::url($path);
        };

        $response = [
            'branding' => [
                'site_name' => $settings->get('site_name', 'Silver Cliff Resort'),
                'tagline' => $settings->get('site_tagline', 'The Real Jungle Experience'),
                'logo_url' => $getStorageUrl($settings->get('site_logo', 'simple_web_ui/logo.png')),
            ],
            'contact' => [
                'phone' => $settings->get('contact_phone', $settings->get('whatsapp_number', '')),
                'whatsapp' => $settings->get('whatsapp_number', $settings->get('contact_phone', '')),
                'email' => $settings->get('contact_email', $settings->get('email', 'info@silvercliffresort.com')),
                'address' => $settings->get('map_location', 'Khao Sok, Thailand'),
                'facebook' => $settings->get('facebook_url', ''),
                'instagram' => $settings->get('instagram_url', ''),
                'map_url' => $settings->get('google_map_iframe_url', ''),
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
