<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Checking /api/packages response:\n\n";

$packages = App\Models\Package::where('is_active', true)->with(['options', 'itineraries'])->get();

foreach ($packages as $package) {
    if (!$package->slug) {
        $package->slug = \Illuminate\Support\Str::slug($package->title);
    }
}

$first = $packages->first();
if ($first) {
    echo "First package:\n";
    echo "  ID: " . $first->id . "\n";
    echo "  Code: " . $first->code . "\n";
    echo "  Title: " . $first->title . "\n";
    echo "  Slug: " . ($first->slug ?? 'NULL') . "\n\n";
    
    echo "✅ Slug is " . ($first->slug ? "present" : "MISSING") . " in API response\n";
} else {
    echo "No packages found\n";
}
