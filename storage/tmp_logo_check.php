<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$template = \App\Models\EmailTemplate::where('is_enabled', true)->first();
$booking = \App\Models\Booking::latest('id')->first();
$mail = \App\Mail\TemplatedMail::make('booking_confirmation_guest', $booking, true);

$outgoingHtml = $mail?->render() ?? '';
$previewHtml = view('emails.preview-wrapper', [
  'template' => $template,
  'booking' => $booking,
  'placeholders' => $mail?->placeholders ?? [],
  'width' => 'desktop',
])->render();

$extract = static function (string $html): ?string {
  if (preg_match('/<img[^>]+src="([^"]+)"/i', $html, $m)) {
    return $m[1];
  }
  return null;
};

$outSrc = $extract($outgoingHtml);
$preSrc = $extract($previewHtml);

echo json_encode([
  'outgoing_src_prefix' => $outSrc ? substr($outSrc, 0, 72) : null,
  'outgoing_src_type' => $outSrc ? (str_starts_with($outSrc, 'data:image') ? 'data-uri-from-cid-fallback' : (str_starts_with($outSrc, 'cid:') ? 'cid' : (str_starts_with($outSrc, 'http') ? 'absolute-url' : 'other'))) : 'none',
  'preview_src' => $preSrc,
], JSON_PRETTY_PRINT), PHP_EOL;