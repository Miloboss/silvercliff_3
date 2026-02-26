<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Package extends Model
{
    protected $fillable = [
        'code', 'slug', 'title', 'subtitle', 'price_thb', 'duration_days', 'duration_nights',
        'description', 'includes', 'is_best_offer', 'is_active', 
        'thumbnail_image_path', 'hero_image_path', 'video_path'
    ];

    protected $casts = [
        'includes' => 'array',
        'is_best_offer' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = ['thumbnail_image_url', 'hero_image_url', 'video_url'];

    public function getThumbnailImageUrlAttribute()
    {
        return $this->thumbnail_image_path ? asset('storage/' . $this->thumbnail_image_path) : null;
    }

    public function getHeroImageUrlAttribute()
    {
        return $this->hero_image_path ? asset('storage/' . $this->hero_image_path) : null;
    }

    public function getVideoUrlAttribute()
    {
        return $this->video_path ? asset('storage/' . $this->video_path) : null;
    }

    // Legacy support for old 'image_path' field (maps to thumbnail)
    public function getImageUrlAttribute()
    {
        return $this->thumbnail_image_url;
    }

    public function itineraries()
    {
        return $this->hasMany(PackageItinerary::class)->orderBy('sort_order')->orderBy('day_no');
    }

    public function options()
    {
        return $this->hasMany(PackageOption::class);
    }

    public function media()
    {
        return $this->hasMany(PackageMedia::class)->orderBy('sort_order');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $package) {
            if (blank($package->slug) && filled($package->title)) {
                $package->slug = static::generateUniqueSlug($package->title);
            }
        });

        static::saving(function ($package) {
            if (blank($package->slug) && filled($package->title)) {
                $package->slug = static::generateUniqueSlug($package->title, $package->id);
            }
        });
    }

    public static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);

        if ($baseSlug === '') {
            $baseSlug = 'package';
        }

        $slug = $baseSlug;
        $counter = 2;

        while (static::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
