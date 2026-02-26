<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailTemplate extends Model
{
    protected $fillable = [
        'key',
        'name',
        'subject_template',
        'header_title',
        'header_tagline',
        'body_intro',
        'policies_text',
        'footer_text',
        'accent_color',
        'is_enabled',
        'is_draft',
        'version',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_draft'   => 'boolean',
        'version'    => 'integer',
    ];

    /**
     * Allowed HTML tags in rich-text fields.
     * This is the whitelist — all other tags are stripped on save.
     */
    public const ALLOWED_HTML_TAGS = '<p><br><strong><em><b><i><ul><ol><li><a><h2><h3><hr><u>';

    /**
     * Strip any disallowed HTML tags from a rich-text string.
     * Safe to call on plain text too (returns it unchanged).
     */
    public static function sanitizeHtml(?string $html): string
    {
        if (!$html) return '';
        return strip_tags($html, self::ALLOWED_HTML_TAGS);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $model) {
            // Sanitize every rich-text field before persisting
            $model->body_intro    = self::sanitizeHtml($model->body_intro);
            $model->policies_text = self::sanitizeHtml($model->policies_text);
            $model->footer_text   = self::sanitizeHtml($model->footer_text);
        });
    }


    /**
     * Supported placeholders that are safe to use in editable fields.
     */
    public const ALLOWED_PLACEHOLDERS = [
        '{booking_code}', '{guest_name}', '{arrival_date}', '{total_thb}', '{booking_type}',
        '{guest_email}', '{guest_whatsapp}', '{guest_phone}',
        '{resort_name}', '{resort_email}', '{resort_whatsapp}', '{resort_phone}', '{resort_address}',
        '{site_name}', '{address}',
        '{contact_email}', '{contact_whatsapp}', '{contact_phone}', '{contact_address}',
        '{whatsapp}', '{email}', '{phone}' // Legacy/shorthand
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function versions(): HasMany
    {
        return $this->hasMany(EmailTemplateVersion::class)->orderByDesc('version');
    }

    // ── Helper: resolve placeholders ─────────────────────────────────────────

    /**
     * Replace safe placeholders in a string with real values.
     * Supports both {key} and {{key}} styles.
     */
    public static function resolvePlaceholders(string $text, array $data): string
    {
        $map = [
            'booking_code'    => $data['booking_code']    ?? '',
            'guest_name'      => $data['guest_name']      ?? '',
            'arrival_date'    => $data['arrival_date']    ?? '',
            'total_thb'       => $data['total_thb']       ?? '',
            'booking_type'    => $data['booking_type']    ?? '',
            'guest_email'     => $data['guest_email']     ?? '',
            'guest_whatsapp'  => $data['guest_whatsapp']  ?? '',
            'guest_phone'     => $data['guest_phone']     ?? '',
            'resort_name'     => $data['resort_name']     ?? '',
            'resort_email'    => $data['resort_email']    ?? '',
            'resort_whatsapp' => $data['resort_whatsapp'] ?? '',
            'resort_phone'    => $data['resort_phone']    ?? '',
            'resort_address'  => $data['resort_address']  ?? '',
            'site_name'       => $data['site_name']       ?? ($data['resort_name'] ?? ''),
            'address'         => $data['address']         ?? ($data['resort_address'] ?? ''),
            'contact_email'   => $data['contact_email']   ?? ($data['resort_email'] ?? ''),
            'contact_whatsapp'=> $data['contact_whatsapp']?? ($data['resort_whatsapp'] ?? ''),
            'contact_phone'   => $data['contact_phone']   ?? ($data['resort_phone'] ?? ''),
            'contact_address' => $data['contact_address'] ?? ($data['resort_address'] ?? ''),
        ];

        // Generic shorthand placeholders.  Prefer an explicit value in `$data` if supplied
        // so that callers (e.g. TemplatedMail) can control whether {email}/{whatsapp}/{phone}
        // refer to the resort or to the guest.  Resort fields are only used as fallbacks.
        $map['whatsapp'] = $data['whatsapp'] ?? $map['contact_whatsapp'] ?? '';
        $map['email']    = $data['email']    ?? $map['contact_email']    ?? '';
        $map['phone']    = $data['phone']    ?? $map['contact_phone']    ?? '';

        return preg_replace_callback('/\{\{?\s*([a-z0-9_]+)\s*\}?\}/i', static function (array $matches) use ($map) {
            $key = strtolower($matches[1] ?? '');
            if (!array_key_exists($key, $map)) {
                return $matches[0];
            }

            return e((string)($map[$key] ?? ''));
        }, $text) ?? $text;
    }

    /**
     * Validate that only allowed placeholders appear in a string.
     * Returns array of invalid placeholders found, or empty array if clean.
     */
    public static function validatePlaceholders(string $text): array
    {
        // match either {key} or {{key}} so that the editor can warn when someone
        // types a placeholder that isn't recognised.  We strip off the surrounding
        // braces when comparing against the allowed list.
        preg_match_all('/\{\{?[a-z_]+\}?\}/', $text, $matches);
        $found = $matches[0] ?? [];
        return array_diff($found, self::ALLOWED_PLACEHOLDERS);
    }

    /**
     * Save a version snapshot (keep last 5).
     */
    public function saveVersion(): void
    {
        $snapshot = $this->only([
            'key', 'name', 'subject_template', 'header_title', 'header_tagline',
            'body_intro', 'policies_text', 'footer_text', 'accent_color',
            'is_enabled', 'is_draft', 'version',
        ]);

        $this->versions()->create([
            'version'  => $this->version,
            'snapshot' => $snapshot,
        ]);

        // Prune to last 5 versions
        $keep = $this->versions()->orderByDesc('version')->pluck('id')->take(5);
        $this->versions()->whereNotIn('id', $keep)->delete();

        // Bump version for next save
        $this->increment('version');
    }

    /**
     * Restore from a version snapshot.
     */
    public function restoreFromVersion(EmailTemplateVersion $templateVersion): void
    {
        $snap = $templateVersion->snapshot;
        $this->fill(collect($snap)->except(['version'])->toArray())->save();
    }
}
