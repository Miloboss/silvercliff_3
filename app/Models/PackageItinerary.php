<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageItinerary extends Model
{
    protected $fillable = ['package_id', 'day_no', 'title', 'description', 'image_path', 'sort_order'];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
