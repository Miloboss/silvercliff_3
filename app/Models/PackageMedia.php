<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageMedia extends Model
{
    protected $fillable = [
        'package_id',
        'file_path',
        'caption',
        'sort_order',
        'type'
    ];

    protected $appends = ['file_url'];

    public function getFileUrlAttribute()
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
