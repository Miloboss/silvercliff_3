/**
 * site-settings.js
 * ─────────────────────────────────────────────────────────────────────────────
 * Shared branding + contact injector for ALL static pages.
 * Include this script on every HTML page (before closing </body>).
 *
 * What it does:
 *   1. Fetches /api/settings once per browser session (cached in sessionStorage)
 *   2. Applies branding: logo, site name, tagline → all .brand-logo, .brand-title, .brand-sub
 *   3. Applies contact: email, whatsapp, phone, address → data-setting / data-setting-link attrs
 *   4. Updates footer copyright year + brand name
 *   5. Gracefully falls back to existing static content if API fails
 *
 * DOM conventions used across pages:
 *   .brand-logo           → <img> logo src
 *   .brand-title          → site name text
 *   .brand-sub            → tagline text
 *   [data-setting="X"]    → text content from contact.X
 *   [data-setting-link="X"] → href from contact.X (tel:, mailto:, wa.me)
 *   .footer-brand-name    → site name in footer copyright
 *   #year                 → current year
 */

(function () {
    'use strict';

    const CACHE_KEY = 'sc_site_settings';
    const CACHE_TTL = 5 * 60 * 1000; // 5 minutes
    const BRANDING_STATE = window.__SCBrandingState || {
        lastBrandName: '',
        lastLogoUrl: '',
    };
    window.__SCBrandingState = BRANDING_STATE;

    // ── Helpers ────────────────────────────────────────────────────────────────

    function formatWhatsAppHref(raw) {
        if (!raw) return null;
        // Strip everything except digits and leading +
        const digits = raw.replace(/[^\d+]/g, '');
        // wa.me needs country code without +
        const number = digits.startsWith('+') ? digits.slice(1) : digits;
        return `https://wa.me/${number}`;
    }

    function formatTelHref(raw) {
        if (!raw) return null;
        return `tel:${raw.replace(/[^\d+]/g, '')}`;
    }

    function cleanText(value) {
        if (typeof value !== 'string') return '';
        return value.trim();
    }

    function isUsableLogoCandidate(value) {
        if (typeof value !== 'string') return false;
        const trimmed = value.trim();
        if (!trimmed) return false;
        if (trimmed === '/' || trimmed === '/storage' || trimmed === '/storage/') return false;
        if (trimmed.toLowerCase() === 'null' || trimmed.toLowerCase() === 'undefined') return false;
        return true;
    }

    function normalizeLogoUrl(value) {
        if (!isUsableLogoCandidate(value)) return '';
        if (value.startsWith('http://') || value.startsWith('https://') || value.startsWith('/')) {
            return value;
        }
        return `/storage/${value.replace(/^\/+/, '')}`;
    }

    function getExistingBrandName(root = document) {
        const byClass = root.querySelector('.brand-title');
        if (byClass && cleanText(byClass.textContent)) return cleanText(byClass.textContent);
        const byId = root.getElementById('siteName');
        if (byId && cleanText(byId.textContent)) return cleanText(byId.textContent);
        return '';
    }

    function getExistingTagline(root = document) {
        const byClass = root.querySelector('.brand-sub');
        if (byClass && cleanText(byClass.textContent)) return cleanText(byClass.textContent);
        const byId = root.getElementById('siteTagline');
        if (byId && cleanText(byId.textContent)) return cleanText(byId.textContent);
        return '';
    }

    function getExistingLogoSrc(root = document) {
        const logo = root.querySelector('.brand-logo, .brand-img');
        if (!logo) return './logo.png';
        return logo.getAttribute('src') || logo.src || './logo.png';
    }

    function resolveBranding(data, root = document) {
        const branding = data?.branding || {};
        const existingBrandName = getExistingBrandName(root);
        const existingTagline = getExistingTagline(root);
        const existingLogoSrc = getExistingLogoSrc(root);

        const resolvedBrandName =
            cleanText(branding.brand_name)
            || cleanText(branding.site_name)
            || cleanText(branding.resort_name)
            || existingBrandName
            || 'SILVER CLIFF RESORT';

        const resolvedTagline = cleanText(branding.tagline) || existingTagline;

        const apiLogoCandidate = normalizeLogoUrl(
            branding.logo_url
            ?? branding.navbar_logo_url
            ?? branding.global_logo_url
            ?? ''
        );

        const resolvedLogoUrl = isUsableLogoCandidate(apiLogoCandidate)
            ? apiLogoCandidate
            : existingLogoSrc;

        return {
            resolvedBrandName,
            resolvedTagline,
            resolvedLogoUrl,
            existingLogoSrc,
        };
    }

    function probeImage(url) {
        return new Promise((resolve) => {
            if (!url) {
                resolve(false);
                return;
            }

            const img = new Image();
            let settled = false;
            const finalize = (ok) => {
                if (settled) return;
                settled = true;
                resolve(ok);
            };

            const timeout = setTimeout(() => finalize(false), 4000);

            img.onload = () => {
                clearTimeout(timeout);
                finalize(true);
            };

            img.onerror = () => {
                clearTimeout(timeout);
                finalize(false);
            };

            img.src = url;
        });
    }

    async function applySiteBranding(data, options = {}) {
        const { resolvedBrandName, resolvedTagline, resolvedLogoUrl, existingLogoSrc } = resolveBranding(data, document);

        if (resolvedBrandName) {
            document.querySelectorAll('.brand-title').forEach(el => {
                el.textContent = resolvedBrandName;
            });
            ['siteName', 'siteNameMobile', 'siteNameContact', 'contactBrandName'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.textContent = resolvedBrandName;
            });
            document.querySelectorAll('.footer-brand-name').forEach(el => {
                el.textContent = resolvedBrandName;
            });
        }

        if (resolvedTagline) {
            document.querySelectorAll('.brand-sub').forEach(el => {
                el.textContent = resolvedTagline;
            });
            ['siteTagline', 'siteTaglineMobile'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.textContent = resolvedTagline;
            });
        }

        const titleEl = document.getElementById('siteTitle');
        if (titleEl && resolvedBrandName) {
            titleEl.textContent = resolvedBrandName + (resolvedTagline ? ` — ${resolvedTagline}` : '');
        }

        const logoNodes = document.querySelectorAll('.brand-logo, .brand-img');
        if (logoNodes.length) {
            const shouldProbeNewLogo = !!resolvedLogoUrl
                && resolvedLogoUrl !== existingLogoSrc
                && resolvedLogoUrl !== BRANDING_STATE.lastLogoUrl;

            let finalLogoUrl = resolvedLogoUrl || existingLogoSrc || './logo.png';
            if (shouldProbeNewLogo) {
                const logoOk = await probeImage(resolvedLogoUrl);
                if (!logoOk) {
                    finalLogoUrl = existingLogoSrc || './logo.png';
                }
            }

            logoNodes.forEach((img) => {
                if (img.tagName !== 'IMG') return;

                const currentSrc = img.getAttribute('src') || img.src || './logo.png';
                const fallbackSrc = img.getAttribute('data-logo-fallback') || currentSrc || './logo.png';
                img.setAttribute('data-logo-fallback', fallbackSrc);

                if (finalLogoUrl && finalLogoUrl !== currentSrc) {
                    img.src = finalLogoUrl;
                }

                img.onerror = function () {
                    this.onerror = null;
                    this.src = this.getAttribute('data-logo-fallback') || './logo.png';
                };
            });

            BRANDING_STATE.lastLogoUrl = finalLogoUrl;
        }

        BRANDING_STATE.lastBrandName = resolvedBrandName;

        return {
            source: options.source || 'site-settings.js',
            resolvedBrandName,
            resolvedTagline,
            resolvedLogoUrl: BRANDING_STATE.lastLogoUrl || resolvedLogoUrl,
        };
    }

    window.resolveSiteBranding = resolveBranding;
    window.applySiteBranding = applySiteBranding;

    // ── Apply settings to DOM ──────────────────────────────────────────────────

    async function applySettings(data) {
        const contact = data.contact || {};

        await applySiteBranding(data, { source: 'site-settings.js' });

        // ── 4. Contact: text via data-setting ─────────────────────────────────────
        //   Supported keys: email, whatsapp, phone, address
        document.querySelectorAll('[data-setting]').forEach(el => {
            const key = el.dataset.setting;
            const value = contact[key];
            if (value) el.textContent = value;
        });

        // ── 5. Contact: links via data-setting-link ───────────────────────────────
        document.querySelectorAll('[data-setting-link]').forEach(el => {
            const key = el.dataset.settingLink;
            const value = contact[key];
            if (!value) return;
            if (key === 'whatsapp') {
                el.href = formatWhatsAppHref(value);
                el.target = '_blank';
                el.rel = 'noopener noreferrer';
            } else if (key === 'phone') {
                el.href = formatTelHref(value);
            } else if (key === 'email') {
                el.href = `mailto:${value}`;
            } else {
                el.href = value;
            }
        });

        // ── 6. Navbar WhatsApp button (if present) ────────────────────────────────
        const navWa = document.getElementById('navWhatsappBtn');
        if (navWa && contact.whatsapp) {
            navWa.href = formatWhatsAppHref(contact.whatsapp);
        }

        // ── 7. Social links ───────────────────────────────────────────────────────
        if (contact.facebook) {
            const el = document.getElementById('fbLink');
            if (el) { el.href = contact.facebook; el.classList.remove('d-none'); }
        }
        if (contact.instagram) {
            const el = document.getElementById('igLink');
            if (el) { el.href = contact.instagram; el.classList.remove('d-none'); }
        }

        if (contact.google_maps_url) {
            const mapBtn = document.querySelector('a[href*="maps.google.com"]');
            if (mapBtn) mapBtn.href = contact.google_maps_url;
        }

        // ── 8. Map iframe ─────────────────────────────────────────────────────────
        if (contact.map_url || contact.google_maps_iframe_url) {
            const url = contact.map_url || contact.google_maps_iframe_url;
            const mapFrame = document.querySelector('.map-frame iframe') || document.getElementById('contactMap');
            if (mapFrame) {
                mapFrame.src = url;
                // If there's a container that was hidden, show it
                const container = document.getElementById('mapContainer');
                if (container) container.style.display = 'block';
            }
        }

        // ── 9. Year ──────────────────────────────────────────────────────────────
        document.querySelectorAll('#year').forEach(el => {
            el.textContent = new Date().getFullYear();
        });
    }

    // ── Fetch with sessionStorage cache ───────────────────────────────────────

    function loadAndApply() {
        try {
            const cached = sessionStorage.getItem(CACHE_KEY);
            if (cached) {
                const { ts, data } = JSON.parse(cached);
                if (Date.now() - ts < CACHE_TTL) {
                    applySettings(data);
                    return;
                }
            }
        } catch (_) { /* ignore parse errors */ }

        fetch('/api/settings')
            .then(r => r.ok ? r.json() : Promise.reject(r.status))
            .then(data => {
                try {
                    sessionStorage.setItem(CACHE_KEY, JSON.stringify({ ts: Date.now(), data }));
                } catch (_) { /* storage full — ignore */ }
                applySettings(data);
            })
            .catch(err => {
                console.warn('[site-settings] Could not load settings:', err);
                // Still set the year even if API fails
                document.querySelectorAll('#year').forEach(el => {
                    el.textContent = new Date().getFullYear();
                });
            });
    }

    // ── Boot ──────────────────────────────────────────────────────────────────
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadAndApply);
    } else {
        loadAndApply(); // DOM already ready
    }

})();
