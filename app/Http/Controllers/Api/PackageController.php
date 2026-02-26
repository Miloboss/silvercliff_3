<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Package;

class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::where('is_active', true)
            ->with(['options', 'itineraries', 'media'])
            ->get();
        
        foreach ($packages as $package) {
            if (!$package->slug) {
                $package->slug = \Illuminate\Support\Str::slug($package->title);
            }
        }
        
        return response()->json($packages);
    }

    public function show(string $slug)
    {
        $package = Package::query()
            ->where('is_active', true)
            ->where(function ($query) use ($slug) {
                $query->where('slug', $slug);

                if (is_numeric($slug)) {
                    $query->orWhere('id', (int) $slug);
                }
            })
            ->with([
                'itineraries' => fn ($query) => $query->orderBy('sort_order')->orderBy('day_no'),
                'options',
                'media' => fn ($query) => $query->orderBy('sort_order'),
            ])
            ->first();

        if (!$package) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if (!$package->is_active) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if (!$package->slug) {
            $package->slug = Package::generateUniqueSlug($package->title, $package->id);
            $package->save();
        }

        return response()->json($package);
    }
}
