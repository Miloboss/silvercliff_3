<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = [
        'code', 'title', 'subtitle', 'price_thb', 'duration_days', 'duration_nights',
        'description', 'includes', 'is_best_offer', 'is_active', 'image_path'
    ];

    protected $casts = [
        'includes' => 'array',
        'is_best_offer' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function itineraries()
    {
        return $this->hasMany(PackageItinerary::class);
    }}
