<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingScheduleItem extends Model
{
    protected $fillable = [
        'booking_id',
        'title',
        'scheduled_date',
        'scheduled_time',
        'duration_minutes',
        'editable_by_admin',
        'meta',
    ];

    protected $casts = [
        'meta' => 'json',
        'editable_by_admin' => 'boolean',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
