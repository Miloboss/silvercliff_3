<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\EmailTemplate;
use App\Models\SiteSetting;
use App\Services\VoucherService;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * TemplatedMail
 *
 * IMPORTANT NOTES:
 * (1) Do NOT name a static factory method "build()" — Laravel's Mailable base class
 *     also has a build() hook that the IoC container tries to inject into. If your
 *     method has a string parameter like build(string $key), the container will throw
 *     BindingResolutionException. Use "make()" instead.
 *
 * (2) Mail dispatch is queued from controller/actions (Mail::to()->queue()) to keep
 *     booking flows responsive and avoid frontend pending/spinner hangs.
 */
class TemplatedMail extends Mailable
{
    use SerializesModels;

    public EmailTemplate $template;
    public ?Booking      $booking;
    public array         $placeholders;
    public bool          $attachVoucher;

    /**
     * Static factory — find an enabled template by key and return a TemplatedMail.
     * Returns null if the template is disabled or not found.
     *
     * NOTE: Named "make" not "build" to avoid conflict with Mailable::build().
     */
    public static function make(string $key, ?Booking $booking = null, bool $attachVoucher = false): ?self
    {
        $template = EmailTemplate::where('key', $key)
            ->where('is_enabled', true)
            ->first();

        if (!$template) {
            Log::debug("[TemplatedMail] No enabled template for key [{$key}]");
            return null;
        }

        return new self($template, $booking, $attachVoucher);
    }


    public function __construct(EmailTemplate $template, ?Booking $booking = null, bool $attachVoucher = false)
    {
        $this->template      = $template;
        $this->booking       = $booking;
        $this->attachVoucher = $attachVoucher;

        $s = SiteSetting::pluck('value', 'key');
        
        // Canonical Resort Info
        $resortName     = $s->get('resort_name',     $s->get('brand_name', $s->get('site_name', 'Silver Cliff Resort')));
        $resortEmail    = $s->get('resort_email',    $s->get('contact_email', $s->get('email', '')));
        $resortWhatsapp = $s->get('resort_whatsapp', $s->get('contact_whatsapp', $s->get('whatsapp_number', $s->get('whatsapp', ''))));
        $resortPhone    = $s->get('resort_phone',    $s->get('contact_phone', $s->get('phone', '')));
        $resortAddress  = $s->get('resort_address',  $s->get('contact_address', $s->get('map_location', $s->get('address', 'Khao Sok, Thailand'))));

        // Clear bogus fallback values that were hardcoded in older settings templates.
        // We want empty strings rather than things like "000000" so the template renderer
        // doesn't happily show placeholder junk in outgoing mail.
        $resortPhone = self::sanitizeContactValue($resortPhone);
        $resortWhatsapp = self::sanitizeContactValue($resortWhatsapp);
        $resortEmail = self::sanitizeContactValue($resortEmail);

        // Determine whether the template we're sending is meant for an internal/admin
        // audience.  Keys for admin templates currently all contain the substring
        // "admin"; this is used to flip the meaning of generic placeholders like
        // {email}/{whatsapp}/{phone} to refer to the *guest* instead of the resort.
        $isAdminMail = str_contains(strtolower($template->key), 'admin');

        $this->placeholders = [
            'booking_code' => $booking?->booking_code ?? 'SC-PREVIEW',
            'guest_name'   => $booking?->full_name    ?? 'Valued Guest',
            'arrival_date' => self::resolveArrivalDate($booking),
            'total_thb'    => $booking ? number_format((float) $booking->total_amount, 2) : '0.00',
            'booking_type' => $booking ? ucfirst($booking->booking_type) : 'Room',
            
            // Guest Info
            'guest_email'    => $booking?->email    ?? '',
            'guest_whatsapp' => $booking?->whatsapp ?? '',
            'guest_phone'    => $booking?->whatsapp ?? '', // whatsapp is often used as phone

            // Resort Info
            'resort_name'     => $resortName,
            'resort_email'    => $resortEmail,
            'resort_whatsapp' => $resortWhatsapp,
            'resort_phone'    => $resortPhone,
            'resort_address'  => $resortAddress,
        ];

        // Shorthand fields can mean either resort contact or guest contact depending
        // on who the message is addressed to.  We favour explicit guest values when
        // constructing an admin mailing, otherwise resort values are used by default.
        if ($isAdminMail) {
            $this->placeholders['email']    = $booking?->email    ?? '';
            $this->placeholders['whatsapp'] = $booking?->whatsapp ?? '';
            $this->placeholders['phone']    = $booking?->whatsapp ?? '';
        } else {
            $this->placeholders['email']    = $resortEmail;
            $this->placeholders['whatsapp'] = $resortWhatsapp;
            $this->placeholders['phone']    = $resortPhone;
        }

        $this->placeholders['site_name'] = $resortName;
        $this->placeholders['address']   = $resortAddress;
        $this->placeholders['contact_email'] = $resortEmail;
        $this->placeholders['contact_whatsapp'] = $resortWhatsapp;
        $this->placeholders['contact_phone'] = $resortPhone;
        $this->placeholders['contact_address'] = $resortAddress;
    }

    public function envelope(): Envelope
    {
        $subject = EmailTemplate::resolvePlaceholders(
            $this->template->subject_template ?? 'Booking Confirmation',
            $this->placeholders
        );
        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.template-mail', with: [
            'template'     => $this->template,
            'booking'      => $this->booking,
            'placeholders' => $this->placeholders,
        ]);
    }

    public function attachments(): array
    {
        if (!$this->attachVoucher || !$this->booking) {
            return [];
        }

        try {
            $pdfContent = app(VoucherService::class)->getPdfContent($this->booking);
            return [
                Attachment::fromData(
                    fn () => $pdfContent,
                    "Voucher-{$this->booking->booking_code}.pdf"
                )->withMime('application/pdf'),
            ];
        } catch (\Exception $e) {
            Log::warning('[TemplatedMail] PDF attachment failed: ' . $e->getMessage());
            return [];
        }
    }

    private static function resolveArrivalDate(?Booking $booking): string
    {
        if (!$booking) return now()->addDays(14)->toDateString();
        return match($booking->booking_type) {
            'room'    => $booking->roomDetail?->check_in    ?? '',
            'tour'    => $booking->tourDetail?->tour_date   ?? '',
            'package' => $booking->packageDetail?->check_in ?? '',
            default   => '',
        };
    }

    private static function sanitizeContactValue(?string $value): string
    {
        $clean = trim((string) $value);
        if ($clean === '') {
            return '';
        }

        if (str_contains($clean, '000000')) {
            return '';
        }

        return $clean;
    }
}
