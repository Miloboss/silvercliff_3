<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Package;

class PackageController extends Controller
{
    public function index()
    {
        return response()->json(Package::where('is_active', true)->get());
    }

    public function show(Package $package)
    {
        if (!$package->is_active) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json($package->load('itineraries'));
    }
}
