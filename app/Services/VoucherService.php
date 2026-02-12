<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class VoucherService
{
    /**
     * Get all necessary data and settings for the voucher.
     */
    public function getVoucherData(Booking $booking): array
    {
        $settings = SiteSetting::pluck('value', 'key');
        
        $brand = [
            'name'     => $settings->get('site_name', 'Silver Cliff Resort'),
            'tagline'  => $settings->get('site_tagline', 'THE REAL JUNGLE EXPERIENCE'),
            'logo_base64' => $this->getLogoAsBase64($settings->get('site_logo')),
            'phone'    => $settings->get('contact_phone', $settings->get('whatsapp_number', '')),
            'whatsapp' => $settings->get('whatsapp_number', $settings->get('contact_phone', '')),
            'email'    => $settings->get('contact_email', $settings->get('email', 'info@silvercliffresort.com')),
            'address'  => $settings->get('contact_address', $settings->get('map_location', 'Khao Sok, Surat Thani, Thailand')),
        ];

        $cssPath = public_path('css/voucher-fixed.css');
        $css = File::exists($cssPath) ? File::get($cssPath) : '';

        return [
            'booking' => $booking,
            'brand'   => $brand,
            'css'     => $css
        ];
    }

    /**
     * Convert logo to base64 for absolute reliability in PDF rendering.
     * Check for GD extension to prevent Internal Server Error.
     */
    private function getLogoAsBase64(?string $path): ?string
    {
        // Guard: If GD is missing, we must skip images to avoid 500 error
        if (!extension_loaded('gd') && !extension_loaded('imagick')) {
            return null;
        }

        $fullPath = null;

        // 1. Check if path is provided
        if ($path) {
            // If it's a storage path
            if (!str_starts_with($path, 'http') && !str_starts_with($path, 'images/') && !str_starts_with($path, 'simple_web_ui/')) {
                $check = storage_path('app/public/' . $path);
                if (File::exists($check)) $fullPath = $check;
            }
            // If it's a public asset path
            if (!$fullPath) {
                $check = public_path($path);
                if (File::exists($check)) $fullPath = $check;
            }
        }

        // 2. Fallback to default logo
        if (!$fullPath) {
            $fallback = public_path('simple_web_ui/logo.png');
            if (File::exists($fallback)) $fullPath = $fallback;
        }

        // 3. Encode to base64
        if ($fullPath && File::exists($fullPath)) {
            $type = pathinfo($fullPath, PATHINFO_EXTENSION);
            $data = File::get($fullPath);
            return 'data:image/' . $type . ';base64,' . base64_encode($data);
        }

        return null;
    }
}
