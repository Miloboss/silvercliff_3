<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageItinerary extends Model
{
    protected $fillable = ['package_id', 'day_no', 'title', 'description'];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }}
