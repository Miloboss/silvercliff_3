<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingTourDetail extends Model
{
    protected $fillable = [
        'booking_id',
        'activity_id',
        'tour_date',
        'tour_time',
        'guests_adults',
        'guests_children',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
