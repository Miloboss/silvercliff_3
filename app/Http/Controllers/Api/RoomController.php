<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RoomType;

class RoomController extends Controller
{
    /**
     * GET /api/rooms
     * Returns list of active room types for the rooms.html listing page.
     */
    public function index()
    {
        $roomTypes = RoomType::with(['images' => fn($q) => $q->where('is_featured', true)->orWhere('sort_order', 0)->limit(1)])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function (RoomType $rt) {
                return [
                    'id'               => $rt->id,
                    'name'             => $rt->name,
                    'slug'             => $rt->slug,
                    'code_prefix'      => $rt->code_prefix,
                    'subtitle'         => $rt->subtitle,
                    'base_price_thb'   => (float) $rt->base_price_thb,
                    'capacity_adults'  => $rt->capacity_adults,
                    'capacity_children'=> $rt->capacity_children,
                    'cover_image_url'  => $rt->cover_image_url,
                    'highlights'       => $rt->highlights ?? [],
                    'rooms_count'      => $rt->active_rooms_count,
                ];
            });

        return response()->json($roomTypes);
    }

    /**
     * GET /api/rooms/{slug}
     * Returns full room type details for room-details.html.
     */
    public function show(string $slug)
    {
        $rt = RoomType::with(['images', 'amenities', 'rooms' => fn($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json([
            'id'               => $rt->id,
            'name'             => $rt->name,
            'slug'             => $rt->slug,
            'code_prefix'      => $rt->code_prefix,
            'subtitle'         => $rt->subtitle,
            'description'      => $rt->description,
            'base_price_thb'   => (float) $rt->base_price_thb,
            'capacity_adults'  => $rt->capacity_adults,
            'capacity_children'=> $rt->capacity_children,
            'cover_image_url'  => $rt->cover_image_url,
            'highlights'       => $rt->highlights ?? [],
            'gallery_images'   => $rt->images->map(fn($img) => [
                'id'         => $img->id,
                'url'        => $img->image_url,
                'caption'    => $img->caption,
                'is_featured'=> $img->is_featured,
            ]),
            'amenities'        => $rt->amenities->map(fn($a) => [
                'id'       => $a->id,
                'name'     => $a->name,
                'key'      => $a->key,
                'icon_key' => $a->icon_key,
            ]),
            'rooms'            => $rt->rooms->map(fn($r) => [
                'id'        => $r->id,
                'room_code' => $r->room_code,
                'notes'     => $r->notes,
            ]),
        ]);
    }
}
