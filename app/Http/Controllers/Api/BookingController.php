<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Activity;
use App\Models\Package;
use Illuminate\Support\Str;
use DB;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'booking_type' => 'required|in:room,tour,package',
            'full_name' => 'required|string',
            'whatsapp' => 'required|string',
            'email' => 'nullable|email',
            'notes' => 'nullable|string',
        ]);

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

            if ($request->booking_type === 'room') {
                $validated = $request->validate([
                    'check_in' => 'required|date',
                    'check_out' => 'required|date|after:check_in',
                    'adults' => 'required|integer|min:1',
                    'children' => 'required|integer|min:0',
                ]);

                $booking->roomDetail()->create([
                    'check_in' => $validated['check_in'],
                    'check_out' => $validated['check_out'],
                    'guests_adults' => $validated['adults'],
                    'guests_children' => $validated['children'],
                ]);
            } 
            elseif ($request->booking_type === 'tour') {
                $validated = $request->validate([
                    'activity_id' => 'required|exists:activities,id',
                    'tour_date' => 'required|date',
                    'tour_time' => 'nullable',
                    'adults' => 'required|integer|min:1',
                    'children' => 'required|integer|min:0',
                ]);

                $booking->tourDetail()->create([
                    'activity_id' => $validated['activity_id'],
                    'tour_date' => $validated['tour_date'],
                    'tour_time' => $validated['tour_time'],
                    'guests_adults' => $validated['adults'],
                    'guests_children' => $validated['children'],
                ]);

                // Auto-generate schedule
                $activity = Activity::find($validated['activity_id']);
                $booking->scheduleItems()->create([
                    'title' => $activity->title,
                    'scheduled_date' => $validated['tour_date'],
                    'scheduled_time' => $validated['tour_time'],
                    'editable_by_admin' => false,
                    'meta' => ['activity_id' => $activity->id]
                ]);
            } 
            elseif ($request->booking_type === 'package') {
                $validated = $request->validate([
                    'package_id' => 'required|exists:packages,id',
                    'check_in' => 'required|date',
                    'check_out' => 'required|date|after:check_in',
                    'adults' => 'required|integer|min:1',
                    'children' => 'required|integer|min:0',
                ]);

                $booking->packageDetail()->create([
                    'package_id' => $validated['package_id'],
                    'check_in' => $validated['check_in'],
                    'check_out' => $validated['check_out'],
                    'guests_adults' => $validated['adults'],
                    'guests_children' => $validated['children'],
                ]);

                // Auto-generate schedule from itineraries
                $package = Package::with('itineraries')->find($validated['package_id']);
                foreach ($package->itineraries as $itinerary) {
                    $dayOffset = $itinerary->day_no - 1;
                    $date = date('Y-m-d', strtotime($validated['check_in'] . " + $dayOffset days"));
                    
                    $booking->scheduleItems()->create([
                        'title' => $itinerary->title,
                        'scheduled_date' => $date,
                        'editable_by_admin' => true,
                        'meta' => ['itinerary_id' => $itinerary->id]
                    ]);
                }
            }

            return response()->json([
                'booking_code' => $booking->booking_code,
                'status' => $booking->status,
                'booking_type' => $booking->booking_type,
            ], 201);
        });
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

        $booking = Booking::with(['roomDetail', 'tourDetail.activity', 'packageDetail.package', 'scheduleItems'])
            ->where('booking_code', $request->booking_code)
            ->first();

        if (!$booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        // Verification logic
        if ($request->whatsapp_last4) {
            $last4 = substr($booking->whatsapp, -4);
            if ($last4 !== $request->whatsapp_last4) {
               return response()->json(['message' => 'Verification failed.'], 403);
            }
        } elseif ($request->email) {
            if (strtolower($booking->email) !== strtolower($request->email)) {
                return response()->json(['message' => 'Verification failed.'], 403);
            }
        }

        $details = null;
        $title = "Silver Cliff Booking";
        
        if ($booking->booking_type === 'room') {
            $details = $booking->roomDetail;
            $title = "Room Stay";
        } elseif ($booking->booking_type === 'tour') {
            $details = $booking->tourDetail;
            $title = $booking->tourDetail->activity->title ?? "Tour";
        } elseif ($booking->booking_type === 'package') {
            $details = $booking->packageDetail;
            $title = $booking->packageDetail->package->title ?? "Package";
        }

        return response()->json([
            'booking_code' => $booking->booking_code,
            'status' => $booking->status,
            'booking_type' => $booking->booking_type,
            'package_title' => $title,
            'check_in' => $details->check_in ?? $details->tour_date ?? null,
            'check_out' => $details->check_out ?? null,
            'adults' => $details->guests_adults ?? 0,
            'children' => $details->guests_children ?? 0,
            'updated_at' => $booking->updated_at->toDateTimeString(),
            'schedule' => $booking->scheduleItems,
        ]);
    }

}
