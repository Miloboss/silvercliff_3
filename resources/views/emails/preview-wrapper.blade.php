<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Preview — {{ $template->name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: #1a1f2e;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── TOOLBAR ── */
        .toolbar {
            background: #111827;
            border-bottom: 1px solid #374151;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0,0,0,0.4);
        }

        .toolbar-title {
            color: #f9fafb;
            font-size: 14px;
            font-weight: 600;
            flex: 1;
        }
        .toolbar-title span {
            color: #9ca3af;
            font-weight: 400;
        }

        .btn-group {
            display: flex;
            border: 1px solid #374151;
            border-radius: 8px;
            overflow: hidden;
        }
        .btn-group a {
            padding: 7px 14px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            color: #9ca3af;
            background: #1f2937;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s;
        }
        .btn-group a:hover { background: #374151; color: #f9fafb; }
        .btn-group a.active { background: #3b82f6; color: #fff; }
        .btn-group a + a { border-left: 1px solid #374151; }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            background: #059669;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s;
        }
        .btn-action:hover { background: #047857; }
        .btn-action.back { background: #374151; }
        .btn-action.back:hover { background: #4b5563; }

        /* ── PREVIEW FRAME ── */
        .preview-stage {
            flex: 1;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 30px 20px;
        }

        .preview-frame {
            background: #f0f4f0;
            transition: max-width 0.35s cubic-bezier(0.4,0,0.2,1);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 0 0 1px rgba(255,255,255,0.05), 0 20px 60px rgba(0,0,0,0.5);
        }
        .preview-frame.desktop { width: 100%; max-width: 680px; }
        .preview-frame.mobile  { width: 100%; max-width: 390px; }

        /* mock phone chrome for mobile */
        .phone-chrome {
            display: none;
        }
        .preview-frame.mobile .phone-chrome {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #1f2937;
            padding: 12px;
            gap: 6px;
        }
        .phone-chrome .notch {
            width: 60px;
            height: 6px;
            background: #374151;
            border-radius: 3px;
        }

        .email-iframe-wrap {
            width: 100%;
        }

        /* Inline the actual email content */
        .email-content {
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div class="toolbar-title">
            📧 Preview: <span>{{ $template->name }}</span>
            &nbsp;·&nbsp;
            <span style="color:#6b7280;font-size:12px;">v{{ $template->version }}</span>
            &nbsp;·&nbsp;
            <span style="color:#6b7280;font-size:11px;">Template saved: {{ $template->updated_at?->format('d M Y H:i') ?? 'never' }}</span>
            &nbsp;·&nbsp;
            @php
                $brandingUpdated = \App\Models\SiteSetting::where('group', 'email_branding')->latest('updated_at')->value('updated_at');
            @endphp
            <span style="color:#6b7280;font-size:11px;">Branding updated: {{ $brandingUpdated ? \Carbon\Carbon::parse($brandingUpdated)->format('d M Y H:i:s') : 'never' }}</span>
        </div>

        {{-- Width toggle --}}
        <div class="btn-group">
            <a href="?width=desktop" class="{{ $width === 'desktop' ? 'active' : '' }}">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="14" rx="2"/><path d="M8 20h8M12 18v2"/></svg>
                Desktop
            </a>
            <a href="?width=mobile" class="{{ $width === 'mobile' ? 'active' : '' }}">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="7" y="2" width="10" height="20" rx="2"/><circle cx="12" cy="18" r="1" fill="currentColor" stroke="none"/></svg>
                Mobile
            </a>
        </div>

        {{-- Back to admin --}}
        <a class="btn-action back" href="/admin/email-templates">
            ← Back to Templates
        </a>

        {{-- Edit link --}}
        <a class="btn-action" href="/admin/email-templates/{{ $template->id }}/edit" style="background:#7c3aed;">
            ✏️ Edit Template
        </a>

        {{-- Branding link --}}
        <a class="btn-action" href="/admin/email-branding-settings" style="background:#1d4ed8;">
            🎨 Edit Branding
        </a>
    </div>

    <div class="preview-stage">
        <div class="preview-frame {{ $width }}">
            @if($width === 'mobile')
            <div class="phone-chrome"><div class="notch"></div></div>
            @endif
            <div class="email-content">
                @include('emails.template-mail', [
                    'template'     => $template,
                    'booking'      => $booking ?? null,
                    'placeholders' => $placeholders,
                ])
            </div>
        </div>
    </div>
</body>
</html>
