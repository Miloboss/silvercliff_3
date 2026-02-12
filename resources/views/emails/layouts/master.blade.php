<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f7f6; }
        .container { max-width: 600px; margin: 20px auto; background: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .header { text-align: center; border-bottom: 2px solid #1e3a1a; padding-bottom: 20px; margin-bottom: 25px; }
        .logo { max-height: 60px; margin-bottom: 10px; }
        .title { color: #1e3a1a; font-size: 24px; font-weight: bold; text-transform: uppercase; margin: 0; }
        .content { font-size: 16px; margin-bottom: 30px; }
        .summary-box { background-color: #f9fbfa; padding: 20px; border-radius: 6px; border: 1px solid #e2e8f0; margin-bottom: 25px; }
        .summary-item { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
        .summary-label { color: #718096; font-weight: bold; }
        .summary-value { color: #2d3748; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .badge-info { background-color: #ebf8ff; color: #2b6cb0; }
        .badge-success { background-color: #f0fff4; color: #2f855a; }
        .badge-warning { background-color: #fffaf0; color: #9c4221; }
        .footer { text-align: center; font-size: 12px; color: #a0aec0; border-top: 1px solid #edf2f7; padding-top: 20px; }
        .button { display: inline-block; padding: 12px 24px; background-color: #1e3a1a; color: #ffffff !important; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 20px; }
        .highlight { color: #c53030; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            @php
                $siteName = $settings->get('site_name', 'Silver Cliff Resort');
                $siteTagline = $settings->get('site_tagline', 'The Real Jungle Experience');
                $logo = $settings->get('site_logo', 'simple_web_ui/logo.png');
                $logoUrl = str_starts_with($logo, 'http') ? $logo : asset($logo);
            @endphp
            <img src="{{ $logoUrl }}" class="logo" alt="{{ $siteName }}">
            <div class="title">{{ $siteName }}</div>
            <div style="font-size: 12px; color: #718096;">{{ $siteTagline }}</div>
        </div>

        <div class="content">
            @yield('content')
        </div>

        <div class="footer">
            <p>
                <strong>{{ $siteName }}</strong><br>
                {{ $settings->get('map_location', 'Khao Sok, Thailand') }}<br>
                Email: {{ $settings->get('contact_email', $settings->get('email', '')) }}<br>
                WhatsApp: {{ $settings->get('whatsapp_number', '') }}
            </p>
            <p>&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
