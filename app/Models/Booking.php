<?php

namespace App\Models;

use App\Mail\TemplatedMail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
        'guest_notification_sent_at',
        'admin_notification_sent_at',
        'guest_confirmation_sent_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'guest_notification_sent_at' => 'datetime',
        'admin_notification_sent_at' => 'datetime',
        'guest_confirmation_sent_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($booking) {
            if (!$booking->booking_code) {
                $booking->booking_code = self::generateUniqueCode();
            }
        });

        static::updated(function (self $booking) {
            if ($booking->wasChanged('status') && $booking->status === 'confirmed') {
                $booking->ensureTripPlanGenerated();
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

    public function schedules(): HasMany
    {
        return $this->hasMany(BookingSchedule::class)->orderBy('sort_order');
    }

    public function packageOptions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(PackageOption::class, 'booking_package_options');
    }

    public function ensureTripPlanGenerated(bool $force = false): void
    {
        if (!$force && $this->schedules()->exists()) {
            return;
        }

        if ($force) {
            $this->schedules()->delete();
        }

        if (!$force && $this->schedules()->exists()) {
            return;
        }

        $this->loadMissing([
            'roomDetail',
            'tourDetail.activity',
            'packageDetail.package.itineraries',
            'packageOptions',
            'scheduleItems',
        ]);

        if ($this->scheduleItems->isNotEmpty()) {
            $legacyItems = $this->scheduleItems
                ->sortBy(fn ($item) => ($item->scheduled_date ?? '9999-12-31') . ' ' . ($item->scheduled_time ?? '23:59:59'))
                ->values();

            $payload = [];
            foreach ($legacyItems as $index => $item) {
                $payload[] = [
                    'day_no' => $index + 1,
                    'title' => $item->title,
                    'description' => null,
                    'schedule_date' => $item->scheduled_date,
                    'schedule_time' => $item->scheduled_time,
                    'status' => 'planned',
                    'sort_order' => $index + 1,
                ];
            }

            if (!empty($payload)) {
                $this->schedules()->createMany($payload);
                return;
            }
        }

        $items = [];

        if ($this->booking_type === 'room' && $this->roomDetail) {
            $checkIn = Carbon::parse($this->roomDetail->check_in)->toDateString();
            $items[] = [
                'day_no' => 1,
                'title' => 'Check-in',
                'description' => 'Welcome to Silver Cliff Resort.',
                'schedule_date' => $checkIn,
                'schedule_time' => '14:00',
                'status' => 'planned',
                'sort_order' => 1,
            ];

            if ($this->roomDetail->check_out) {
                $items[] = [
                    'day_no' => 2,
                    'title' => 'Check-out',
                    'description' => 'Thank you for staying with us.',
                    'schedule_date' => Carbon::parse($this->roomDetail->check_out)->toDateString(),
                    'schedule_time' => '11:00',
                    'status' => 'planned',
                    'sort_order' => 2,
                ];
            }
        }

        if ($this->booking_type === 'tour' && $this->tourDetail) {
            $items[] = [
                'day_no' => 1,
                'title' => $this->tourDetail->activity?->title ?? 'Tour Activity',
                'description' => 'Your guided activity schedule.',
                'schedule_date' => Carbon::parse($this->tourDetail->tour_date)->toDateString(),
                'schedule_time' => $this->tourDetail->tour_time ?: 'Time to be confirmed',
                'status' => 'planned',
                'sort_order' => 1,
            ];
        }

        if ($this->booking_type === 'package' && $this->packageDetail) {
            $checkIn = Carbon::parse($this->packageDetail->check_in);
            $package = $this->packageDetail->package;
            $itineraries = $package?->itineraries
                ?->sortBy(fn ($item) => sprintf('%05d-%05d', (int) ($item->sort_order ?? 0), (int) ($item->day_no ?? 0)))
                ?->values() ?? collect();

            if ($itineraries->isNotEmpty()) {
                foreach ($itineraries as $index => $itinerary) {
                    $dayNo = (int) ($itinerary->day_no ?: ($index + 1));
                    $description = $itinerary->description;

                    if ($dayNo === 1 && $this->packageOptions->isNotEmpty()) {
                        $options = $this->packageOptions->pluck('name')->implode(', ');
                        $description = trim(($description ? $description . ' ' : '') . "Selected options: {$options}");
                    }

                    $items[] = [
                        'day_no' => $dayNo,
                        'title' => $itinerary->title ?: "Day {$dayNo}",
                        'description' => $description ?: null,
                        'schedule_date' => $checkIn->copy()->addDays(max($dayNo - 1, 0))->toDateString(),
                        'schedule_time' => 'Time to be confirmed',
                        'status' => 'planned',
                        'sort_order' => $index + 1,
                    ];
                }
            } else {
                $durationDays = max((int) ($package?->duration_days ?? 0), 1);
                for ($day = 1; $day <= $durationDays; $day++) {
                    $items[] = [
                        'day_no' => $day,
                        'title' => "Day {$day} Program",
                        'description' => 'Detailed activities will be finalized by our team.',
                        'schedule_date' => $checkIn->copy()->addDays($day - 1)->toDateString(),
                        'schedule_time' => 'Time to be confirmed',
                        'status' => 'planned',
                        'sort_order' => $day,
                    ];
                }
            }
        }

        if (!empty($items)) {
            $this->schedules()->createMany($items);
        }
    }

    /**
     * Confirm a booking and queue a single guest voucher email (idempotent).
     */
    public static function confirmAndQueueGuestVoucher(int $bookingId): array
    {
        return DB::transaction(function () use ($bookingId) {
            $booking = self::query()->lockForUpdate()->find($bookingId);

            if (!$booking) {
                return ['state' => 'missing', 'booking' => null];
            }

            if ($booking->status !== 'confirmed') {
                $booking->forceFill(['status' => 'confirmed'])->saveQuietly();
            }

            $booking->ensureTripPlanGenerated();

            if (!$booking->email) {
                return ['state' => 'no_email', 'booking' => $booking];
            }

            if ($booking->guest_confirmation_sent_at) {
                Log::info("Confirmation email skipped (already sent) for {$booking->email} (Booking: {$booking->booking_code})");
                return ['state' => 'already_sent', 'booking' => $booking];
            }

            $mail = TemplatedMail::make('booking_confirmation_guest', $booking, true);
            if (!$mail) {
                return ['state' => 'template_disabled', 'booking' => $booking];
            }

            Mail::to($booking->email)->queue($mail);
            $sentAt = now();
            self::query()->whereKey($booking->id)->update(['guest_confirmation_sent_at' => $sentAt]);
            $booking->forceFill(['guest_confirmation_sent_at' => $sentAt]);
            Log::info("Confirmation email queued with voucher to {$booking->email} (Booking: {$booking->booking_code})");

            return ['state' => 'queued', 'booking' => $booking];
        });
    }
}
