<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Package;

class PackageController extends Controller
{
    public function show($slug)
    {
        $package = Package::where('slug', $slug)
            ->where('is_active', true)
            ->with(['itineraries', 'options' => function($q) {
                $q->where('is_active', true);
            }])
            ->firstOrFail();

        return view('packages.show', compact('package'));
    }
}
