<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$pkg = App\Models\Package::where('code', 'ULTIMATE-JUNGLE')->first();
if ($pkg) {
    echo "Title: " . $pkg->title . "\n";
    echo "Slug in DB: " . ($pkg->slug ?? 'NULL') . "\n";
    echo "Generated slug: " . \Illuminate\Support\Str::slug($pkg->title) . "\n";
} else {
    echo "Package not found\n";
}
