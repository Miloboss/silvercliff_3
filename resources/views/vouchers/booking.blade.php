<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Booking Voucher - {{ $booking->booking_code }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #333; line-height: 1.6; }
        .header { text-align: center; border-bottom: 2px solid #2d5a27; padding-bottom: 10px; margin-bottom: 20px; }
        .logo { font-size: 24px; font-weight: bold; color: #2d5a27; }
        .voucher-title { font-size: 18px; text-transform: uppercase; letter-spacing: 2px; margin-top: 5px; }
        .section { margin-bottom: 20px; }
        .section-title { font-weight: bold; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 10px; color: #2d5a27; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 8px; vertical-align: top; }
        .label { font-weight: bold; width: 150px; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; border-top: 1px solid #eee; padding-top: 20px; }
        .amount { font-size: 20px; font-weight: bold; color: #2d5a27; }
        .note { background: #f9f9f9; padding: 15px; border-left: 4px solid #8cb33a; font-style: italic; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">SILVER CLIFF RESORT</div>
        <div class="voucher-title">Booking Voucher</div>
    </div>

    <div class="section">
        <div class="section-title">Reservation Details</div>
        <table>
            <tr>
                <td class="label">Booking Code:</td>
                <td><strong>{{ $booking->booking_code }}</strong></td>
            </tr>
            <tr>
                <td class="label">Booking Type:</td>
                <td>{{ ucfirst($booking->booking_type) }}</td>
            </tr>
            <tr>
                <td class="label">Status:</td>
                <td>{{ ucfirst($booking->status) }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Customer Information</div>
        <table>
            <tr>
                <td class="label">Name:</td>
                <td>{{ $booking->full_name }}</td>
            </tr>
            <tr>
                <td class="label">WhatsApp:</td>
                <td>{{ $booking->whatsapp }}</td>
            </tr>
            @if($booking->email)
            <tr>
                <td class="label">Email:</td>
                <td>{{ $booking->email }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="section">
        <div class="section-title">Stay / Tour Details</div>
        <table>
            @if($booking->booking_type === 'room')
                <tr>
                    <td class="label">Check-in:</td>
                    <td>{{ $booking->roomDetail->check_in }}</td>
                </tr>
                <tr>
                    <td class="label">Check-out:</td>
                    <td>{{ $booking->roomDetail->check_out }}</td>
                </tr>
                <tr>
                    <td class="label">Guests:</td>
                    <td>{{ $booking->roomDetail->guests_adults }} Adults, {{ $booking->roomDetail->guests_children }} Children</td>
                </tr>
            @elseif($booking->booking_type === 'tour')
                <tr>
                    <td class="label">Activity:</td>
                    <td>{{ $booking->tourDetail->activity->title }}</td>
                </tr>
                <tr>
                    <td class="label">Date:</td>
                    <td>{{ $booking->tourDetail->tour_date }}</td>
                </tr>
                <tr>
                    <td class="label">Time:</td>
                    <td>{{ $booking->tourDetail->tour_time ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Guests:</td>
                    <td>{{ $booking->tourDetail->guests_adults }} Adults, {{ $booking->tourDetail->guests_children }} Children</td>
                </tr>
            @elseif($booking->booking_type === 'package')
                <tr>
                    <td class="label">Package:</td>
                    <td>{{ $booking->packageDetail->package->title }}</td>
                </tr>
                <tr>
                    <td class="label">Check-in:</td>
                    <td>{{ $booking->packageDetail->check_in }}</td>
                </tr>
                <tr>
                    <td class="label">Check-out:</td>
                    <td>{{ $booking->packageDetail->check_out }}</td>
                </tr>
                <tr>
                    <td class="label">Guests:</td>
                    <td>{{ $booking->packageDetail->guests_adults }} Adults, {{ $booking->packageDetail->guests_children }} Children</td>
                </tr>
            @endif
        </table>
    </div>

    <div class="section">
        <div class="section-title">Financial Summary</div>
        <table>
            <tr>
                <td class="label">Expected Total:</td>
                <td class="amount">{{ number_format($booking->total_amount, 2) }} THB</td>
            </tr>
        </table>
        <div class="note">
            Important: Payment handled at reception (external POS). This voucher is a confirmation of reservation details and expected revenue.
        </div>
    </div>

    @if($booking->notes)
    <div class="section">
        <div class="section-title">Notes</div>
        <p>{{ $booking->notes }}</p>
    </div>
    @endif

    <div class="footer">
        <p>Silver Cliff Resort, Khao Sok, Surat Thani, Thailand</p>
        <p>Contact: +66 84 845 3550 | silvercliff_resort@hotmail.com</p>
        <p>Generated on {{ now()->format('Y-m-d H:i') }}</p>
    </div>
</body>
</html>
