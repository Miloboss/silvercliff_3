<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\GalleryAlbum;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        $query = GalleryAlbum::with(['images' => function($q) {
            $q->where('is_active', true)->orderBy('sort_order');
        }])->where('is_active', true);
        
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        return response()->json($query->get());
    }
}
