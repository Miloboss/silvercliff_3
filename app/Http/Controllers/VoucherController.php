<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\VoucherService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    protected $voucherService;

    public function __construct(VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }

    /**
     * Generate and download the PDF voucher for a specific booking.
     */
    public function download(Booking $booking)
    {
        $data = $this->voucherService->getVoucherData($booking);
        
        $pdf = Pdf::loadView('documents.voucher', $data);
        
        // Settings for print-safe
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("Voucher-{$booking->booking_code}.pdf");
    }
}
