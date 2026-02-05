<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\GalleryImage;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        $query = GalleryImage::where('is_active', true);
        
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        return response()->json($query->orderBy('sort_order')->get());
    }
}
