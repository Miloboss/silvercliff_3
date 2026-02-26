@php
/*
 * ── LUXURY JUNGLE EMAIL LAYOUT ──────────────────────────────────────────────
 * Unified renderer for ALL booking emails (Guests, Admins, Status Updates).
 * Supports Admin-driven branding and per-template content overrides.
 */

$s = \App\Models\SiteSetting::pluck('value', 'key');

// 1. Resolve Placeholders FIRST (to be used in tagline/titles)
$ph = $placeholders ?? [];

// 2. Brand Identity
$siteName = $s->get('brand_name', $s->get('site_name', 'Khao Sok Silver Cliff Resort'));

// 3. Colors - Legacy and Backwards Compatibility
$primaryColor   = $s->get('email_primary_color',   $s->get('brand_primary_color', '#152a10'));
$secondaryColor = $s->get('email_secondary_color', '#2a5220');
$btnBgColor      = $s->get('email_button_bg',       $s->get('brand_button_bg',    '#C6A84B'));
$btnTextColor    = $s->get('email_button_text',     $s->get('brand_button_text',  '#152a10'));
$globalAccent   = $s->get('brand_accent_color', '#C6A84B');
$accentColor    = (isset($template) && !empty($template->accent_color)) ? $template->accent_color : $globalAccent;

// 3b. New Colors from Email Branding
$headerBgColor = $s->get('header_background_color') ?: $primaryColor;
$headerTextColor = $s->get('header_text_color') ?: '#ffffff';
$bodyBgColor = $s->get('body_background_color') ?: '#f4f2ea';
$cardBgColor = $s->get('card_background_color') ?: '#ffffff';
$primaryBtnColor = $s->get('primary_button_color') ?: $btnBgColor;
$primaryBtnTextColor = $s->get('primary_button_text_color') ?: $btnTextColor;
$accentBorderColor = $s->get('accent_border_color') ?: $accentColor;
$dividerColor = $s->get('divider_color') ?: '#eeeeee';
$footerBgColor = $s->get('footer_background_color') ?: '#faf9f6';

// 4. Header Text (Per-template overrides vs Defaults)
$headerTitleText = (isset($template) && !empty($template->header_title)) ? $template->header_title : $siteName;
$headerTitle     = \App\Models\EmailTemplate::resolvePlaceholders($headerTitleText, $ph);

$headerTaglineText = (isset($template) && !empty($template->header_tagline)) ? $template->header_tagline : $s->get('brand_tagline', 'The Real Jungle Experience');
$headerTagline     = \App\Models\EmailTemplate::resolvePlaceholders($headerTaglineText, $ph);

// 5. Logo URL (Absolute & Secure)
$logoPath = $s->get('email_logo', '') ?: $s->get('logo_main', '') ?: $s->get('site_logo', '');
$logoUrl  = '';

if ($logoPath) {
    $normalizedLogoPath = ltrim(str_replace('\\', '/', $logoPath), '/');
    $logoAbsolutePath = null;

    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($normalizedLogoPath)) {
        $logoAbsolutePath = \Illuminate\Support\Facades\Storage::disk('public')->path($normalizedLogoPath);
        // Always build a fully-qualified absolute URL for email clients.
        $logoUrl = url(\Illuminate\Support\Facades\Storage::disk('public')->url($normalizedLogoPath));
    } elseif (is_file(public_path($normalizedLogoPath))) {
        $logoAbsolutePath = public_path($normalizedLogoPath);
        $logoUrl = url('/' . ltrim($normalizedLogoPath, '/'));
    } else {
        $logoUrl = \App\Support\PublicStorageUrl::fromPath($logoPath) ?? '';
        if ($logoUrl && !str_starts_with($logoUrl, 'http://') && !str_starts_with($logoUrl, 'https://')) {
            $logoUrl = url($logoUrl);
        }
    }

    $host = (string) parse_url($logoUrl, PHP_URL_HOST);
    $scheme = strtolower((string) parse_url($logoUrl, PHP_URL_SCHEME));
    $isIp = (bool) filter_var($host, FILTER_VALIDATE_IP);
    $isPublicIp = $isIp && (bool) filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    $isPrivateOrReservedIp = $isIp && ! $isPublicIp;
    $isLocalHost = in_array(strtolower($host), ['localhost', '127.0.0.1'], true) || str_ends_with(strtolower($host), '.local');
    $isNonHttps = $scheme !== 'https';

    // Remote clients (e.g. Gmail) cannot fetch localhost/private URLs reliably.
    // For local/test/non-https hosts, embed as CID when sending actual email.
    $shouldEmbedCid = isset($message)
        && ($message instanceof \Illuminate\Mail\Message)
        && $logoAbsolutePath
        && is_readable($logoAbsolutePath)
        && ($isLocalHost || $isPrivateOrReservedIp || $isNonHttps || app()->environment(['local', 'testing']));

    if ($shouldEmbedCid) {
        try {
            $logoUrl = $message->embed($logoAbsolutePath);
        } catch (\Throwable $exception) {
            // Keep absolute URL fallback.
        }
    }

    if (isset($message) && ($message instanceof \Illuminate\Mail\Message)) {
        \Illuminate\Support\Facades\Log::info('[EmailLogo] resolved source for outgoing email', [
            'logo_path' => $logoPath,
            'resolved_src' => $logoUrl,
            'host' => $host,
            'scheme' => $scheme,
            'embedded_cid' => str_starts_with((string) $logoUrl, 'cid:'),
            'app_url' => config('app.url'),
        ]);
    }
}

// 6. Header Background (Legacy style + new extensions)
$bgStyle       = $s->get('email_header_style',     $s->get('brand_bg_style', 'gradient'));
$headerBgPath  = $s->get('email_header_bg_image', $s->get('brand_header_bg_image', ''));
$headerBgUrl   = \App\Support\PublicStorageUrl::fromPath($headerBgPath) ?? '';

$headerBgCss = match($bgStyle) {
    'gradient'     => "background-color:{$headerBgColor};background-image:linear-gradient(135deg,{$headerBgColor} 0%,{$secondaryColor} 100%);",
    'jungle_image' => $headerBgUrl
        ? "background:{$headerBgColor} url('{$headerBgUrl}') center/cover no-repeat;"
        : "background-color:{$headerBgColor};background-image:linear-gradient(135deg,{$headerBgColor} 0%,{$secondaryColor} 100%);",
    default        => "background-color:{$headerBgColor};",
};

// 7. Sizing & Spacing Tokens
$logoH   = ['sm' => '48', 'md' => '64', 'lg' => '84'][$s->get('email_logo_size',     $s->get('brand_logo_size',     'md'))] ?? '64';
$cardPad = ['sm' => '20', 'md' => '32', 'lg' => '48'][$s->get('email_card_padding',  $s->get('brand_card_padding',  'md'))] ?? '32';
$headPx  = ['sm' => '18', 'md' => '22', 'lg' => '27'][$s->get('email_heading_scale', $s->get('brand_heading_scale', 'md'))] ?? '22';
$bRadius = (int) ($s->get('email_border_radius', $s->get('brand_card_radius', '12')) ?: 12);

// Overrides
$logoMaxW = $s->get('logo_max_width') ?: 200;
$hPad = $s->get('header_padding') !== null && $s->get('header_padding') !== '' ? $s->get('header_padding') : 50;
$bPad = $s->get('body_padding') !== null && $s->get('body_padding') !== '' ? $s->get('body_padding') : $cardPad;
$cRadius = $s->get('card_radius') !== null && $s->get('card_radius') !== '' ? $s->get('card_radius') : $bRadius;
$btnRadius = $s->get('button_radius') !== null && $s->get('button_radius') !== '' ? $s->get('button_radius') : 6;

// Typography Overrides
$titleFs = $s->get('title_font_size') ?: $headPx;
$bodyFs = $s->get('body_font_size') ?: 15;
$lHeight = $s->get('line_height') ?: 1.8;

// Toggles
$showLogo = $s->has('show_logo') ? (bool)$s->get('show_logo') : true;
$showDividers = $s->has('show_dividers') ? (bool)$s->get('show_dividers') : true;


// 8. Body Content
$bodyHtml     = \App\Models\EmailTemplate::resolvePlaceholders($template->body_intro    ?? '', $ph);
$policiesText = \App\Models\EmailTemplate::resolvePlaceholders($template->policies_text ?? '', $ph);
$footerText   = \App\Models\EmailTemplate::resolvePlaceholders($template->footer_text   ?? '', $ph);

// 9. CTA Button URL
$ctaUrl = null;
if (isset($booking) && $booking && $booking->booking_code) {
    $ctaUrl = url('/check-booking?code=' . $booking->booking_code);
}
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!--[if mso]>
    <style type="text/css">
        table, td { font-family: Arial, sans-serif !important; }
    </style>
    <![endif]-->
    <title>{{ $siteName }}</title>
</head>
<body style="margin:0;padding:0;background-color:{{ $bodyBgColor }};font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">

{{-- Preheader text --}}
<div style="display:none; max-height:0px; overflow:hidden;">
    {{ strip_tags($bodyHtml) }}
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:{{ $bodyBgColor }};margin:0;padding:0;">
<tr><td align="center" style="padding:40px 10px;">

    {{-- Main Container --}}
    <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"
           style="max-width:600px;width:100%;background-color:{{ $cardBgColor }};border-radius:{{ $cRadius }}px;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,0.1);">

        {{-- ── HEADER ── --}}
        <tr>
            <td style="{{ $headerBgCss }} padding:{{ $hPad }}px 40px;text-align:center;">
                @if($showLogo && $logoUrl)
                <img src="{{ $logoUrl }}" height="{{ $logoH }}" alt="{{ $siteName }}"
                     style="display:inline-block;margin-bottom:20px;height:{{ $logoH }}px;max-width:{{ $logoMaxW }}px;width:auto;object-fit:contain;">
                @endif
                <h1 style="margin:0;color:{{ $headerTextColor }};font-size:{{ $titleFs }}px;font-weight:700;text-transform:uppercase;letter-spacing:3px;line-height:1.2;">
                    {{ $headerTitle }}
                </h1>
                <p style="margin:10px 0 0;color:{{ $accentBorderColor }};font-size:12px;letter-spacing:2px;text-transform:uppercase;font-weight:600;opacity:0.9;">
                    {{ $headerTagline }}
                </p>
            </td>
        </tr>

        {{-- Decor bar --}}
        @if($showDividers)
        <tr>
            <td style="height:4px;background:linear-gradient(to right, transparent, {{ $accentBorderColor }}, transparent);"></td>
        </tr>
        @endif

        {{-- ── BODY ── --}}
        <tr>
            <td style="padding:{{ $bPad }}px 40px;">

                {{-- Intro --}}
                @if(trim(strip_tags($bodyHtml)))
                <div style="font-size:{{ $bodyFs }}px;color:#2d3a2d;line-height:{{ $lHeight }};margin-bottom:30px;">
                    {!! $bodyHtml !!}
                </div>
                @endif

                {{-- ── Booking Summary ── --}}
                @if(isset($booking) && $booking && $booking->id)
                @php
                    $booking->loadMissing(['roomDetail', 'tourDetail.activity', 'packageDetail.package']);
                    $summaryRows = [
                        ['Reference', '#' . $booking->booking_code,                          true],
                        ['Type',      ucfirst($booking->booking_type),                     false],
                        ['Total',     'THB ' . number_format((float)$booking->total_amount, 2), true],
                    ];

                    if ($booking->booking_type === 'room' && $booking->roomDetail) {
                        $ci = \Carbon\Carbon::parse($booking->roomDetail->check_in);
                        $co = \Carbon\Carbon::parse($booking->roomDetail->check_out);
                        array_splice($summaryRows, 2, 0, [
                            ['Check-in',  $ci->format('d M Y'),                  false],
                            ['Guests',    ($booking->roomDetail->guests_adults + $booking->roomDetail->guests_children) . ' Person(s)', false],
                        ]);
                    } elseif ($booking->booking_type === 'tour' && $booking->tourDetail) {
                        array_splice($summaryRows, 2, 0, [
                            ['Activity', $booking->tourDetail->activity?->title ?? 'Tour', false],
                            ['Date',     \Carbon\Carbon::parse($booking->tourDetail->tour_date)->format('d M Y'), false],
                        ]);
                    } elseif ($booking->booking_type === 'package' && $booking->packageDetail) {
                        array_splice($summaryRows, 2, 0, [
                            ['Package',   $booking->packageDetail->package?->title ?? 'Package',                    false],
                            ['Arrival',   \Carbon\Carbon::parse($booking->packageDetail->check_in)->format('d M Y'),  false],
                        ]);
                    }
                @endphp

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                       style="background-color:{{ $primaryColor }}08; border:1px solid {{ $accentBorderColor }}40; border-radius:8px; margin-bottom:30px;">
                    <tr>
                        <td align="left" style="padding:15px 20px; border-bottom:1px solid {{ $accentBorderColor }}40;">
                            <span style="font-size:10px; font-weight:700; text-transform:uppercase; color:{{ $primaryColor }}; letter-spacing:1px;">Booking Details</span>
                        </td>
                        <td align="right" style="padding:15px 20px; border-bottom:1px solid {{ $accentBorderColor }}40;">
                            <span style="background-color:{{ $accentColor }}20; color:{{ $accentColor }}; padding:4px 10px; border-radius:20px; font-size:10px; font-weight:700; text-transform:uppercase; border:1px solid {{ $accentColor }}40;">
                                {{ $booking->status }}
                            </span>
                        </td>
                    </tr>
                    @foreach($summaryRows as [$label, $value, $bold])
                    <tr>
                        <td style="padding:12px 20px; font-size:13px; color:#666;">{{ $label }}</td>
                        <td style="padding:12px 20px; font-size:13px; text-align:right; {{ $bold ? 'font-weight:bold; color:'.$primaryColor.';' : 'color:#333;' }}">{{ $value }}</td>
                    </tr>
                    @endforeach
                </table>
                @endif

                {{-- CTA Button --}}
                @if($ctaUrl)
                <div style="text-align:center; margin-bottom:40px;">
                    <a href="{{ $ctaUrl }}" style="background-color:{{ $primaryBtnColor }}; color:{{ $primaryBtnTextColor }}; padding:14px 28px; border-radius:{{ $btnRadius }}px; font-size:14px; font-weight:700; text-decoration:none; display:inline-block; box-shadow:0 4px 12px {{ $primaryBtnColor }}40; letter-spacing:1px; text-transform:uppercase;">
                        Manage Your Booking
                    </a>
                </div>
                @endif

                {{-- Note --}}
                @if(trim($policiesText))
                <div style="padding:20px; background-color:{{ $cardBgColor }}; border-left:4px solid {{ $accentBorderColor }}; border-radius:4px; font-size:12px; color:#5a4a2a; line-height:1.6; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <strong style="color:{{ $primaryColor }}; text-transform:uppercase; font-size:10px;">Note:</strong><br>
                    {!! nl2br($policiesText) !!}
                </div>
                @endif

            </td>
        </tr>

        {{-- ── FOOTER ── --}}
        <tr>
            <td style="background-color:{{ $footerBgColor }}; padding:40px; text-align:center; border-top:1px solid {{ $dividerColor }};">
                @if(trim($footerText))
                <div style="font-size:12px; color:#888; line-height:1.6; margin-bottom:20px;">
                    {!! nl2br($footerText) !!}
                </div>
                @endif
                <p style="margin:0; font-size:11px; color:{{ $primaryColor }}; font-weight:700; letter-spacing:1px;">{{ strtoupper($siteName) }}</p>
                <p style="margin:8px 0 0; font-size:10px; color:#aaa; text-transform:uppercase;">&copy; {{ date('Y') }} All Rights Reserved</p>
            </td>
        </tr>

    </table>{{-- /Container --}}

</td></tr>
</table>{{-- /Wrapper --}}

</body>
</html>
