<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingRoomAssignment extends Model
{
    protected $fillable = [
        'booking_id',
        'room_id',
        'assigned_from',
        'assigned_to',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public static function isRoomOccupied($roomId, $from, $to, $excludeBookingId = null): bool
    {
        return self::where('room_id', $roomId)
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('assigned_from', [$from, $to])
                    ->orWhereBetween('assigned_to', [$from, $to])
                    ->orWhere(function ($q) use ($from, $to) {
                        $q->where('assigned_from', '<=', $from)
                            ->where('assigned_to', '>=', $to);
                    });
            })
            ->when($excludeBookingId, fn($q) => $q->where('booking_id', '!=', $excludeBookingId))
            ->exists();
    }
}
