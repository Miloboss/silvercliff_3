<?php

namespace Tests\Feature;

use App\Mail\TemplatedMail;
use App\Models\Booking;
use App\Models\EmailTemplate;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        // create minimal templates used by the controller; fields can be mostly empty
        EmailTemplate::create([
            'key' => 'booking_confirmation_guest',
            'name' => 'Guest confirmation',
            'subject_template' => 'Your booking {booking_code}',
            'body_intro' => 'Hello {guest_name}, resort phone {resort_phone} email {resort_email}',
            'version' => 1,
            'is_enabled' => true,
            'is_draft' => false,
        ]);
        EmailTemplate::create([
            'key' => 'booking_confirmation_admin',
            'name' => 'Admin notice',
            'subject_template' => 'New booking {booking_code}',
            'body_intro' => 'Guest contact: {email} / {whatsapp}',
            'version' => 1,
            'is_enabled' => true,
            'is_draft' => false,
        ]);
    }

    public function test_booking_emails_are_queued_and_variables_resolve()
    {
        // set a known resort phone/email so we can check they appear in guest mail
        SiteSetting::create(['key' => 'resort_phone', 'value' => '12345']);
        SiteSetting::create(['key' => 'resort_email', 'value' => 'resort@example.com']);

        $payload = [
            'booking_type' => 'room',
            'full_name' => 'Alice',
            'whatsapp' => '+6699999999',
            'email' => 'alice@example.com',
            'check_in' => '2026-03-01',
            'check_out' => '2026-03-05',
            'adults' => 1,
            'children' => 0,
        ];

        $this->postJson('/api/bookings', $payload)
            ->assertStatus(201)
            ->assertJsonStructure(['booking_code', 'status', 'booking_type']);

        $this->assertDatabaseHas('bookings', ['whatsapp' => '+6699999999']);

        // exactly two templated mails should have been sent (guest + admin)
        Mail::assertSent(TemplatedMail::class, 2);

        // check guest email contains resort contact info and no raw placeholder
        Mail::assertSent(TemplatedMail::class, function (TemplatedMail $mail) {
            if ($mail->template->key !== 'booking_confirmation_guest') {
                return false;
            }
            $rendered = $mail->render();
            return str_contains($rendered, '12345')
                && str_contains($rendered, 'resort@example.com')
                && ! str_contains($rendered, '{email}');
        });

        // check admin email uses guest contact shorthand rather than resort
        Mail::assertSent(TemplatedMail::class, function (TemplatedMail $mail) {
            if ($mail->template->key !== 'booking_confirmation_admin') {
                return false;
            }
            $rendered = $mail->render();
            return str_contains($rendered, 'alice@example.com')
                && str_contains($rendered, '+6699999999');
        });
    }

    public function test_duplicate_submissions_do_not_produce_extra_emails()
    {
        $payload = [
            'booking_type' => 'room',
            'full_name' => 'Bob',
            'whatsapp' => '+6611111111',
            'email' => 'bob@example.com',
            'check_in' => '2026-03-10',
            'check_out' => '2026-03-12',
            'adults' => 2,
            'children' => 0,
        ];

        $this->postJson('/api/bookings', $payload)->assertStatus(201);
        $this->postJson('/api/bookings', $payload)
            ->assertStatus(200)
            ->assertJson(['is_duplicate' => true]);

        // still only two mails sent total
        Mail::assertSent(TemplatedMail::class, 2);
    }

    public function test_site_setting_changes_affect_future_emails()
    {
        SiteSetting::create(['key' => 'resort_phone', 'value' => 'APPLE']);

        $payload = [
            'booking_type' => 'tour',
            'full_name' => 'Carol',
            'whatsapp' => '+6622222222',
            'email' => 'carol@example.com',
            'activity_id' => 1,
            'tour_date' => '2026-04-01',
            'adults' => 1,
            'children' => 0,
        ];

        // create a dummy activity so validation passes
        \App\Models\Activity::create([
            'id' => 1,
            'title' => 'Dummy',
            'description' => 'Dummy activity for email test.',
            'price_thb' => 500,
        ]);

        $this->postJson('/api/bookings', $payload)->assertStatus(201);

        Mail::assertSent(TemplatedMail::class, function (TemplatedMail $mail) {
            return str_contains($mail->render(), 'APPLE');
        });
    }

    public function test_confirm_action_flow_is_idempotent_and_queues_single_voucher_email()
    {
        $booking = Booking::create([
            'booking_type' => 'room',
            'status' => 'pending',
            'full_name' => 'Daisy',
            'whatsapp' => '+6633333333',
            'email' => 'daisy@example.com',
            'source' => 'website',
            'subtotal' => 3000,
            'total_amount' => 3000,
            'currency' => 'THB',
            'payment_status' => 'unpaid',
        ]);

        $booking->roomDetail()->create([
            'check_in' => '2026-05-01',
            'check_out' => '2026-05-03',
            'guests_adults' => 2,
            'guests_children' => 0,
        ]);

        $first = Booking::confirmAndQueueGuestVoucher($booking->id);
        $second = Booking::confirmAndQueueGuestVoucher($booking->id);

        $this->assertSame('queued', $first['state']);
        $this->assertSame('already_sent', $second['state']);

        $booking->refresh();
        $this->assertSame('confirmed', $booking->status);
        $this->assertNotNull($booking->guest_confirmation_sent_at);

        Mail::assertSent(TemplatedMail::class, 1);
        Mail::assertSent(TemplatedMail::class, function (TemplatedMail $mail) use ($booking) {
            return $mail->template->key === 'booking_confirmation_guest'
                && $mail->attachVoucher === true
                && $mail->booking?->id === $booking->id
                && count($mail->attachments()) === 1;
        });
    }

    public function test_full_create_and_confirm_flow_is_single_send_per_stage(): void
    {
        $payload = [
            'booking_type' => 'room',
            'full_name' => 'Evan',
            'whatsapp' => '+6644444444',
            'email' => 'evan@example.com',
            'check_in' => '2026-06-01',
            'check_out' => '2026-06-03',
            'adults' => 2,
            'children' => 0,
        ];

        $created = $this->postJson('/api/bookings', $payload)
            ->assertStatus(201)
            ->assertJson(['success' => true])
            ->json();

        $this->postJson('/api/bookings', $payload)
            ->assertStatus(200)
            ->assertJson(['is_duplicate' => true]);

        // create stage should still result in only 2 mails sent (guest + admin)
        Mail::assertSent(TemplatedMail::class, 2);

        $booking = Booking::findOrFail($created['booking_id']);
        $first = Booking::confirmAndQueueGuestVoucher($booking->id);
        $second = Booking::confirmAndQueueGuestVoucher($booking->id);

        $this->assertSame('queued', $first['state']);
        $this->assertSame('already_sent', $second['state']);

        // +1 guest confirmation with voucher after admin confirm path
        Mail::assertSent(TemplatedMail::class, 3);
        Mail::assertSent(TemplatedMail::class, function (TemplatedMail $mail) use ($booking) {
            return $mail->template->key === 'booking_confirmation_guest'
                && $mail->attachVoucher === true
                && $mail->booking?->id === $booking->id
                && count($mail->attachments()) === 1;
        });
    }
}
