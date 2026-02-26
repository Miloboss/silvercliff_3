<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class RoomType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'code_prefix',
        'subtitle',
        'description',
        'base_price_thb',
        'capacity_adults',
        'capacity_children',
        'cover_image',
        'highlights',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'highlights'        => 'array',
        'is_active'         => 'boolean',
        'base_price_thb'    => 'decimal:2',
        'capacity_adults'   => 'integer',
        'capacity_children' => 'integer',
        'sort_order'        => 'integer',
    ];

    // ── Relationships ──────────────────────────────────────────
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(RoomImage::class)->orderBy('sort_order');
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'room_type_amenities')
                    ->orderBy('sort_order');
    }

    // ── Accessors ──────────────────────────────────────────────
    public function getCoverImageUrlAttribute(): ?string
    {
        if (!$this->cover_image) return null;
        return Storage::disk('public')->url($this->cover_image);
    }

    public function getActiveRoomsCountAttribute(): int
    {
        return $this->rooms()->where('is_active', true)->count();
    }
}
