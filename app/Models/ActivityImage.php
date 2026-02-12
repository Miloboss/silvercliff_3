<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityImage extends Model
{
    protected $fillable = ['activity_id', 'image_path', 'sort_order'];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
}
