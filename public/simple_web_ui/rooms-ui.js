/**
 * rooms-ui.js
 * Rooms listing (rooms.html) and Room Details (room-details.html)
 * Fetches data from /api/rooms and /api/rooms/{slug}
 */

const API_BASE = '/api';

// Fallback images per slug if no cover_image is set in DB
const FALLBACK_COVERS = {
    deluxe: 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?auto=format&fit=crop&w=1600&q=80',
    oc: 'https://images.unsplash.com/photo-1510798831971-661eb04b3739?auto=format&fit=crop&w=1600&q=80',
    bungalow: 'https://images.unsplash.com/photo-1596394516093-501ba68a0ba6?auto=format&fit=crop&w=1600&q=80',
    family: 'https://images.unsplash.com/photo-1586105251261-72a756497a11?auto=format&fit=crop&w=1600&q=80',
};

const FALLBACK_GALLERY = {
    deluxe: [
        'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?auto=format&fit=crop&w=800&q=75',
        'https://images.unsplash.com/photo-1540518614846-7eded433c457?auto=format&fit=crop&w=800&q=75',
        'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?auto=format&fit=crop&w=800&q=75',
        'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?auto=format&fit=crop&w=800&q=75',
        'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=800&q=75',
        'https://images.unsplash.com/photo-1560185893-a55cbc8c57e8?auto=format&fit=crop&w=800&q=75',
    ],
    oc: [
        'https://images.unsplash.com/photo-1510798831971-661eb04b3739?auto=format&fit=crop&w=800&q=75',
        'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?auto=format&fit=crop&w=800&q=75',
        'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=800&q=75',
        'https://images.unsplash.com/photo-1484154218962-a197022b5858?auto=format&fit=crop&w=800&q=75',
        'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=800&q=75',
        'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?auto=format&fit=crop&w=800&q=75',
    ],
    bungalow: [
        'https://images.unsplash.com/photo-1596394516093-501ba68a0ba6?auto=format&fit=crop&w=800&q=75',
        'https://images.unsplash.com/photo-1571896349842-33c89424de2d?auto=format&fit=crop&w=800&q=75',
        'https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?auto=format&fit=crop&w=800&q=75',
        'https://images.unsplash.com/photo-1537640538966-79f369143f8f?auto=format&fit=crop&w=800&q=75',
        'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?auto=format&fit=crop&w=800&q=75',
        'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=800&q=75',
    ],
    family: [
        'https://images.unsplash.com/photo-1586105251261-72a756497a11?auto=format&fit=crop&w=800&q=75',
        'https://images.unsplash.com/photo-1631049552057-403cdb8f0658?auto=format&fit=crop&w=800&q=75',
        'https://images.unsplash.com/photo-1566665797739-1674de7a421a?auto=format&fit=crop&w=800&q=75',
        'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=800&q=75',
        'https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?auto=format&fit=crop&w=800&q=75',
        'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=800&q=75',
    ],
};

// ─── Shared Utilities ─────────────────────────────────────────────────────────
function initDrawer() {
    const menuBtn = document.getElementById('menuBtn');
    const drawer = document.getElementById('mobileDrawer');
    const drawerClose = document.getElementById('drawerClose');

    function openDrawer() {
        drawer.classList.add('open');
        drawer.setAttribute('aria-hidden', 'false');
        menuBtn.classList.add('is-open');
        menuBtn.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }
    function closeDrawer() {
        drawer.classList.remove('open');
        drawer.setAttribute('aria-hidden', 'true');
        menuBtn.classList.remove('is-open');
        menuBtn.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }
    menuBtn?.addEventListener('click', () => menuBtn.classList.contains('is-open') ? closeDrawer() : openDrawer());
    drawerClose?.addEventListener('click', closeDrawer);
    drawer?.addEventListener('click', e => { if (e.target === drawer) closeDrawer(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDrawer(); });
}

function initSteppers() {
    document.querySelectorAll('.step-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.target);
            if (!input) return;
            let val = parseInt(input.value);
            if (btn.dataset.step === 'up' && val < parseInt(input.max)) val++;
            if (btn.dataset.step === 'down' && val > parseInt(input.min)) val--;
            input.value = val;
        });
    });
}

function initScrollProgress() {
    const bar = document.querySelector('.scroll-progress__bar');
    if (!bar) return;
    window.addEventListener('scroll', () => {
        const pct = (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100;
        bar.style.width = Math.min(pct, 100) + '%';
    }, { passive: true });
}

function setYear() {
    const el = document.getElementById('year');
    if (el) el.textContent = new Date().getFullYear();
}

function initDateDefaults(checkinId, checkoutId) {
    const today = new Date().toISOString().split('T')[0];
    const cin = document.getElementById(checkinId);
    const cout = document.getElementById(checkoutId);
    if (!cin || !cout) return;
    cin.min = today;
    cin.value = today;
    const tomorrow = new Date(); tomorrow.setDate(tomorrow.getDate() + 1);
    cout.min = tomorrow.toISOString().split('T')[0];
    cout.value = cout.min;
    cin.addEventListener('change', () => {
        const d = new Date(cin.value); d.setDate(d.getDate() + 1);
        cout.min = d.toISOString().split('T')[0];
        if (new Date(cout.value) <= new Date(cin.value)) cout.value = cout.min;
    });
}

function showError(container, message) {
    if (container) container.innerHTML = `
    <div class="glass-card p-5 text-center" style="color:var(--text-dim);">
      <div style="font-size:2rem;margin-bottom:12px;">⚠️</div>
      <p>${message}</p>
      <a href="rooms.html" class="btn btn-outline-light mt-3">← Back to Rooms</a>
    </div>`;
}

// ─── rooms.html Logic ─────────────────────────────────────────────────────────
function initRoomsPage() {
    const desktopGrid = document.getElementById('roomsGridDesktop');
    const mobileCarousel = document.getElementById('roomsCarouselMobile');
    if (!desktopGrid && !mobileCarousel) return;

    // Show skeleton while loading
    const skeletonHTML = Array(4).fill(`
    <div class="room-card glass-card overflow-hidden" style="opacity:0.4;">
      <div class="room-card-img" style="background:rgba(255,255,255,0.05);"></div>
      <div class="room-card-body">
        <div style="height:20px;background:rgba(255,255,255,0.07);border-radius:8px;margin-bottom:12px;"></div>
        <div style="height:14px;background:rgba(255,255,255,0.05);border-radius:8px;width:70%;"></div>
      </div>
    </div>`).join('');
    if (desktopGrid) desktopGrid.innerHTML = skeletonHTML;

    fetch(`${API_BASE}/rooms`)
        .then(r => r.json())
        .then(rooms => {
            if (!rooms.length) {
                showError(desktopGrid, 'No rooms available at this time.');
                return;
            }

            const cardHTML = (room) => {
                const coverUrl = room.cover_image_url || FALLBACK_COVERS[room.slug] || FALLBACK_COVERS.deluxe;
                const highlights = (room.highlights || []).slice(0, 4);
                const price = room.base_price_thb
                    ? `From THB ${Number(room.base_price_thb).toLocaleString()}`
                    : 'Price on request';

                return `
          <div class="room-card glass-card overflow-hidden" data-aos="fade-up">
            <div class="room-card-img">
              <img src="${coverUrl}" alt="${room.name}" loading="lazy">
              <div class="room-card-badge">🏡 ${room.name}</div>
              <div class="room-card-count">${room.rooms_count ?? ''} rooms</div>
            </div>
            <div class="room-card-body">
              <h2 class="room-card-title">${room.name}</h2>
              <p class="room-card-sub">${room.subtitle || ''}</p>
              <div class="room-card-highlights">
                ${highlights.map(h => `<span class="room-highlight-pill">${h.icon || ''} ${h.label}</span>`).join('')}
              </div>
              <div class="room-card-price">${price} <span class="room-card-per">/ night</span></div>
              <div class="room-card-actions">
                <a href="room-details.html?type=${room.slug}" class="btn btn-outline-light fw-bold flex-fill">View Details</a>
                                <a href="booking.html?type=room&room_slug=${encodeURIComponent(room.slug)}" class="btn btn-danger fw-bold flex-fill">Book Now</a>
              </div>
            </div>
          </div>`;
            };

            if (desktopGrid) desktopGrid.innerHTML = rooms.map(cardHTML).join('');
            if (mobileCarousel) mobileCarousel.innerHTML = rooms.map(r => `<div class="rooms-carousel-item">${cardHTML(r)}</div>`).join('');

            // Re-init AOS for newly injected cards
            if (typeof AOS !== 'undefined') AOS.refresh();
        })
        .catch(err => {
            console.error('Rooms API error:', err);
            showError(desktopGrid, 'Could not load rooms. Please try again later.');
        });
}

// ─── room-details.html Logic ──────────────────────────────────────────────────
function initRoomDetailsPage() {
    const params = new URLSearchParams(window.location.search);
    const slug = params.get('slug') || params.get('type') || 'deluxe';

    const mainContent = document.getElementById('rdHeroImg')?.closest('main');

    fetch(`${API_BASE}/rooms/${slug}`)
        .then(r => {
            if (!r.ok) throw new Error(`HTTP ${r.status}`);
            return r.json();
        })
        .then(room => renderRoomDetails(room, slug))
        .catch(err => {
            console.error('Room detail API error:', err);
            // Show fallback UI with static data
            renderRoomDetailsFallback(slug);
        });
}

function renderRoomDetails(room, slug) {
    // Page title
    document.title = `${room.name} — Silver Cliff Resort`;
    const pageTitleEl = document.getElementById('pageTitle');
    if (pageTitleEl) pageTitleEl.textContent = `${room.name} — Silver Cliff Resort`;

    // Hero
    const coverUrl = room.cover_image_url || FALLBACK_COVERS[slug] || FALLBACK_COVERS.deluxe;
    const heroImg = document.getElementById('rdHeroImg');
    if (heroImg) heroImg.src = coverUrl;

    const badge = document.getElementById('rdBadge');
    if (badge) badge.textContent = `🏡 ${room.name}`;

    const title = document.getElementById('rdTitle');
    if (title) title.textContent = room.name;

    const subtitle = document.getElementById('rdSubtitle');
    if (subtitle) subtitle.textContent = room.subtitle || '';

    // Highlights bar
    const bar = document.getElementById('rdHighlightsBar');
    if (bar && room.highlights?.length) {
        bar.innerHTML = room.highlights.map(h => `
      <div class="rd-highlight-item">
        <span class="rd-highlight-icon">${h.icon || ''}</span>
        <span class="rd-highlight-label">${h.label}</span>
      </div>`).join('');
    }

    // Gallery
    const gallery = document.getElementById('rdGallery');
    if (gallery) {
        // Use DB images if available, else fallback Unsplash
        const images = room.gallery_images?.length
            ? room.gallery_images.map(img => ({ url: img.url, caption: img.caption || room.name }))
            : (FALLBACK_GALLERY[slug] || FALLBACK_GALLERY.deluxe).map((url, i) => ({ url, caption: `${room.name} — Photo ${i + 1}` }));

        gallery.innerHTML = images.map((img, i) => `
      <a class="rd-gallery-item glightbox" href="${img.url}" data-gallery="room-gallery" data-title="${img.caption}">
        <img src="${img.url}" alt="${img.caption}" loading="lazy">
        <div class="rd-gallery-overlay">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/>
          </svg>
        </div>
      </a>`).join('');

        if (typeof GLightbox !== 'undefined') GLightbox({ selector: '.glightbox' });
    }

    // Description
    const desc = document.getElementById('rdDescription');
    if (desc) desc.textContent = room.description || '';

    // Amenities
    const amenitiesEl = document.getElementById('rdAmenities');
    if (amenitiesEl && room.amenities?.length) {
        amenitiesEl.innerHTML = room.amenities.map(a => `
      <div class="rd-amenity-item">${a.icon_key || ''} ${a.name}</div>`).join('');
    }

    // Policies
    const capacity = document.getElementById('rdCapacity');
    if (capacity) {
        const policies = [
            { icon: '👥', label: 'Max Occupancy', value: `${room.capacity_adults} Adults + ${room.capacity_children} Children` },
            { icon: '🚭', label: 'Smoking', value: 'Non-smoking' },
            { icon: '🐾', label: 'Pets', value: 'Not allowed' },
            { icon: '🕐', label: 'Check-in', value: 'From 14:00' },
            { icon: '🕙', label: 'Check-out', value: 'Until 11:00' },
            { icon: '💳', label: 'Payment', value: 'Cash / Wise on arrival' },
        ];
        capacity.innerHTML = policies.map(p => `
      <div class="col-6 col-md-4">
        <div class="rd-policy-card">
          <div class="rd-policy-icon">${p.icon}</div>
          <div class="rd-policy-label">${p.label}</div>
          <div class="rd-policy-value">${p.value}</div>
        </div>
      </div>`).join('');
    }

    // Sidebar price
    const sidebarPrice = document.getElementById('rdSidebarPrice');
    if (sidebarPrice) {
        sidebarPrice.textContent = room.base_price_thb
            ? `THB ${Number(room.base_price_thb).toLocaleString()}`
            : 'THB —';
    }

    // Panel room name
    const panelName = document.getElementById('rdPanelRoomName');
    if (panelName) panelName.textContent = room.name;

    // Date defaults
    initDateDefaults('sidebarCheckin', 'sidebarCheckout');
    initDateDefaults('panelCheckin', 'panelCheckout');

    // Booking redirect helper
    function goToBooking(checkinId, checkoutId, adultsId, childrenId) {
        const cin = document.getElementById(checkinId)?.value;
        const cout = document.getElementById(checkoutId)?.value;
        const adults = document.getElementById(adultsId)?.value || 2;
        const children = document.getElementById(childrenId)?.value || 0;
        window.location.href = `booking.html?type=room&room_slug=${encodeURIComponent(slug)}&checkin=${cin}&checkout=${cout}&adults=${adults}&children=${children}`;
    }

    document.getElementById('sidebarBookBtn')?.addEventListener('click', () =>
        goToBooking('sidebarCheckin', 'sidebarCheckout', 'sidebarAdults', 'sidebarChildren'));

    // FAB + Reserve Panel
    const fab = document.getElementById('rdFab');
    const panel = document.getElementById('rdReservePanel');
    const backdrop = document.getElementById('rdBackdrop');
    const panelClose = document.getElementById('rdPanelClose');

    function openPanel() { panel.classList.add('is-open'); panel.setAttribute('aria-hidden', 'false'); backdrop.classList.add('is-visible'); document.body.style.overflow = 'hidden'; }
    function closePanel() { panel.classList.remove('is-open'); panel.setAttribute('aria-hidden', 'true'); backdrop.classList.remove('is-visible'); document.body.style.overflow = ''; }

    fab?.addEventListener('click', openPanel);
    panelClose?.addEventListener('click', closePanel);
    backdrop?.addEventListener('click', closePanel);

    document.getElementById('panelContinueBtn')?.addEventListener('click', () =>
        goToBooking('panelCheckin', 'panelCheckout', 'panelAdults', 'panelChildren'));

    // Hide FAB on desktop
    function toggleFab() { if (fab) fab.style.display = window.innerWidth >= 992 ? 'none' : 'flex'; }
    toggleFab();
    window.addEventListener('resize', toggleFab);
}

function renderRoomDetailsFallback(slug) {
    // If API fails, show a minimal error with a link back
    const title = document.getElementById('rdTitle');
    if (title) title.textContent = 'Room Details';
    const subtitle = document.getElementById('rdSubtitle');
    if (subtitle) subtitle.textContent = 'Could not load room details. Please try again.';
    const heroImg = document.getElementById('rdHeroImg');
    if (heroImg) heroImg.src = FALLBACK_COVERS[slug] || FALLBACK_COVERS.deluxe;

    // Still init FAB/panel/dates so page doesn't break
    initDateDefaults('sidebarCheckin', 'sidebarCheckout');
    initDateDefaults('panelCheckin', 'panelCheckout');
}

// ─── Boot ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    if (typeof AOS !== 'undefined') AOS.init({ duration: 650, once: true });

    initDrawer();
    initSteppers();
    initScrollProgress();
    setYear();

    if (document.getElementById('roomsGridDesktop') || document.getElementById('roomsCarouselMobile')) {
        initRoomsPage();
    }
    if (document.getElementById('rdHeroImg')) {
        initRoomDetailsPage();
    }
});
