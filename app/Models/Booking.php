<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $fillable = [
        'booking_code',
        'booking_type',
        'status',
        'full_name',
        'whatsapp',
        'email',
        'notes',
        'source',
        'subtotal',
        'total_amount',
        'currency',
        'payment_status',
        'paid_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($booking) {
            if (!$booking->booking_code) {
                $booking->booking_code = self::generateUniqueCode();
            }
        });
    }

    public static function generateUniqueCode()
    {
        do {
            $code = 'SC-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(4));
        } while (self::where('booking_code', $code)->exists());

        return $code;
    }

    public function roomDetail(): HasOne
    {
        return $this->hasOne(BookingRoomDetail::class);
    }

    public function roomAssignments(): HasMany
    {
        return $this->hasMany(BookingRoomAssignment::class);
    }

    public function tourDetail(): HasOne
    {
        return $this->hasOne(BookingTourDetail::class);
    }

    public function packageDetail(): HasOne
    {
        return $this->hasOne(BookingPackageDetail::class);
    }

    public function scheduleItems(): HasMany
    {
        return $this->hasMany(BookingScheduleItem::class);
    }
}
