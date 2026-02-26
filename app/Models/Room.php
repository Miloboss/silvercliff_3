<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Room extends Model
{
    protected $fillable = [
        'room_type_id',
        'room_code',
        'zone',
        'sort_order',
        'is_active',
        'notes',
        'price_per_night_thb',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(BookingRoomAssignment::class);
    }
}
