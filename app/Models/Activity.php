<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = ['title', 'description', 'cover_image', 'price_thb', 'time_slots', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'time_slots' => 'array',
    ];

    protected $appends = ['cover_image_url'];

    public function getCoverImageUrlAttribute()
    {
        return $this->cover_image ? asset('storage/' . $this->cover_image) : null;
    }

    public function images()
    {
        return $this->hasMany(ActivityImage::class)->orderBy('sort_order');
    }
}
