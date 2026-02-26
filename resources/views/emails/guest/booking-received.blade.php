@extends('emails.layouts.master')

@section('content')
    <p>Dear {{ $booking->full_name }},</p>

    <p>Thank you for choosing <strong>{{ $settings->get('site_name', 'Silver Cliff Resort') }}</strong>! We have received your booking request and our team is currently reviewing the availability.</p>

    <div class="summary-box">
        <h3 style="margin-top: 0; font-size: 18px; color: #1e3a1a;">Booking Summary</h3>
        <div class="summary-item">
            <span class="summary-label">Booking Code:</span>
            <span class="summary-value" style="font-weight: bold; color: #1e3a1a;">#{{ $booking->booking_code }}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Booking Type:</span>
            <span class="summary-value">{{ ucfirst($booking->booking_type) }}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Total Amount:</span>
            <span class="summary-value" style="font-weight: bold;">THB {{ number_format($booking->total_amount, 2) }}</span>
        </div>

        @if($booking->booking_type === 'room' && $booking->roomDetail)
            <div class="summary-item">
                <span class="summary-label">Dates:</span>
                <span class="summary-value">{{ $booking->roomDetail->check_in }} to {{ $booking->roomDetail->check_out }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">Guests:</span>
                <span class="summary-value">{{ $booking->roomDetail->guests_adults }} Adults, {{ $booking->roomDetail->guests_children }} Children</span>
            </div>
        @elseif($booking->booking_type === 'tour' && $booking->tourDetail)
            <div class="summary-item">
                <span class="summary-label">Date:</span>
                <span class="summary-value">{{ $booking->tourDetail->tour_date }} ({{ $booking->tourDetail->tour_time ?? 'Standard Time' }})</span>
            </div>
             <div class="summary-item">
                <span class="summary-label">Guests:</span>
                <span class="summary-value">{{ $booking->tourDetail->guests_adults }} Adults, {{ $booking->tourDetail->guests_children }} Children</span>
            </div>
        @elseif($booking->booking_type === 'package' && $booking->packageDetail)
             <div class="summary-item">
                <span class="summary-label">Dates:</span>
                <span class="summary-value">{{ $booking->packageDetail->check_in }} to {{ $booking->packageDetail->check_out }}</span>
            </div>
             <div class="summary-item">
                <span class="summary-label">Guests:</span>
                <span class="summary-value">{{ $booking->packageDetail->guests_adults }} Adults, {{ $booking->packageDetail->guests_children }} Children</span>
            </div>
        @endif
        <div class="summary-item">
            <span class="summary-label">Status:</span>
            <span class="badge badge-warning">{{ ucfirst($booking->status) }}</span>
        </div>
    </div>

    <p style="padding: 15px; background: #fff5f5; border-left: 4px solid #c53030; color: #742a2a; border-radius: 4px;">
        <strong>Note:</strong> Payment is not processed online. Final payment is to be settled at the resort reception (External POS).
    </p>

    <p>We will contact you shortly with a confirmation. If you have any questions, please feel free to reply to this email or contact us via WhatsApp.</p>

    <p>See you in the jungle!</p>
@endsection
