<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\SiteSetting;
use App\Mail\GuestBookingReceivedMail;
use App\Mail\AdminNewBookingMail;
use App\Mail\GuestBookingStatusUpdatedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class BookingObserver
{
    /**
     * Ensure the model is fully saved to the DB before triggering jobs.
     */
    public $afterCommit = true;

    /**
     * Handle the Booking "created" event.
     */
    public function created(Booking $booking): void
    {
        try {
            // Send to Guest
            if (!empty($booking->email)) {
                Mail::to($booking->email)->queue(new GuestBookingReceivedMail($booking));
            }

            // Send to Admin
            $adminEmail = $this->getAdminEmail();

            if ($adminEmail) {
                Mail::to($adminEmail)->queue(new AdminNewBookingMail($booking));
            }
        } catch (\Exception $e) {
            Log::error("Failed to queue booking creation emails for #{$booking->booking_code}: " . $e->getMessage());
        }
    }

    /**
     * Handle the Booking "updated" event.
     */
    public function updated(Booking $booking): void
    {
        // Only trigger if status has changed
        if ($booking->isDirty('status')) {
            try {
                if (!empty($booking->email)) {
                    Mail::to($booking->email)->queue(new GuestBookingStatusUpdatedMail($booking));
                }
            } catch (\Exception $e) {
                Log::error("Failed to queue status update email for #{$booking->booking_code}: " . $e->getMessage());
            }
        }
    }

    /**
     * Helper to resolve the admin notification recipient.
     */
    private function getAdminEmail(): ?string
    {
        return SiteSetting::where('key', 'admin_notifications_email')->value('value') 
            ?? SiteSetting::where('key', 'contact_email')->value('value')
            ?? config('mail.from.address');
    }
}
