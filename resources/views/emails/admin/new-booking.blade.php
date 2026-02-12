@extends('emails.layouts.master')

@section('content')
    <p style="color: #c53030; font-weight: bold; font-size: 18px;">New Booking Notification</p>

    <p>A new booking request has been submitted through the website. Please review and manage this booking in the admin panel.</p>

    <div class="summary-box">
        <h3 style="margin-top: 0; font-size: 18px; color: #1e3a1a;">Guest Details</h3>
        <div class="summary-item">
            <span class="summary-label">Name:</span>
            <span class="summary-value">{{ $booking->full_name }}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Email:</span>
            <span class="summary-value">{{ $booking->email }}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">WhatsApp:</span>
            <span class="summary-value">{{ $booking->whatsapp }}</span>
        </div>
    </div>

    <div class="summary-box">
        <h3 style="margin-top: 0; font-size: 18px; color: #1e3a1a;">Booking Details</h3>
        <div class="summary-item">
            <span class="summary-label">Booking Code:</span>
            <span class="summary-value" style="font-weight: bold;">#{{ $booking->booking_code }}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Type:</span>
            <span class="summary-value">{{ ucfirst($booking->booking_type) }}</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Total:</span>
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
        @if($booking->notes)
            <div style="margin-top: 10px;">
                <span class="summary-label">Guest Notes:</span><br>
                <div style="font-size: 13px; color: #4a5568; margin-top: 5px; padding: 10px; background: #fff; border: 1px solid #e2e8f0; border-radius: 4px;">{{ $booking->notes }}</div>
            </div>
        @endif
    </div>

    <div style="text-align: center;">
        <a href="{{ config('app.url') }}/admin/bookings/{{ $booking->id }}/edit" class="button">View in Admin Panel</a>
    </div>
@endsection
