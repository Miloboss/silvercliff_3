<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Mail;

try {
    Mail::raw('Test message', function($m) {
        $m->to('test@example.com')->subject('Test');
    });
    \Illuminate\Support\Facades\Log::info('mailtest success');
} catch (\Throwable $e) {
    \Illuminate\Support\Facades\Log::error('mailtest failed', ['error' => $e->getMessage()]);
}
echo "Mail attempt done\n";
