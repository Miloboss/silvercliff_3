<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\SiteSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;

class VoucherService
{
    // ── Brand token resolution ─────────────────────────────────────────────

    /**
     * Resolve all brand tokens from SiteSettings.
     * brand_* group is the canonical source; email_branding is legacy fallback.
     */
    public function getBrand(): array
    {
        $s = SiteSetting::pluck('value', 'key');

        $primaryColor = $s->get('brand_primary_color',  $s->get('email_primary_color',  '#152a10'));
        $accentColor  = $s->get('brand_accent_color',   '#C6A84B');
        $bgStyle      = $s->get('brand_bg_style',        $s->get('email_header_style', 'gradient'));
        $secondaryColor = '#2a5220'; // derived gradient end

        $headerBgPath = $s->get('brand_header_bg_image', $s->get('email_header_bg_image', ''));
        $headerBgUrl  = $headerBgPath
            ? (str_starts_with($headerBgPath, 'http') ? $headerBgPath : asset($headerBgPath))
            : '';

        $headerStyle = match($bgStyle) {
            'gradient'     => "background: linear-gradient(135deg, {$primaryColor} 0%, {$secondaryColor} 100%);",
            'jungle_image' => $headerBgUrl
                ? "background: url('{$headerBgUrl}') center/cover no-repeat; background-color: {$primaryColor};"
                : "background: linear-gradient(135deg, {$primaryColor} 0%, {$secondaryColor} 100%);",
            default        => "background-color: {$primaryColor};",
        };

        // Sizing maps
        $logoH   = ['sm' => '48', 'md' => '64', 'lg' => '88'][$s->get('brand_logo_size', 'md')] ?? '64';
        $cardPad = ['sm' => '20', 'md' => '32', 'lg' => '48'][$s->get('brand_card_padding', 'md')] ?? '32';
        $bRadius = $s->get('brand_card_radius', '12');
        $headPx  = ['sm' => '18', 'md' => '22', 'lg' => '27'][$s->get('brand_heading_scale', 'md')] ?? '22';

        // Logo: prefer logo_pdf for PDFs, else logo_main, else site_logo
        $logoPathPdf  = $s->get('logo_pdf', '') ?: $s->get('logo_main', '') ?: $s->get('site_logo', 'simple_web_ui/logo.png');
        $logoPathEmail = $s->get('logo_main', '')  ?: $s->get('site_logo', 'simple_web_ui/logo.png');

        return [
            'name'               => $s->get('brand_name', $s->get('site_name', 'Silver Cliff Resort')),
            'tagline'            => $s->get('brand_tagline', $s->get('site_tagline', 'The Real Jungle Experience')),
            'primary_color'      => $primaryColor,
            'accent_color'       => $accentColor,
            'button_bg'          => $s->get('brand_button_bg',   '#C6A84B'),
            'button_text'        => $s->get('brand_button_text', '#152a10'),
            'header_style_css'   => $headerStyle,
            'card_radius'        => $bRadius,
            'card_padding'       => $cardPad,
            'heading_px'         => $headPx,
            'logo_h'             => $logoH,
            'logo_base64_email'  => $this->toBase64($logoPathEmail),
            'logo_base64_pdf'    => $this->toBase64($logoPathPdf),
            'watermark_enabled'  => (bool) $s->get('brand_watermark', '1'),
            'watermark_opacity'  => (int) $s->get('brand_watermark_opacity', '8'),
            'signature_name'     => $s->get('brand_signature_name', 'Reservations Team'),
            'signature_base64'   => $this->toBase64($s->get('brand_signature_image', '')),
            'terms_text'         => $s->get('brand_terms_text', ''),
            'footer_text'        => $s->get('brand_footer_text', ''),
            // Contact
            'whatsapp'   => $s->get('contact_whatsapp', $s->get('whatsapp_number', '')),
            'email'      => $s->get('contact_email', ''),
            'address'    => $s->get('contact_address', $s->get('map_location', 'Khao Sok, Surat Thani, Thailand')),
            'phone'      => $s->get('contact_phone', ''),
        ];
    }

    // ── Voucher data for Blade view ────────────────────────────────────────

    public function getVoucherData(Booking $booking): array
    {
        $brand   = $this->getBrand();
        $booking->load(['roomDetail', 'tourDetail.activity', 'packageDetail.package', 'scheduleItems', 'packageOptions']);

        // QR code URL
        $qrData  = urlencode(url('/') . '?check_booking=' . $booking->booking_code);
        $qrUrl   = "https://chart.googleapis.com/chart?chs=120x120&cht=qr&chl={$qrData}&choe=UTF-8";

        return [
            'booking' => $booking,
            'brand'   => $brand,
            'qr_url'  => $qrUrl,
        ];
    }

    // ── PDF generation ─────────────────────────────────────────────────────

    public function generatePdf(Booking $booking): \Barryvdh\DomPDF\PDF
    {
        $data = $this->getVoucherData($booking);
        $pdf  = Pdf::loadView('documents.voucher', $data);
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled'  => true,
            'isRemoteEnabled'       => false, // base64 images only — no remote fetching
            'defaultFont'           => 'helvetica',
            'dpi'                   => 150,
        ]);
        return $pdf;
    }

    public function download(Booking $booking): \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\Response
    {
        return $this->generatePdf($booking)->download("Voucher-{$booking->booking_code}.pdf");
    }

    public function stream(Booking $booking): \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\Response
    {
        return $this->generatePdf($booking)->stream("Voucher-{$booking->booking_code}.pdf");
    }

    /**
     * Get raw PDF string content for email attachment.
     */
    public function getPdfContent(Booking $booking): string
    {
        return $this->generatePdf($booking)->output();
    }

    // ── Image helpers ──────────────────────────────────────────────────────

    public function toBase64(?string $path): ?string
    {
        if (!$path) return null;
        if (!extension_loaded('gd') && !extension_loaded('imagick')) return null;

        // Short-circuit: if already base64 data URI
        if (str_starts_with($path, 'data:')) return $path;

        $resolved = null;
        // Try storage path
        if (!str_starts_with($path, 'http') && !str_starts_with($path, 'simple_web_ui/')) {
            $check = storage_path('app/public/' . $path);
            if (File::exists($check)) $resolved = $check;
        }
        // Try public_path
        if (!$resolved) {
            $check = public_path($path);
            if (File::exists($check)) $resolved = $check;
        }
        // Default fallback
        if (!$resolved) {
            $fallback = public_path('simple_web_ui/logo.png');
            if (File::exists($fallback)) $resolved = $fallback;
        }

        if ($resolved && File::exists($resolved)) {
            $ext  = strtolower(pathinfo($resolved, PATHINFO_EXTENSION));
            $mime = match($ext) { 'jpg', 'jpeg' => 'jpeg', 'gif' => 'gif', 'webp' => 'webp', default => 'png' };
            return 'data:image/' . $mime . ';base64,' . base64_encode(File::get($resolved));
        }

        return null;
    }
}
