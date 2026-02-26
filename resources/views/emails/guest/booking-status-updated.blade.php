@extends('emails.layouts.master')

@section('content')
    <p>Dear {{ $booking->full_name }},</p>

    <p>The status of your booking <strong>#{{ $booking->booking_code }}</strong> at <strong>{{ $settings->get('site_name', 'Silver Cliff Resort') }}</strong> has been updated.</p>

    <div style="text-align: center; margin: 30px 0;">
        <span style="font-size: 14px; color: #718096; text-transform: uppercase; font-weight: bold; display: block; margin-bottom: 5px;">Your new status is:</span>
        @php
            $statusClass = match($booking->status) {
                'confirmed' => 'badge-success',
                'cancelled' => 'highlight',
                default => 'badge-info'
            };
        @endphp
        <span class="badge {{ $statusClass }}" style="font-size: 20px; padding: 8px 24px;">{{ strtoupper($booking->status) }}</span>
    </div>

    @if($booking->status === 'confirmed')
        <p>Your reservation is now fully confirmed! We are looking forward to showing you the magic of Khao Sok.</p>
        <div style="background-color: #f0fff4; padding: 20px; border-radius: 6px; border: 1px solid #c6f6d5; margin-bottom: 25px;">
            <p style="margin: 0; color: #276749;">
                <strong>Confirmed Booking Summary:</strong><br>
                Booking ID: #{{ $booking->booking_code }}<br>
                Type: {{ ucfirst($booking->booking_type) }}<br>
                @if($booking->booking_type === 'room' && $booking->roomDetail)
                    Stay: {{ $booking->roomDetail->check_in }} to {{ $booking->roomDetail->check_out }}<br>
                    Guests: {{ $booking->roomDetail->guests_adults }} Adults, {{ $booking->roomDetail->guests_children }} Children
                @elseif($booking->booking_type === 'tour' && $booking->tourDetail)
                    Date: {{ $booking->tourDetail->tour_date }} ({{ $booking->tourDetail->tour_time ?? 'Standard Time' }})<br>
                    Guests: {{ $booking->tourDetail->guests_adults }} Adults, {{ $booking->tourDetail->guests_children }} Children
                @elseif($booking->booking_type === 'package' && $booking->packageDetail)
                    Stay: {{ $booking->packageDetail->check_in }} to {{ $booking->packageDetail->check_out }}<br>
                    Guests: {{ $booking->packageDetail->guests_adults }} Adults, {{ $booking->packageDetail->guests_children }} Children
                @endif
            </p>
        </div>
    @elseif($booking->status === 'cancelled')
        <p>We regret to inform you that your booking request has been cancelled. If you have any questions or would like to rebook for a different date, please contact us.</p>
    @else
        <p>Your booking is currently on hold. We will update you as soon as there is more information.</p>
    @endif

    <p style="padding: 15px; background: #fff5f5; border-left: 4px solid #c53030; color: #742a2a; border-radius: 4px;">
        <strong>Reminder:</strong> Payment is to be settled at the resort reception (External POS).
    </p>

    <div style="text-align: center;">
        <a href="{{ config('app.url') }}/booking-status.html?id={{ $booking->booking_code }}" class="button">Check Booking Status Online</a>
    </div>
@endsection
