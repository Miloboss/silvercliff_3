<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Activity;
use App\Models\Package;
use DB;
use Illuminate\Support\Facades\Cache;

class BookingController extends Controller
{
    private function verifyBookingLookup(Booking $booking, ?string $whatsappLast4, ?string $email): bool
    {
        if ($whatsappLast4) {
            return substr((string) $booking->whatsapp, -4) === $whatsappLast4;
        }

        if ($email) {
            return strtolower((string) $booking->email) === strtolower($email);
        }

        return false;
    }

    private function buildBookingLookupResponse(Booking $booking): array
    {
        $details = null;
        $title = 'Silver Cliff Booking';

        if ($booking->booking_type === 'room') {
            $details = $booking->roomDetail;
            $title = 'Room Stay';
        } elseif ($booking->booking_type === 'tour') {
            $details = $booking->tourDetail;
            $title = $booking->tourDetail?->activity?->title ?? 'Tour';
        } elseif ($booking->booking_type === 'package') {
            $details = $booking->packageDetail;
            $title = $booking->packageDetail?->package?->title ?? 'Package';
        }

        $tripPlan = [];
        if ($booking->status === 'confirmed') {
            if ($booking->schedules->isEmpty()) {
                $booking->ensureTripPlanGenerated();
                $booking->load('schedules');
            }

            $tripPlan = $booking->schedules->map(fn ($item) => [
                'day_no' => $item->day_no,
                'title' => $item->title,
                'description' => $item->description,
                'date' => $item->schedule_date?->toDateString(),
                'time' => $item->schedule_time,
                'status' => $item->status,
            ])->values();
        }

        return [
            'booking_code' => $booking->booking_code,
            'status' => $booking->status,
            'booking_type' => $booking->booking_type,
            'package_title' => $title,
            'check_in' => $details->check_in ?? $details->tour_date ?? null,
            'check_out' => $details->check_out ?? null,
            'adults' => $details->guests_adults ?? 0,
            'children' => $details->guests_children ?? 0,
            'updated_at' => $booking->updated_at->toDateTimeString(),
            'trip_plan_available' => $booking->status === 'confirmed',
            'trip_plan' => $tripPlan,
        ];
    }

    public function store(Request $request)
    {
        $request->validate([
            'booking_type' => 'required|in:room,tour,package',
            'full_name' => 'required|string',
            'whatsapp' => 'required|string',
            'email' => 'nullable|email',
            'notes' => 'nullable|string',
        ]);

        $identitySeed = implode('|', [
            strtolower(trim((string) $request->input('whatsapp'))),
            strtolower(trim((string) $request->input('booking_type'))),
            trim((string) ($request->input('arrival_date') ?? $request->input('tour_date') ?? $request->input('check_in') ?? '')),
        ]);
        $submissionMutexKey = 'booking:submit:' . sha1($identitySeed);

        if (!Cache::add($submissionMutexKey, 1, now()->addSeconds(15))) {
            return response()->json([
                'success' => true,
                'message' => 'Booking request is already being processed. Please wait a moment.',
                'booking_id' => null,
                'booking_code' => null,
                'status' => 'pending',
                'is_duplicate' => true,
            ], 200);
        }

        try {
            // ── IDEMPOTENCY CHECK ───────────────────────────────────────────────
            // Prevent duplicate bookings from the same user within 2 minutes
            $existing = Booking::where('whatsapp', $request->whatsapp)
                ->where('booking_type', $request->booking_type)
                ->where('created_at', '>=', now()->subMinutes(2))
                ->first();

            if ($existing) {
                \Illuminate\Support\Facades\Log::info('[BookingController] Duplicate booking blocked', ['booking_code' => $existing->booking_code]);
                return response()->json([
                    'success' => true,
                    'message' => 'Booking already received. We are processing your request.',
                    'booking_id' => $existing->id,
                    'booking_code' => $existing->booking_code,
                    'status' => $existing->status,
                    'is_duplicate' => true,
                ], 200);
            }

            return DB::transaction(function () use ($request) {
                $booking = Booking::create([
                    'booking_type' => $request->booking_type,
                    'status' => 'pending',
                    'full_name' => $request->full_name,
                    'whatsapp' => $request->whatsapp,
                    'email' => $request->email,
                    'notes' => $request->notes,
                    'source' => 'website',
                ]);

                $price = 0;
                if ($request->booking_type === 'room') {
                    $validated = $request->validate([
                        'check_in' => 'required|date',
                        'check_out' => 'required|date|after:check_in',
                        'adults' => 'required|integer|min:1',
                        'children' => 'required|integer|min:0',
                    ]);
                    $booking->roomDetail()->create([
                        'check_in' => $validated['check_in'], 'check_out' => $validated['check_out'],
                        'guests_adults' => $validated['adults'], 'guests_children' => $validated['children'],
                    ]);
                    $start = new \DateTime($validated['check_in']);
                    $end = new \DateTime($validated['check_out']);
                    $nights = $start->diff($end)->days;
                    $roomPrice = \App\Models\Room::where('is_active', true)->first()?->price_per_night_thb ?? 1500;
                    $price = $nights * $roomPrice;
                } 
                elseif ($request->booking_type === 'tour') {
                    $validated = $request->validate([
                        'activity_id' => 'required|exists:activities,id',
                        'tour_date' => 'required|date', 'tour_time' => 'nullable',
                        'adults' => 'required|integer|min:1', 'children' => 'required|integer|min:0',
                    ]);
                    $activity = Activity::find($validated['activity_id']);
                    $price = $activity->price_thb;
                    $booking->tourDetail()->create([
                        'activity_id' => $validated['activity_id'], 'tour_date' => $validated['tour_date'],
                        'tour_time' => $validated['tour_time'] ?? null, 'guests_adults' => $validated['adults'], 'guests_children' => $validated['children'],
                    ]);
                    $booking->scheduleItems()->create([
                        'title' => $activity->title, 'scheduled_date' => $validated['tour_date'],
                        'scheduled_time' => $validated['tour_time'] ?? null, 'editable_by_admin' => false,
                        'meta' => ['activity_id' => $activity->id]
                    ]);
                } 
                elseif ($request->booking_type === 'package') {
                    if ($request->has('check_in') && !$request->has('arrival_date')) {
                        $request->merge(['arrival_date' => $request->check_in]);
                    }
                    $validated = $request->validate([
                        'package_id' => 'required|exists:packages,id', 'arrival_date' => 'required|date',
                        'package_options' => 'nullable|array', 'adults' => 'nullable|integer', 'children' => 'nullable|integer'
                    ]);
                    $package = Package::with(['itineraries', 'options'])->find($validated['package_id']);
                    if ($package->code === 'ULTIMATE-JUNGLE') {
                        if (count($validated['package_options'] ?? []) !== 2) {
                            return response()->json(['message' => 'Please select exactly 2 activities.'], 422);
                        }
                    }
                    $price = $package->price_thb;
                    $nights = $package->duration_nights ?: 3;
                    $checkIn = $validated['arrival_date'];
                    $checkOut = date('Y-m-d', strtotime($checkIn . " + $nights days"));

                    $booking->packageDetail()->create([
                        'package_id' => $validated['package_id'], 'check_in' => $checkIn, 'check_out' => $checkOut,
                        'guests_adults' => $validated['adults'] ?? 2, 'guests_children' => $validated['children'] ?? 0,
                    ]);
                    if (!empty($validated['package_options'])) {
                        $booking->packageOptions()->attach($validated['package_options']);
                    }
                    foreach ($package->itineraries as $itinerary) {
                        $dayOffset = $itinerary->day_no - 1;
                        $date = date('Y-m-d', strtotime($checkIn . " + $dayOffset days"));
                        $title = $itinerary->title;
                        if ($itinerary->day_no === 1 && !empty($validated['package_options'])) {
                            $optionNames = \App\Models\PackageOption::whereIn('id', $validated['package_options'])->pluck('name')->toArray();
                            $title .= " (" . implode(', ', $optionNames) . ")";
                        }
                        $booking->scheduleItems()->create([
                            'title' => $title, 'scheduled_date' => $date, 'editable_by_admin' => true,
                            'meta' => ['itinerary_id' => $itinerary->id]
                        ]);
                    }
                }

                $booking->update([
                    'subtotal' => $price,
                    'total_amount' => $price,
                    'payment_status' => 'unpaid',
                ]);

                // ── EMAIL NOTIFICATIONS ─────────────────────────────────────────
                // Schedule mailing to run after the HTTP response so the UI isn't held up.
                $this->scheduleBookingEmails($booking);

                return response()->json([
                    'success' => true,
                    'message' => 'Booking created successfully.',
                    'booking_id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'status' => $booking->status,
                    'booking_type' => $booking->booking_type,
                    'total_amount' => $booking->total_amount,
                    'currency' => $booking->currency,
                    'is_duplicate' => false,
                ], 201);
            });
        } finally {
            Cache::forget($submissionMutexKey);
        }
    }

    /**
     * Schedule guest/admin notification emails to be sent once the HTTP response has
     * been returned.  We avoid touching the global queue so no worker is required.
     * Timestamps are updated inside the callback so they only mark after an attempt
     * to send has been made.
     */
    private function scheduleBookingEmails(\App\Models\Booking $booking): void
    {
        app()->terminating(function () use ($booking) {
            $adminRecipients = \App\Models\SiteSetting::whereIn('key', [
                'resort_email', 'admin_notifications_email', 'contact_email', 'email',
            ])->pluck('value', 'key');

            $adminEmail = collect([
                $adminRecipients->get('resort_email'),
                $adminRecipients->get('admin_notifications_email'),
                $adminRecipients->get('contact_email'),
                $adminRecipients->get('email'),
                config('mail.from.address'),
            ])->first(static fn ($address) => filled($address) && filter_var($address, FILTER_VALIDATE_EMAIL));

            try {
                DB::transaction(function () use ($booking, $adminEmail) {
                    $lockedBooking = Booking::query()->lockForUpdate()->find($booking->id);
                    if (!$lockedBooking) {
                        return;
                    }

                    if ($adminEmail && !$lockedBooking->admin_notification_sent_at) {
                        $adminMail = \App\Mail\TemplatedMail::make('booking_confirmation_admin', $lockedBooking);
                        if ($adminMail) {
                            \Illuminate\Support\Facades\Mail::to($adminEmail)->send($adminMail);
                            $lockedBooking->forceFill(['admin_notification_sent_at' => now()])->saveQuietly();
                        }
                    }

                    if ($lockedBooking->email && !$lockedBooking->guest_notification_sent_at) {
                        $guestMail = \App\Mail\TemplatedMail::make('booking_received_guest', $lockedBooking)
                            ?? \App\Mail\TemplatedMail::make('booking_confirmation_guest', $lockedBooking);

                        if ($guestMail) {
                            \Illuminate\Support\Facades\Mail::to($lockedBooking->email)->send($guestMail);
                            $lockedBooking->forceFill(['guest_notification_sent_at' => now()])->saveQuietly();
                        }
                    }
                });
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Notification dispatch failed', [
                    'booking' => $booking->booking_code,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    public function check(Request $request, string $booking_code)
    {
        $request->validate([
            'whatsapp_last4' => 'nullable|string|digits:4',
            'email' => 'nullable|email',
        ]);

        if (!$request->whatsapp_last4 && !$request->email) {
            return response()->json([
                'message' => 'Please provide either the last 4 digits of your WhatsApp or your email.',
            ], 422);
        }

        $normalizedCode = strtoupper(trim($booking_code));

        $booking = Booking::with([
            'roomDetail',
            'tourDetail.activity',
            'packageDetail.package',
            'schedules',
        ])->where('booking_code', $normalizedCode)->first();

        if (!$booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        if (!$this->verifyBookingLookup($booking, $request->whatsapp_last4, $request->email)) {
            return response()->json(['message' => 'Verification failed.'], 403);
        }

        return response()->json($this->buildBookingLookupResponse($booking));
    }

    public function status(Request $request)
    {
        $request->validate([
            'booking_code' => 'required|string',
            'whatsapp_last4' => 'nullable|string|digits:4',
            'email' => 'nullable|email',
        ]);

        if (!$request->whatsapp_last4 && !$request->email) {
            return response()->json(['message' => 'Please provide either the last 4 digits of your whatsapp or your email.'], 422);
        }

        $booking = Booking::with(['roomDetail', 'tourDetail.activity', 'packageDetail.package', 'scheduleItems', 'schedules'])
            ->where('booking_code', $request->booking_code)
            ->first();

        if (!$booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        if (!$this->verifyBookingLookup($booking, $request->whatsapp_last4, $request->email)) {
            return response()->json(['message' => 'Verification failed.'], 403);
        }

        return response()->json($this->buildBookingLookupResponse($booking));
    }

}
