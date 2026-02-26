<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmailTemplate;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key'              => 'booking_received_guest',
                'name'             => 'Guest Booking Received',
                'subject_template' => 'Booking Received – #{booking_code} | {site_name}',
                'header_title'     => 'Booking Received',
                'header_tagline'   => 'Thank you for choosing {site_name}!',
                'body_intro'       => "Dear {guest_name},\n\nWe have received your booking request and our team is reviewing availability.\n\nYour booking reference is: {booking_code}\nBooking type: {booking_type}\nArrival date: {arrival_date}\nEstimated total: THB {total_thb}\n\nWe'll contact you shortly with full confirmation. In the meantime, feel free to reach us on WhatsApp: {whatsapp}",
                'policies_text'    => "Payment is not processed online. Final payment is settled at the resort reception.",
                'footer_text'      => "See you in the jungle!\n{site_name} — {address}",
                'accent_color'     => '#1e3a1a',
                'is_enabled'       => true,
                'is_draft'         => false,
            ],
            [
                'key'              => 'booking_confirmation_guest',
                'name'             => 'Guest Booking Confirmation',
                'subject_template' => 'Booking Confirmed – #{booking_code} | {site_name}',
                'header_title'     => 'Booking Confirmed',
                'header_tagline'   => 'Your reservation is now confirmed.',
                'body_intro'       => "Dear {guest_name},\n\nGreat news — your booking is now confirmed.\n\nBooking reference: {booking_code}\nBooking type: {booking_type}\nArrival date: {arrival_date}\nConfirmed total: THB {total_thb}\n\nPlease find your voucher attached to this email.",
                'policies_text'    => "Please bring this voucher at check-in. Payment is settled at the resort reception unless otherwise arranged.",
                'footer_text'      => "We look forward to welcoming you!\n{site_name} — {address}",
                'accent_color'     => '#1e3a1a',
                'is_enabled'       => true,
                'is_draft'         => false,
            ],
            [
                'key'              => 'booking_confirmation_admin',
                'name'             => 'Admin New Booking Alert',
                'subject_template' => '[NEW BOOKING] #{booking_code} – {booking_type} – {guest_name}',
                'header_title'     => 'New Booking Alert',
                'header_tagline'   => 'A new booking has been submitted.',
                'body_intro'       => "A new booking has been submitted via the website.\n\nBooking Code: {booking_code}\nGuest Name: {guest_name}\nBooking Type: {booking_type}\nArrival Date: {arrival_date}\nTotal Amount: THB {total_thb}\nGuest WhatsApp: {whatsapp}\nGuest Email: {email}",
                'policies_text'    => null,
                'footer_text'      => "Log in to admin panel to view and manage this booking.",
                'accent_color'     => '#1e3a1a',
                'is_enabled'       => true,
                'is_draft'         => false,
            ],
            [
                'key'              => 'booking_status_updated',
                'name'             => 'Guest Booking Status Update',
                'subject_template' => 'Your Booking Status Update – #{booking_code}',
                'header_title'     => 'Booking Update',
                'header_tagline'   => 'Your booking status has changed.',
                'body_intro'       => "Dear {guest_name},\n\nThis is an update regarding your booking {booking_code}.\n\nYour booking has been updated. Please log in or contact us if you have questions.\n\nWhatsApp: {whatsapp}\nEmail: {email}",
                'policies_text'    => null,
                'footer_text'      => "{site_name} — {address}",
                'accent_color'     => '#1e3a1a',
                'is_enabled'       => true,
                'is_draft'         => false,
            ],
        ];

        foreach ($templates as $t) {
            EmailTemplate::updateOrCreate(['key' => $t['key']], $t);
        }
    }
}
