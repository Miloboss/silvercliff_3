<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Voucher - {{ $booking->booking_code }}</title>
    <style>
        {!! $css !!}
    </style>
</head>
<body>
    @if($brand['logo_base64'])
        <!-- Background Watermark -->
        <img src="{{ $brand['logo_base64'] }}" class="watermark" alt="Watermark">
    @endif

    <div class="voucher-container">
        <!-- Header -->
        <div class="header">
            <div class="brand-section">
                <div class="logo-container">
                    @if($brand['logo_base64'])
                        <img src="{{ $brand['logo_base64'] }}" class="logo" alt="Logo">
                    @endif
                </div>
                <h1 class="site-name">{{ $brand['name'] }}</h1>
                <div class="site-tagline">{{ $brand['tagline'] }}</div>
            </div>
            <div class="doc-info">
                <div class="doc-type">VOUCHER</div>
                <div class="doc-number">#{{ $booking->booking_code }}</div>
            </div>
            <div class="clear"></div>
        </div>

        <!-- Info Grid -->
        <div class="info-section">
            <div class="info-col">
                <div class="section-title">Guest Details</div>
                <div class="info-text"><strong>{{ $booking->full_name }}</strong></div>
                @if($booking->whatsapp)
                    <div class="info-text">{{ $booking->whatsapp }}</div>
                @endif
                @if($booking->email)
                    <div class="info-text">{{ $booking->email }}</div>
                @endif
            </div>
            <div class="info-col">
                <div class="section-title">Reservation Info</div>
                <div class="info-text"><span class="info-label">Issued Date:</span> {{ now()->format('d M Y') }}</div>
                <div class="info-text"><span class="info-label">Category:</span> {{ ucfirst($booking->booking_type) }} Stay</div>
                <div class="info-text"><span class="info-label">Status:</span> {{ ucfirst($booking->status) }}</div>
            </div>
            <div class="clear"></div>
        </div>

        <!-- Details Table -->
        <table class="details-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Schedule / Details</th>
                    <th style="text-align: right;">Capacity</th>
                </tr>
            </thead>
            <tbody>
                @if($booking->booking_type === 'room' && $booking->roomDetail)
                    <tr>
                        <td>
                            <strong>Room Accommodation</strong><br>
                            Check-in: {{ $booking->roomDetail->check_in }}<br>
                            Check-out: {{ $booking->roomDetail->check_out }}
                        </td>
                        <td>
                            @php
                                $checkIn = \Carbon\Carbon::parse($booking->roomDetail->check_in);
                                $checkOut = \Carbon\Carbon::parse($booking->roomDetail->check_out);
                                $nights = $checkIn->diffInDays($checkOut);
                            @endphp
                            {{ $nights }} Night(s) Stay
                        </td>
                        <td class="amount">
                            {{ $booking->roomDetail->guests_adults }} Adults<br>
                            {{ $booking->roomDetail->guests_children }} Children
                        </td>
                    </tr>
                @elseif($booking->booking_type === 'tour' && $booking->tourDetail)
                    <tr>
                        <td>
                            <strong>Tour: {{ $booking->tourDetail->activity?->title ?? 'Activity' }}</strong><br>
                            Date: {{ $booking->tourDetail->tour_date }}
                        </td>
                        <td>
                            @if($booking->tourDetail->tour_time)
                                Time: {{ $booking->tourDetail->tour_time }}
                            @else
                                Full Day Tour
                            @endif
                        </td>
                        <td class="amount">
                            {{ $booking->tourDetail->guests_adults }} Adults<br>
                            {{ $booking->tourDetail->guests_children }} Children
                        </td>
                    </tr>
                @elseif($booking->booking_type === 'package' && $booking->packageDetail)
                    <tr>
                        <td>
                            <strong>Package: {{ $booking->packageDetail->package?->title ?? 'Package' }}</strong><br>
                            Check-in: {{ $booking->packageDetail->check_in }}
                        </td>
                        <td>
                            Check-out: {{ $booking->packageDetail->check_out }}
                        </td>
                        <td class="amount">
                            {{ $booking->packageDetail->guests_adults }} Adults<br>
                            {{ $booking->packageDetail->guests_children }} Children
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>

        <!-- Summary -->
        <div class="summary-container">
            <div class="summary-row total">
                <div class="summary-label">TOTAL (THB)</div>
                <div class="summary-value">{{ number_format($booking->total_amount, 2) }}</div>
                <div class="clear"></div>
            </div>
        </div>
        <div class="clear"></div>

        <!-- Notes -->
        <div class="notes-section">
            <strong>REMARKS / POLICIES:</strong><br>
            - This voucher is a reservation confirmation only.<br>
            - Final payment is processed via External POS at the resort.<br>
            - Please present this document (digital or printed) at reception upon check-in.
            @if($booking->notes)
                <br><br><strong>GUEST NOTES:</strong><br>
                {{ $booking->notes }}
            @endif
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-text">
                {{ $brand['address'] }}<br>
                Email: {{ $brand['email'] }} | WhatsApp: {{ $brand['whatsapp'] }}
            </div>
        </div>
    </div>
</body>
</html>
