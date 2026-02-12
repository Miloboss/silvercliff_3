<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/simple_web_ui/index.html');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/bookings/{booking}/download-voucher', [App\Http\Controllers\VoucherController::class, 'download'])
        ->name('bookings.download-voucher');
});
