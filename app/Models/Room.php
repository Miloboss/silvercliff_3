<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = [
        'room_code',
        'zone',
        'sort_order',
        'is_active',
        'notes',
        'price_per_night_thb',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(BookingRoomAssignment::class);
    }
}
