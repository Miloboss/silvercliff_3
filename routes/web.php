<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/simple_web_ui/index.html');
});

Route::get('/check-booking', function (\Illuminate\Http\Request $request) {
    $code = $request->query('code', $request->query('id'));
    $target = '/simple_web_ui/booking-status.html';

    if (filled($code)) {
        return redirect()->to($target . '?code=' . urlencode($code));
    }

    return redirect()->to($target);
})->name('check-booking');

Route::middleware(['auth'])->group(function () {

    // ── Voucher download (existing) ──────────────────────────────────────────
    Route::get('/admin/bookings/{booking}/download-voucher',
        [App\Http\Controllers\VoucherController::class, 'download'])
        ->name('bookings.download-voucher');

    // ── Email template preview ───────────────────────────────────────────────
    Route::get('/admin/email-templates/first/preview',
        [App\Http\Controllers\Admin\EmailTemplatePreviewController::class, 'firstPreview'])
        ->name('filament.admin.email-templates.preview.first');

    Route::get('/admin/email-templates/{emailTemplate}/preview',
        [App\Http\Controllers\Admin\EmailTemplatePreviewController::class, 'preview'])
        ->whereNumber('emailTemplate')
        ->name('filament.admin.email-templates.preview');

    // ── Brand / email branding preview (legacy route kept) ──────────────────
    Route::get('/admin/email-branding/preview',
        [App\Http\Controllers\Admin\EmailTemplatePreviewController::class, 'brandingPreview'])
        ->name('filament.admin.email-branding.preview');

    // ── PDF voucher preview + sample download ────────────────────────────────
    Route::get('/admin/voucher/preview',
        [App\Http\Controllers\Admin\VoucherPreviewController::class, 'preview'])
        ->name('filament.admin.voucher.preview');

    Route::get('/admin/voucher/sample-download',
        [App\Http\Controllers\Admin\VoucherPreviewController::class, 'sampleDownload'])
        ->name('filament.admin.voucher.sample-download');
});


Route::get('/packages/{slug}', [App\Http\Controllers\PackageController::class, 'show'])->name('packages.show');
