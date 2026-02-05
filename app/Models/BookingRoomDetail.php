<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingRoomDetail extends Model
{
    protected $fillable = [
        'booking_id',
        'check_in',
        'check_out',
        'guests_adults',
        'guests_children',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
