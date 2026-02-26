<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingSchedule extends Model
{
    protected $fillable = [
        'booking_id',
        'day_no',
        'title',
        'description',
        'schedule_date',
        'schedule_time',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'schedule_date' => 'date',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
