<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== EMAIL RENDER PIPELINE TEST ===\n\n";

// 1. Check that template data is loaded
$template = App\Models\EmailTemplate::where('key', 'booking_confirmation_guest')
    ->where('is_enabled', true)
    ->first();
if (!$template) die("ERROR: No enabled guest template found.\n");

echo "Template: {$template->name} (v{$template->version})\n";
echo "  header_title:   " . ($template->header_title ?: '(empty → falls back to siteName)') . "\n";
echo "  header_tagline: " . ($template->header_tagline ?: '(empty → falls back to brand_tagline)') . "\n";
echo "  accent_color:   " . ($template->accent_color ?: '(empty → falls back to global)') . "\n";
echo "  body_intro:     " . mb_substr(strip_tags($template->body_intro ?? ''), 0, 60) . "...\n";

// 2. Check branding settings
$s = App\Models\SiteSetting::pluck('value', 'key');
echo "\nBranding settings:\n";
echo "  brand_name:          " . $s->get('brand_name', '(missing)') . "\n";
echo "  brand_primary_color: " . $s->get('brand_primary_color', '(missing)') . "\n";
echo "  brand_accent_color:  " . $s->get('brand_accent_color', '(missing)') . "\n";
echo "  logo_main:           " . ($s->get('logo_main', '') ?: '(empty)') . "\n";
echo "  email_logo:          " . ($s->get('email_logo', '') ?: '(empty)') . "\n";

// 3. Build TemplatedMail and test render
$booking = App\Models\Booking::whereNotNull('email')->latest()->first();
echo "\nUsing booking: " . ($booking ? $booking->booking_code : 'none') . "\n";

$mail = new App\Mail\TemplatedMail($template, $booking);
echo "Placeholders resolved: " . count($mail->placeholders) . " keys\n";

// 4. Render the view
try {
    $html = view('emails.template-mail', [
        'template'     => $template,
        'booking'      => $booking,
        'placeholders' => $mail->placeholders,
    ])->render();

    echo "\nRender OK: " . strlen($html) . " bytes\n";

    // Check that key content is present
    $checks = [
        'header_title'  => $template->header_title ?: $s->get('brand_name', 'Khao Sok'),
        'accent_color'  => $template->accent_color ?: $s->get('brand_accent_color', '#C6A84B'),
        'body_intro'    => mb_substr(strip_tags($template->body_intro ?? ''), 0, 30),
    ];
    foreach ($checks as $label => $needle) {
        if ($needle && str_contains($html, $needle)) {
            echo "  ✓ {$label} found in output\n";
        } else {
            echo "  ✗ {$label} NOT found (expected: {$needle})\n";
        }
    }

    // Check logo
    if (str_contains($html, '<img src=')) {
        preg_match('/img src="([^"]+)"/', $html, $m);
        echo "  ✓ Logo img found: " . ($m[1] ?? 'unknown') . "\n";
    } else {
        echo "  ✗ No logo <img> found\n";
    }

    file_put_contents(__DIR__.'/test_email_output.html', $html);
    echo "\nFull HTML saved to test_email_output.html\n";

} catch (Throwable $e) {
    echo "\nRENDER FAILED: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== DONE ===\n";
