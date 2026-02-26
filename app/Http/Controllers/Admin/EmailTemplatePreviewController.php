<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TemplatedMail;
use App\Models\Booking;
use App\Models\EmailTemplate;

class EmailTemplatePreviewController extends Controller
{
    /**
     * Render a live preview of the template in the browser with a toolbar.
     * Uses the SAME Blade view as real mail send — guarantees WYSIWYG.
     */
    public function preview(EmailTemplate $emailTemplate)
    {
        $width = request('width', 'desktop');

        // Build a realistic fake booking so the summary section renders
        $fakeBooking = $this->buildFakeBooking();

        // Create TemplatedMail with the REAL template + fake booking
        // This ensures placeholders resolve identically to real send
        $mail = new TemplatedMail($emailTemplate, $fakeBooking);

        return view('emails.preview-wrapper', [
            'template'     => $emailTemplate,
            'booking'      => $fakeBooking,
            'placeholders' => $mail->placeholders,
            'width'        => $width,
        ]);
    }

    /**
     * Preview the first enabled template (used by branding page preview button).
     */
    public function firstPreview()
    {
        $template = EmailTemplate::where('is_enabled', true)->first();
        if (!$template) {
            return response('<html><body style="font-family:sans-serif;padding:40px"><h2>No enabled templates found.</h2><p>Create and enable an email template first.</p></body></html>');
        }
        return $this->preview($template);
    }

    /**
     * Branding preview — alias to firstPreview.
     */
    public function brandingPreview()
    {
        return $this->firstPreview();
    }

    /**
     * Build a realistic fake Booking for preview rendering.
     * Sets an ->id so summary section is shown, but is never persisted.
     */
    private function buildFakeBooking(): Booking
    {
        $b = new Booking();
        $b->id           = 999;  // non-zero so summary box renders
        $b->booking_code = 'SC-PREVIEW-0001';
        $b->booking_type = 'package';
        $b->status       = 'pending';
        $b->full_name    = 'John Doe';
        $b->email        = 'guest@example.com';
        $b->whatsapp     = '+66 84 845 3550';
        $b->total_amount = 12500;

        // Stub related detail so Carbon::parse doesn't crash
        $b->setRelation('roomDetail', null);
        $b->setRelation('tourDetail', null);
        $b->setRelation('packageDetail', null);

        return $b;
    }
}
