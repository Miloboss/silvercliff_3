<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Voucher · {{ $booking->booking_code }}</title>
<style>
/* ── Reset ── */
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: Helvetica, Arial, sans-serif;
    font-size: 13px;
    color: #2d2d2d;
    background: #f5f2ec;
}

/* ── Watermark ── */
.watermark {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 340px;
    opacity: {{ ($brand['watermark_opacity'] ?? 8) / 100 }};
    z-index: -1;
}

/* ── Page wrapper ── */
.page {
    width: 100%;
    min-height: 297mm;
    padding: 0;
    position: relative;
}

/* ── Header ── */
.header {
    {{ $brand['header_style_css'] }}
    padding: 28px 36px 22px;
    position: relative;
}
.header-inner {
    display: table;
    width: 100%;
}
.header-left  { display: table-cell; vertical-align: middle; width: 60%; }
.header-right { display: table-cell; vertical-align: middle; text-align: right; width: 40%; }

.logo { height: {{ $brand['logo_h'] ?? 60 }}px; max-width: 200px; }

.brand-name {
    color: #ffffff;
    font-size: 18px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    margin-top: 8px;
}
.brand-tagline {
    color: {{ $brand['accent_color'] }};
    font-size: 10px;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-top: 3px;
}

.doc-label {
    color: {{ $brand['accent_color'] }};
    font-size: 10px;
    letter-spacing: 3px;
    text-transform: uppercase;
    font-weight: 600;
}
.doc-code {
    color: #ffffff;
    font-size: 22px;
    font-weight: 800;
    letter-spacing: 2px;
    margin-top: 4px;
}
.doc-date {
    color: rgba(255,255,255,0.65);
    font-size: 10px;
    margin-top: 4px;
}

/* ── Gold divider ── */
.gold-divider {
    height: 2px;
    background: linear-gradient(to right, {{ $brand['accent_color'] }}, transparent);
}

/* ── Body section ── */
.body { padding: 28px 36px; }

/* ── Two-column info ── */
.info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
.info-table td { vertical-align: top; padding: 0 16px 0 0; width: 50%; }
.info-table td:last-child { padding-right: 0; border-left: 1px solid #d6c99a; padding-left: 20px; }

.section-label {
    font-size: 9px;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    color: {{ $brand['accent_color'] }};
    font-weight: 700;
    margin-bottom: 8px;
    padding-bottom: 4px;
    border-bottom: 1px solid {{ $brand['accent_color'] }}44;
}

.info-name  { font-size: 16px; font-weight: 700; color: #1a1a1a; margin-bottom: 4px; }
.info-line  { font-size: 12px; color: #555; margin-bottom: 2px; }
.info-label { color: #888; font-size: 11px; }

.status-badge {
    display: inline-block;
    padding: 3px 12px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    background: {{ $brand['accent_color'] }}22;
    color: {{ $brand['accent_color'] }};
    border: 1px solid {{ $brand['accent_color'] }}55;
    margin-top: 6px;
}

/* ── Booking details card ── */
.details-card {
    background: #ffffff;
    border-radius: {{ $brand['card_radius'] ?? 12 }}px;
    border: 1px solid #e8dfc8;
    margin-bottom: 20px;
    overflow: hidden;
}
.details-card-header {
    background: {{ $brand['primary_color'] }}0d;
    padding: 12px 20px;
    border-bottom: 1px solid #e8dfc8;
}
.details-card-header p {
    font-size: 9px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: {{ $brand['primary_color'] }};
    font-weight: 700;
}
.details-card-body { padding: 0; }

.details-row {
    display: table;
    width: 100%;
    border-collapse: collapse;
}
.details-row td {
    padding: 12px 20px;
    border-bottom: 1px solid #f0ebe0;
    font-size: 12px;
    vertical-align: middle;
}
.details-row td:first-child { color: #888; width: 40%; font-weight: 600; }
.details-row td:last-child  { color: #1a1a1a; font-weight: 500; }
.details-row:last-child td  { border-bottom: none; }

/* ── Total section ── */
.total-bar {
    background: {{ $brand['primary_color'] }};
    border-radius: {{ $brand['card_radius'] ?? 12 }}px;
    padding: 16px 24px;
    margin-bottom: 20px;
    display: table;
    width: 100%;
}
.total-bar-left  { display: table-cell; vertical-align: middle; }
.total-bar-right { display: table-cell; vertical-align: middle; text-align: right; }
.total-label {
    font-size: 10px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: {{ $brand['accent_color'] }};
    font-weight: 600;
}
.total-sub {
    color: rgba(255,255,255,0.6);
    font-size: 10px;
    margin-top: 2px;
}
.total-amount {
    font-size: 26px;
    font-weight: 800;
    color: #ffffff;
}
.total-currency {
    font-size: 13px;
    color: {{ $brand['accent_color'] }};
    font-weight: 600;
    margin-left: 4px;
}

/* ── Policies ── */
.policies {
    background: #fffbf0;
    border-left: 3px solid {{ $brand['accent_color'] }};
    border-radius: 0 6px 6px 0;
    padding: 12px 16px;
    margin-bottom: 20px;
    font-size: 11px;
    color: #5a4a2a;
    line-height: 1.7;
}
.policies strong { color: #3a2a10; }

/* ── Contact box ── */
.contact-box {
    background: #ffffff;
    border: 1px solid #e8dfc8;
    border-radius: {{ $brand['card_radius'] ?? 12 }}px;
    padding: 16px 20px;
    margin-bottom: 20px;
}
.contact-grid { display: table; width: 100%; }
.contact-col  { display: table-cell; width: 33.33%; vertical-align: top; padding-right: 12px; }
.contact-col:last-child { padding-right: 0; }
.contact-icon { font-size: 10px; color: {{ $brand['accent_color'] }}; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
.contact-val  { font-size: 11px; color: #333; font-weight: 600; }

/* ── QR + Signature ── */
.qr-sig-table { display: table; width: 100%; margin-bottom: 20px; }
.qr-cell      { display: table-cell; vertical-align: top; width: 36%; padding-right: 20px; }
.sig-cell     { display: table-cell; vertical-align: bottom; text-align: right; }
.qr-label     { font-size: 9px; color: #999; letter-spacing: 1px; text-transform: uppercase; margin-top: 6px; text-align: center; }
.sig-line     { border-top: 1px solid #ccc; width: 180px; margin-left: auto; padding-top: 6px; }
.sig-name     { font-size: 11px; color: #555; text-align: right; }
.sig-title    { font-size: 9px; color: #999; letter-spacing: 1px; text-transform: uppercase; text-align: right; }

/* ── Footer ── */
.footer {
    {{ $brand['header_style_css'] }}
    padding: 14px 36px;
    text-align: center;
}
.footer p { color: rgba(255,255,255,0.75); font-size: 10px; letter-spacing: 1px; }
.footer .footer-brand { color: {{ $brand['accent_color'] }}; font-weight: 700; font-size: 11px; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 4px; }
</style>
</head>
<body>
<div class="page">

    {{-- Watermark --}}
    @if($brand['watermark_enabled'] && $brand['logo_base64_pdf'])
    <img class="watermark" src="{{ $brand['logo_base64_pdf'] }}" alt="">
    @endif

    {{-- ══ HEADER ═══════════════════════════════════════════════════════════ --}}
    <div class="header">
        <div class="header-inner">
            <div class="header-left">
                @if($brand['logo_base64_pdf'])
                <img src="{{ $brand['logo_base64_pdf'] }}" class="logo" alt="{{ $brand['name'] }}">
                @endif
                <div class="brand-name">{{ $brand['name'] }}</div>
                <div class="brand-tagline">{{ $brand['tagline'] }}</div>
            </div>
            <div class="header-right">
                <div class="doc-label">Booking Voucher</div>
                <div class="doc-code">#{{ $booking->booking_code }}</div>
                <div class="doc-date">Issued: {{ now()->format('d F Y') }}</div>
            </div>
        </div>
    </div>
    <div class="gold-divider"></div>

    {{-- ══ BODY ══════════════════════════════════════════════════════════════ --}}
    <div class="body">

        {{-- Guest & Reservation info --}}
        <table class="info-table">
            <tr>
                <td>
                    <div class="section-label">Guest Details</div>
                    <div class="info-name">{{ $booking->full_name }}</div>
                    @if($booking->whatsapp)
                    <div class="info-line">📱 {{ $booking->whatsapp }}</div>
                    @endif
                    @if($booking->email)
                    <div class="info-line">✉️ {{ $booking->email }}</div>
                    @endif
                </td>
                <td>
                    <div class="section-label">Reservation Info</div>
                    <div class="info-line"><span class="info-label">Type:</span> {{ ucfirst($booking->booking_type) }} Booking</div>
                    <div class="info-line"><span class="info-label">Issued:</span> {{ now()->format('d M Y, H:i') }}</div>
                    <div class="info-line"><span class="info-label">Payment:</span> At Resort (External POS)</div>
                    <div><span class="status-badge">{{ ucfirst($booking->status) }}</span></div>
                </td>
            </tr>
        </table>

        {{-- ── Booking Details Card ──────────────────────────────────────────── --}}
        <div class="details-card">
            <div class="details-card-header">
                <p>Booking Details</p>
            </div>
            <div class="details-card-body">
                @if($booking->booking_type === 'room' && $booking->roomDetail)
                @php
                    $ci = \Carbon\Carbon::parse($booking->roomDetail->check_in);
                    $co = \Carbon\Carbon::parse($booking->roomDetail->check_out);
                    $nights = $ci->diffInDays($co);
                @endphp
                <table class="details-row" style="width:100%;border-collapse:collapse;">
                    <tr><td>Description</td><td>Room Accommodation, {{ $nights }} Night(s)</td></tr>
                    <tr><td>Check-in</td><td>{{ $ci->format('d F Y') }}</td></tr>
                    <tr><td>Check-out</td><td>{{ $co->format('d F Y') }}</td></tr>
                    <tr><td>Guests</td><td>{{ $booking->roomDetail->guests_adults }} Adults, {{ $booking->roomDetail->guests_children }} Children</td></tr>
                </table>

                @elseif($booking->booking_type === 'tour' && $booking->tourDetail)
                <table class="details-row" style="width:100%;border-collapse:collapse;">
                    <tr><td>Activity</td><td><strong>{{ $booking->tourDetail->activity?->title ?? 'Tour' }}</strong></td></tr>
                    <tr><td>Date</td><td>{{ \Carbon\Carbon::parse($booking->tourDetail->tour_date)->format('d F Y') }}</td></tr>
                    @if($booking->tourDetail->tour_time)
                    <tr><td>Time</td><td>{{ $booking->tourDetail->tour_time }}</td></tr>
                    @endif
                    <tr><td>Guests</td><td>{{ $booking->tourDetail->guests_adults }} Adults, {{ $booking->tourDetail->guests_children }} Children</td></tr>
                </table>

                @elseif($booking->booking_type === 'package' && $booking->packageDetail)
                <table class="details-row" style="width:100%;border-collapse:collapse;">
                    <tr><td>Package</td><td><strong>{{ $booking->packageDetail->package?->title ?? 'Package' }}</strong></td></tr>
                    <tr><td>Arrival</td><td>{{ \Carbon\Carbon::parse($booking->packageDetail->check_in)->format('d F Y') }}</td></tr>
                    <tr><td>Departure</td><td>{{ \Carbon\Carbon::parse($booking->packageDetail->check_out)->format('d F Y') }}</td></tr>
                    <tr><td>Guests</td><td>{{ $booking->packageDetail->guests_adults }} Adults, {{ $booking->packageDetail->guests_children }} Children</td></tr>
                    @if($booking->packageOptions && $booking->packageOptions->count())
                    <tr><td>Options</td><td>{{ $booking->packageOptions->pluck('name')->join(', ') }}</td></tr>
                    @endif
                </table>
                @endif

                @if($booking->notes)
                <div style="padding:12px 20px;border-top:1px solid #f0ebe0;font-size:11px;color:#666;">
                    <strong>Guest Notes:</strong> {{ $booking->notes }}
                </div>
                @endif
            </div>
        </div>

        {{-- ── Total ─────────────────────────────────────────────────────────── --}}
        <div class="total-bar">
            <div class="total-bar-left">
                <div class="total-label">Total Amount</div>
                <div class="total-sub">Payable at resort reception</div>
            </div>
            <div class="total-bar-right">
                <span class="total-amount">{{ number_format($booking->total_amount, 2) }}</span>
                <span class="total-currency">THB</span>
            </div>
        </div>

        {{-- ── Policies ─────────────────────────────────────────────────────── --}}
        <div class="policies">
            <strong>Important:</strong><br>
            @if(!empty($brand['terms_text']))
                {!! nl2br(e($brand['terms_text'])) !!}
            @else
                · This voucher is a reservation confirmation only — not a payment receipt.<br>
                · Final payment is processed via External POS at the resort reception.<br>
                · Please present this document (digital or printed) at check-in.
            @endif
        </div>

        {{-- ── Contact Box ──────────────────────────────────────────────────── --}}
        <div class="contact-box">
            <div class="section-label" style="margin-bottom:10px;">Contact &amp; Location</div>
            <div class="contact-grid">
                <div class="contact-col">
                    <div class="contact-icon">📱 WhatsApp</div>
                    <div class="contact-val">{{ $brand['whatsapp'] ?: '—' }}</div>
                </div>
                <div class="contact-col">
                    <div class="contact-icon">✉️ Email</div>
                    <div class="contact-val">{{ $brand['email'] ?: '—' }}</div>
                </div>
                <div class="contact-col">
                    <div class="contact-icon">📍 Address</div>
                    <div class="contact-val">{{ $brand['address'] ?: '—' }}</div>
                </div>
            </div>
        </div>

        {{-- ── QR Code + Signature ───────────────────────────────────────────── --}}
        <div class="qr-sig-table">
            <div class="qr-cell">
                <img src="{{ $qr_url }}" width="100" height="100" alt="QR Code">
                <div class="qr-label">Scan to check booking status</div>
            </div>
            <div class="sig-cell">
                @if($brand['signature_base64'])
                <img src="{{ $brand['signature_base64'] }}" height="40" alt="Signature" style="margin-bottom:4px;">
                @endif
                <div class="sig-line">
                    <div class="sig-name">{{ $brand['signature_name'] }}</div>
                    <div class="sig-title">{{ $brand['name'] }}</div>
                </div>
            </div>
        </div>

    </div>{{-- /body --}}

    {{-- ══ FOOTER ════════════════════════════════════════════════════════════ --}}
    <div class="footer">
        <div class="footer-brand">{{ $brand['name'] }}</div>
        <p>{{ $brand['address'] }} &nbsp;·&nbsp; {{ $brand['whatsapp'] }} &nbsp;·&nbsp; {{ $brand['email'] }}</p>
        
        @if(!empty($brand['footer_text']))
        <p style="margin-top:4px;">{!! nl2br(e($brand['footer_text'])) !!}</p>
        @else
        <p style="margin-top:4px;">&copy; {{ date('Y') }} {{ $brand['name'] }}. All rights reserved.</p>
        @endif
    </div>

</div>
</body>
</html>
