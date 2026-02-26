<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$packages = App\Models\Package::where('is_active', true)->with(['options', 'itineraries'])->get();
foreach ($packages as $package) {
    if (!$package->slug) {
        $package->slug = \Illuminate\Support\Str::slug($package->title);
    }
}

echo "Packages with slugs:\n";
foreach ($packages as $pkg) {
    echo sprintf("ID: %d | Code: %s | Title: %s | Slug: %s\n", 
        $pkg->id, 
        $pkg->code, 
        $pkg->title, 
        $pkg->slug ?? 'NULL'
    );
}
