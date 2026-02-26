<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\VoucherService;

class VoucherPreviewController extends Controller
{
    public function __construct(private VoucherService $voucherService) {}

    /**
     * Stream the PDF in the browser for live preview.
     * Uses the most recent booking (or a demo if none exists).
     */
    public function preview()
    {
        $booking = Booking::with(['roomDetail', 'tourDetail.activity', 'packageDetail.package', 'packageOptions'])
            ->latest()->first() ?? $this->fakeDemoBooking();

        return $this->voucherService->stream($booking);
    }

    /**
     * Download a sample PDF using demo data — no real booking required.
     */
    public function sampleDownload()
    {
        $booking = $this->fakeDemoBooking();
        return $this->voucherService->download($booking);
    }

    // ── Demo booking factory ───────────────────────────────────────────────

    private function fakeDemoBooking(): Booking
    {
        $b = new Booking();
        $b->id           = 0;
        $b->booking_code = 'SC-DEMO-0001';
        $b->booking_type = 'package';
        $b->full_name    = 'John Doe';
        $b->email        = 'guest@example.com';
        $b->whatsapp     = '+66 81 234 5678';
        $b->total_amount = 12500;
        $b->status       = 'confirmed';
        $b->notes        = '';
        // Nullify relations so template handles the else case gracefully
        $b->setRelation('roomDetail',    null);
        $b->setRelation('tourDetail',    null);
        $b->setRelation('packageDetail', null);
        $b->setRelation('packageOptions', collect());
        return $b;
    }
}
