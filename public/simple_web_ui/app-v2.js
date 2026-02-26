// ===== Init libs =====
console.log('🎯 APP.JS LOADED - Version: FIXED_PACKAGE_DETAILS - Line 614 links to package-details.html');
document.addEventListener("DOMContentLoaded", () => {
  if (typeof AOS !== 'undefined') {
    AOS.init({ duration: 650, once: true });
  }

  // ===== DOM refs =====
  const menuBtn = document.getElementById("menuBtn");
  const drawer = document.getElementById("mobileDrawer");
  const drawerClose = document.getElementById("drawerClose");
  const navLinks = Array.from(
    document.querySelectorAll(".nav-links .nav-link, .drawer-links .drawer-link")
  );
  const heroBookingForm = document.getElementById("heroBookingForm");
  const validationMsg = document.getElementById("bookingValidation");
  const revealElements = Array.from(document.querySelectorAll(".reveal"));
  const sectionIds = ["#intro", "#products", "#rooms-preview", "#info", "#packages", "#gallery", "#contact"];
  const sections = sectionIds
    .map((id) => document.querySelector(id))
    .filter(Boolean);

  const packageSelect = document.getElementById("packageSelect");
  const packageGrid = document.getElementById("packageGrid");
  const productGrid = document.getElementById("productGrid");
  const activitiesGrid = document.getElementById("activitiesGrid");
  const galleryGrid = document.getElementById("galleryGrid");

  function updateActiveLink(id) {
    if (!navLinks.length) return;
    navLinks.forEach((link) => {
      const isMatch = link.getAttribute("href") === id;
      link.classList.toggle("active", isMatch);
      if (isMatch) {
        link.setAttribute("aria-current", "page");
      } else {
        link.removeAttribute("aria-current");
      }
    });
  }

  // ===== Drawer / Menu =====
  function openDrawer() {
    if (!drawer) return;
    drawer.classList.add("open");
    drawer.setAttribute("aria-hidden", "false");
    menuBtn?.classList.add("is-open");
    menuBtn?.setAttribute("aria-expanded", "true");
    document.body.style.overflow = "hidden";
  }

  function closeDrawer() {
    if (!drawer) return;
    drawer.classList.remove("open");
    drawer.setAttribute("aria-hidden", "true");
    menuBtn?.classList.remove("is-open");
    menuBtn?.setAttribute("aria-expanded", "false");
    document.body.style.overflow = "";
  }

  menuBtn?.addEventListener("click", () => {
    const isOpen = menuBtn.classList.contains("is-open");
    isOpen ? closeDrawer() : openDrawer();
  });

  drawerClose?.addEventListener("click", closeDrawer);
  drawer?.addEventListener("click", (e) => {
    if (e.target === drawer) closeDrawer();
  });

  // ===== Steppers =====
  document.querySelectorAll(".step-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      const targetId = btn.dataset.target;
      const input = document.getElementById(targetId);
      if (!input) return;
      const step = btn.dataset.step;
      let val = parseInt(input.value);

      if (step === "up") {
        if (val < parseInt(input.max)) val++;
      } else {
        if (val > parseInt(input.min)) val--;
      }
      input.value = val;
      input.dispatchEvent(new Event('change'));
    });
  });

  // ===== Reveal animation =====
  if (revealElements.length && typeof IntersectionObserver !== 'undefined') {
    const revealObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            revealObserver.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.2, rootMargin: "0px 0px -10px 0px" }
    );
    revealElements.forEach((el) => revealObserver.observe(el));
  }

  // ===== Section highlight =====
  if (sections.length && typeof IntersectionObserver !== 'undefined') {
    const sectionObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            updateActiveLink(`#${entry.target.id}`);
          }
        });
      },
      { rootMargin: "-45% 0px -50% 0px", threshold: 0.25 }
    );
    sections.forEach((section) => sectionObserver.observe(section));
  }

  // ===== Date Defaults & Constraints (Index Hero) =====
  const today = new Date().toISOString().split("T")[0];
  const checkinInput = document.getElementById("checkin");
  const checkoutInput = document.getElementById("checkout");

  if (checkinInput && checkoutInput && heroBookingForm) {
    checkinInput.min = today;
    checkinInput.value = today;

    const setCheckoutMin = () => {
      const cinDate = new Date(checkinInput.value);
      cinDate.setDate(cinDate.getDate() + 1);
      checkoutInput.min = cinDate.toISOString().split("T")[0];
      if (new Date(checkoutInput.value) <= new Date(checkinInput.value)) {
        checkoutInput.value = checkoutInput.min;
      }
    };
    checkinInput.addEventListener("change", setCheckoutMin);
    setCheckoutMin();
  }

  // ===== Hero Form Submit (Index Only) =====
  heroBookingForm?.addEventListener("submit", (e) => {
    e.preventDefault();
    const cin = checkinInput.value;
    const cout = checkoutInput.value;
    const ad = document.getElementById("adults")?.value || 2;
    const ch = document.getElementById("children")?.value || 0;
    const pkgId = packageSelect?.value;
    const options = document.getElementById("heroPackageOptions")?.value;

    if (new Date(cout) <= new Date(cin)) {
      validationMsg.textContent = "Checkout must be after check-in.";
      validationMsg.classList.add("show");
      return;
    }

    // Find the selected package and redirect to static details page
    if (pkgId) {
      const selectedPackage = currentPackages.find(p => p.id == pkgId);
      if (selectedPackage && selectedPackage.slug) {
        window.location.href = `package-details.html?slug=${selectedPackage.slug}`;
        return;
      }
    }

    // Fallback to direct booking if no package selected
    goToBookingPage({
      package_id: pkgId,
      check_in: cin,
      check_out: cout,
      adults: ad,
      children: ch,
      package_options: options
    });
  });

  // Escape key for drawer
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeDrawer();
  });

  // ===== API Loading (If elements exist) =====
  if (packageGrid || activitiesGrid || galleryGrid || productGrid) {
    loadSettings();
    // Subsequent loads (loadPackages, loadActivities, etc.) are triggered from inside loadSettings()
  }

  // Category filters setup
  document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      renderGallery(btn.dataset.filter);
    });
  });

  // Handle URL filter for gallery
  const urlParams = new URLSearchParams(window.location.search);
  const filterParam = urlParams.get('filter');
  if (filterParam && galleryGrid) {
    const targetBtn = document.querySelector(`.filter-btn[data-filter="${filterParam}"]`);
    if (targetBtn) {
      document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      targetBtn.classList.add('active');
      // renderGallery will be called by loadGallery after data is fetched
    }
  }

  // Year in footer
  const yearEl = document.getElementById("year");
  if (yearEl) yearEl.textContent = new Date().getFullYear();
});

// ===== GLOBAL HELPERS & API LOGIC =====
let currentPackages = [];
let currentGallery = []; // This stores Albums with nested images
let currentActivities = [];
let siteSettings = {};

async function loadSettings() {
  try {
    const res = await fetch('/api/settings');
    const data = await res.json();
    siteSettings = data;

    // 1) BRANDING BINDING
    const branding = data.branding || {};
    if (typeof window.applySiteBranding === 'function') {
      await window.applySiteBranding(data, { source: 'app-v2.js' });
    } else {
      const siteName = (
        branding.brand_name
        ?? branding.site_name
        ?? branding.resort_name
        ?? 'SILVER CLIFF RESORT'
      );
      if (siteName) {
        ['siteName', 'siteNameMobile', 'siteNameContact'].forEach(id => {
          const el = document.getElementById(id);
          if (el) el.textContent = siteName;
        });
        const titleEl = document.getElementById('siteTitle');
        if (titleEl) titleEl.textContent = `${siteName} — ${branding.tagline || ''}`;
        document.querySelectorAll('.footer-brand-name').forEach(el => {
          el.textContent = siteName;
        });
      }
      if (branding.tagline) {
        ['siteTagline', 'siteTaglineMobile'].forEach(id => {
          const el = document.getElementById(id);
          if (el) el.textContent = branding.tagline;
        });
      }

      // Logo Binding (legacy fallback only when shared resolver is absent)
      const logoSrc = branding.logo_url || './logo.png';
      document.querySelectorAll('.brand-logo, .brand-img').forEach(img => {
        img.src = logoSrc;
        img.onerror = function () {
          this.onerror = null;
          this.src = './logo.png';
        };
      });
    }

    if (branding.favicon_url) {
      let link = document.querySelector("link[rel~='icon']");
      if (!link) {
        link = document.createElement('link');
        link.rel = 'icon';
        document.head.appendChild(link);
      }
      link.href = branding.favicon_url;
    }

    // 2) CONTACT BINDING (Dynamic via data-setting)
    const contact = data.contact || {};

    // Bind text content
    document.querySelectorAll('[data-setting]').forEach(el => {
      const key = el.dataset.setting;
      if (contact[key]) {
        el.textContent = contact[key];
      }
    });

    // Bind links (tel:, mailto:, etc)
    document.querySelectorAll('[data-setting-link]').forEach(el => {
      const key = el.dataset.settingLink;
      if (contact[key]) {
        if (key === 'phone' || key === 'whatsapp') {
          el.href = `tel:${contact[key].replace(/\D/g, '')}`;
        } else if (key === 'email') {
          el.href = `mailto:${contact[key]}`;
        } else {
          el.href = contact[key];
        }
      }
    });

    // Social Links
    if (contact.facebook) {
      const el = document.getElementById('fbLink');
      if (el) { el.href = contact.facebook; el.classList.remove('d-none'); }
    }
    if (contact.instagram) {
      const el = document.getElementById('igLink');
      if (el) { el.href = contact.instagram; el.classList.remove('d-none'); }
    }

    // Map Iframe
    if (contact.map_url) {
      const map = document.getElementById('contactMap');
      const container = document.getElementById('mapContainer');
      if (map && container) {
        map.src = contact.map_url;
        container.style.display = 'block';
      }
    }

    // 3) HERO BINDING
    const hero = data.hero || {};
    // If we have Ultimate Jungle package, we use its info for hero if not explicitly set
    const ultimatePkg = currentPackages.find(p => p.code === 'ULTIMATE-JUNGLE');

    if (ultimatePkg && !hero.title) {
      const titleEl = document.getElementById('heroTitle');
      if (titleEl) titleEl.innerHTML = ultimatePkg.title.replace(' ', '<br />');
      const subEl = document.getElementById('heroTagline');
      if (subEl) subEl.textContent = ultimatePkg.subtitle || ultimatePkg.description;
    } else {
      if (hero.title) {
        const el = document.getElementById('heroTitle');
        if (el) el.innerHTML = hero.title;
      }
      if (hero.tagline) {
        const el = document.getElementById('heroTagline');
        if (el) el.textContent = hero.tagline;
      }
    }

    if (hero.cta_text) {
      const el = document.getElementById('heroCtaText');
      if (el) el.textContent = hero.cta_text;
    }

    if (hero.bg_url) {
      const video = document.querySelector('.hero-video-bg video');
      if (video) {
        const source = video.querySelector('source');
        if (source) {
          source.src = hero.bg_url;
          video.load();
        }
      }
    }

    // 4) INTRO BINDING
    const intro = data.intro || {};
    if (intro.title) {
      const el = document.getElementById('introTitle');
      if (el) el.textContent = intro.title;
    }
    if (intro.text) {
      const el = document.getElementById('introText');
      if (el) el.textContent = intro.text;
    }

    // 5) ACTIVITIES BINDING
    const act = data.activities || {};
    if (act.title) {
      const el = document.getElementById('activitiesTitle');
      if (el) el.textContent = act.title;
    }
    if (act.subtitle) {
      const el = document.getElementById('activitiesSubtitle');
      if (el) el.textContent = act.subtitle;
    }

    // 6) PACKAGES BINDING
    const pkg = data.packages || {};
    if (pkg.title) {
      const el = document.getElementById('packagesTitle');
      if (el) el.textContent = pkg.title;
    }
    if (pkg.subtitle) {
      const el = document.getElementById('packagesSubtitle');
      if (el) el.textContent = pkg.subtitle;
    }

    // 7) GALLERY BINDING
    const gal = data.gallery || {};
    if (gal.title) {
      const el = document.getElementById('galleryTitle');
      if (el) el.textContent = gal.title;
    }
    if (gal.subtitle) {
      const el = document.getElementById('gallerySubtitle');
      if (el) el.textContent = gal.subtitle;
    }
    if (gal.cta) {
      const el = document.getElementById('galleryCtaText');
      if (el) el.textContent = gal.cta;
    }

    // Trigger dependent loads with new limits
    await loadPackages(); // Move this UP to ensure currentPackages is populated
    loadActivities();
    loadGallery();
    loadRooms();

  } catch (err) { console.error("Failed to load settings", err); }
}

async function loadPackages() {
  try {
    const res = await fetch('/api/packages');
    const data = await res.json();

    // Prioritize Ultimate Jungle Experience
    data.sort((a, b) => {
      if (a.code === 'ULTIMATE-JUNGLE') return -1;
      if (b.code === 'ULTIMATE-JUNGLE') return 1;
      return 0;
    });

    currentPackages = data;
    const packageGrid = document.getElementById("packageGrid");
    if (packageGrid) {
      packageGrid.innerHTML = data.map(packageCard).join("");
      // Add delegated click handler for package cards
      packageGrid.addEventListener('click', handlePackageCardClick);
    }
    const packageSelect = document.getElementById("packageSelect");
    if (packageSelect) {
      packageSelect.innerHTML = '<option value="" disabled selected>Select a package</option>';
      data.forEach(pkg => {
        const o = document.createElement("option");
        o.value = pkg.id; o.textContent = pkg.title;
        if (pkg.code === 'ULTIMATE-JUNGLE') o.selected = true;
        packageSelect.appendChild(o);
      });
    }
  } catch (err) { console.error("Failed to load packages", err); }
}



async function loadRooms() {
  try {
    const res = await fetch('/api/rooms');
    const data = await res.json();
    const productGrid = document.getElementById("productGrid");
    if (!productGrid) return;

    const roomTypesMode = Array.isArray(data) && data.some(r => r && (r.slug || r.name || r.cover_image_url));

    // New API shape: room types. Render cards that match the rooms preview section.
    if (roomTypesMode && productGrid.classList.contains('room-preview-grid')) {
      const fallbackImage = 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?auto=format&fit=crop&w=1200&q=80';
      const rooms = data.slice(0, 4);
      productGrid.innerHTML = rooms.map(rt => {
        const slug = rt.slug ? encodeURIComponent(rt.slug) : '';
        const title = rt.name || 'Room';
        const subtitle = rt.subtitle || (rt.rooms_count ? `${rt.rooms_count} rooms available` : 'Jungle Comfort');
        const image = rt.cover_image_url || fallbackImage;
        const href = slug ? `room-details.html?slug=${slug}` : 'rooms.html';
        return `
          <a href="${href}" class="room-preview-card">
            <img src="${image}" alt="${title}" class="room-preview-img">
            <div class="room-preview-overlay">
              <h3 class="room-preview-title">${title}</h3>
              <p class="room-preview-sub">${subtitle}</p>
            </div>
          </a>
        `;
      }).join("");
      return;
    }

    // Legacy API shape: individual rooms with a zone field.
    const zones = [...new Set((data || []).map(r => r?.zone).filter(Boolean))];
    productGrid.innerHTML = zones.map(zone => {
      const count = data.filter(r => r.zone === zone).length;
      return `
        <div class="col-12 col-md-6 col-lg-3">
          <div class="glass-card p-4 text-center h-100 reveal" data-aos="fade-up">
            <div style="font-size: 2.5rem; margin-bottom: 1rem;">🏡</div>
            <h3 class="h5 fw-bold">Zone ${zone}</h3>
            <p class="text-muted-soft small mb-3">${count} Bungalows available</p>
            <a href="booking.html?type=room" class="btn btn-mini btn-outline-light w-100 mt-auto">Book Room</a>
          </div>
        </div>
      `;
    }).join("");
  } catch (err) { console.error("Failed to load rooms", err); }
}

async function loadActivities() {
  try {
    const res = await fetch('/api/activities');
    const data = await res.json();
    currentActivities = data;
    const grid = document.getElementById("activitiesGrid");
    if (grid) {
      const isIndex = !!document.getElementById('actNext');
      const limit = parseInt(siteSettings?.activities?.limit || 6);
      const displayedItems = isIndex ? data.slice(0, limit) : data;
      grid.innerHTML = displayedItems.map(activityCard).join("");
      if (isIndex) initActivitiesCarousel();
    }
  } catch (err) { console.error("Failed to load activities", err); }
}

async function loadGallery() {
  try {
    const res = await fetch('/api/gallery');
    const data = await res.json();
    currentGallery = data; // Now stores Albums

    // Check for URL filter
    const urlParams = new URLSearchParams(window.location.search);
    const filterParam = urlParams.get('filter') || 'all';
    renderGallery(filterParam);
  } catch (err) { console.error("Failed to load gallery", err); }
}

function renderGallery(filter = 'all') {
  const grid = document.getElementById("galleryGrid");
  if (!grid) return;

  let allImages = [];
  currentGallery.forEach(album => {
    // Exact category match or 'all'
    if (filter === 'all' || album.category.toLowerCase() === filter.toLowerCase()) {
      (album.images || []).forEach(img => {
        allImages.push({
          image_url: img.image_url,
          album_title: album.title,
          category: album.category
        });
      });
    }
  });

  const isHome = !!document.querySelector('.home-gallery');
  if (isHome) {
    const limit = parseInt(siteSettings?.gallery?.limit || 6);
    allImages = allImages.slice(0, limit);
  }

  grid.innerHTML = allImages.map(galleryItem).join("");
  if (typeof GLightbox !== 'undefined') GLightbox({ selector: ".glightbox" });
}

// Carousel Logic for Activities
let actCurrentIndex = 0;
function initActivitiesCarousel() {
  const track = document.getElementById('activitiesGrid');
  const prevBtn = document.getElementById('actPrev');
  const nextBtn = document.getElementById('actNext');
  if (!track || !prevBtn || !nextBtn) return;

  const cards = track.querySelectorAll('.cardx');
  if (cards.length === 0) return;

  const updateCarousel = () => {
    const cardWidth = (cards[0].offsetWidth || 300) + 16;
    const visibleCards = getVisibleCardsCount();
    const maxIndex = Math.max(0, cards.length - visibleCards);

    if (actCurrentIndex > maxIndex) actCurrentIndex = maxIndex;
    if (actCurrentIndex < 0) actCurrentIndex = 0;

    track.style.transform = `translateX(-${actCurrentIndex * cardWidth}px)`;

    prevBtn.disabled = actCurrentIndex === 0;
    nextBtn.disabled = actCurrentIndex >= maxIndex;
  };

  function getVisibleCardsCount() {
    if (window.innerWidth >= 1200) return 3;
    if (window.innerWidth >= 768) return 2;
    return 1;
  }

  nextBtn.addEventListener('click', () => {
    actCurrentIndex++;
    updateCarousel();
  });

  prevBtn.addEventListener('click', () => {
    actCurrentIndex--;
    updateCarousel();
  });

  // Swipe support
  let touchStartX = 0;
  let touchEndX = 0;

  track.addEventListener('touchstart', e => {
    touchStartX = e.changedTouches[0].screenX;
  }, { passive: true });

  track.addEventListener('touchend', e => {
    touchEndX = e.changedTouches[0].screenX;
    handleSwipe();
  }, { passive: true });

  function handleSwipe() {
    const diff = touchStartX - touchEndX;
    if (Math.abs(diff) > 50) {
      if (diff > 0) {
        const visibleCards = getVisibleCardsCount();
        if (actCurrentIndex < cards.length - visibleCards) {
          actCurrentIndex++;
          updateCarousel();
        }
      } else {
        if (actCurrentIndex > 0) {
          actCurrentIndex--;
          updateCarousel();
        }
      }
    }
  }

  window.addEventListener('resize', updateCarousel);
  updateCarousel();
}

function packageCard(p) {
  const imgUrl = p.image_url || 'https://images.unsplash.com/photo-1502672023488-70e25813eb80?auto=format&fit=crop&w=1200&q=80';
  const isUltimate = p.code === 'ULTIMATE-JUNGLE';
  const badge = isUltimate ? '🌟 FEATURED' : (p.is_best_offer ? 'BEST OFFER' : 'HOT');

  // Using .scroll-item for horizontal slider
  // Force vertical layout for consistency in slider
  return `
    <div class="scroll-item">
      <div class="glass-card overflow-hidden h-100 ${isUltimate ? 'border-primary' : ''} package-card" data-slug="${p.slug}" style="cursor: pointer;">
        <div class="d-flex flex-column h-100">
            <div class="packages-slider-img-wrap">
              <img src="${imgUrl}" alt="${p.title}" style="height: 100%; width: 100%; object-fit: cover;">
              <div class="img-overlay" style="bottom: auto; top: 0;">
                <div class="img-overlay-title">${badge} • THB ${new Intl.NumberFormat().format(p.price_thb)}</div>
              </div>
            </div>
            
            <div class="packages-slider-content">
              <h2 class="h4 fw-bold mb-1">${p.title}</h2>
              <p class="text-secondary small mb-3">${p.subtitle || ''}</p>
              <p class="text-muted-soft small mb-4" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">${p.description}</p>
              
              <div class="mt-auto d-flex gap-2 flex-wrap">
                <span class="btn btn-danger fw-bold w-100">View Details</span>
              </div>
            </div>
        </div>
      </div>
    </div>
  `;
}

// Global modal state
let activePackage = null;
let selectedOptions = [];

window.openPackageModal = (id) => {
  const p = currentPackages.find(x => x.id == id);
  if (!p) return;
  activePackage = p;
  selectedOptions = [];

  document.getElementById('mdlPkgTitle').textContent = p.title;
  document.getElementById('mdlPkgSubtitle').textContent = p.subtitle || '';
  document.getElementById('mdlPkgImage').src = p.image_url || '';
  document.getElementById('mdlPkgDesc').textContent = p.description;
  document.getElementById('mdlPkgPrice').textContent = 'THB ' + new Intl.NumberFormat().format(p.price_thb);

  // Timeline
  const timeline = document.getElementById('mdlItineraryTimeline');
  timeline.innerHTML = (p.itineraries || []).map(it => `
        <div class="timeline-item">
            <div class="timeline-dot"></div>
            <div class="timeline-content">
                <div class="timeline-day">Day ${it.day_no}</div>
                <div class="timeline-title">${it.title}</div>
                <div class="timeline-desc small">${it.description}</div>
            </div>
        </div>
    `).join('');

  // Options
  const optSection = document.getElementById('mdlOptionsSection');
  const optGrid = document.getElementById('mdlOptionsGrid');
  if (p.options && p.options.length > 0) {
    optSection.classList.remove('d-none');
    optGrid.innerHTML = p.options.map(opt => `
            <div class="col-6">
                <label class="option-pill" onclick="togglePackageOption(this, '${opt.id}')">
                    <input type="checkbox" value="${opt.id}">
                    ${opt.name}
                </label>
            </div>
        `).join('');
  } else {
    optSection.classList.add('d-none');
  }

  document.getElementById('packageDetailModal').classList.add('is-open');
  document.body.style.overflow = 'hidden';
};

window.closePackageModal = () => {
  document.getElementById('packageDetailModal').classList.remove('is-open');
  document.body.style.overflow = '';
};

window.togglePackageOption = (el, id) => {
  const idx = selectedOptions.indexOf(id);
  if (idx > -1) {
    selectedOptions.splice(idx, 1);
    el.classList.remove('active');
  } else {
    selectedOptions.push(id);
    el.classList.add('active');
  }
  document.getElementById('mdlOptionsError').style.display = 'none';
};

window.continueToHeroBooking = () => {
  // Validate exactly 2 options if Ultimate
  if (activePackage.code === 'ULTIMATE-JUNGLE' && selectedOptions.length !== 2) {
    document.getElementById('mdlOptionsError').style.display = 'block';
    return;
  }

  // Set value in hero form
  const sel = document.getElementById('packageSelect');
  if (sel) sel.value = activePackage.id;

  // Store options in hidden input
  const optInput = document.getElementById('heroPackageOptions');
  if (optInput) optInput.value = selectedOptions.join(',');

  closePackageModal();

  // Scroll to hero
  document.getElementById('heroBookingForm')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
};

function activityCard(a) {
  const imgUrl = a.cover_image_url || '';
  const mediaContent = imgUrl ? `<img src="${imgUrl}" alt="${a.title}" style="width: 100%; height: 100%; object-fit: cover;">` : '<div style="font-size: 3rem;">🌴</div>';

  // Mapping activity categories/titles to gallery filters if possible
  // For now, we route to gallery with a filter if it matches one of the known ones
  const galleryFilter = a.title.toLowerCase().includes('lake') ? 'lake' :
    a.title.toLowerCase().includes('trek') ? 'jungle' :
      a.title.toLowerCase().includes('elephant') ? 'elephant' : 'all';

  return `
    <article class="cardx">
      <div class="cardx-media" style="height: 200px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.05); overflow: hidden;">
        ${mediaContent}
        <div class="cardx-tag">TOUR</div>
        ${a.price_thb ? `<div class="cardx-price">THB ${new Intl.NumberFormat().format(a.price_thb)}</div>` : ''}
      </div>
      <div class="cardx-body">
        <div class="cardx-code">ACT-${a.id}</div>
        <h3 class="cardx-title">${a.title}</h3>
        <p class="cardx-desc" style="font-size: 0.85rem; color: var(--muted);">${a.description}</p>
        <div class="cardx-actions" style="flex-direction:column; gap:8px;">
           <a class="btn btn-outline-light btn-mini w-100" href="gallery.html?filter=${galleryFilter}">View Gallery</a>
           <a class="btn btn-danger btn-mini w-100" href="booking.html?type=tour&activity_id=${a.id}">Book This Tour</a>
        </div>
      </div>
    </article>
  `;
}

function galleryItem(item) {
  const imgUrl = item.image_url;
  return `
    <a class="g-item glightbox" href="${imgUrl}" data-gallery="resort">
      <img src="${imgUrl}" alt="Gallery Image" loading="lazy">
      <div class="g-item-overlay">
          <div class="g-item-info">
            <span class="g-item-category">${item.category || 'RESORT'}</span>
            <h3 class="g-item-title">${item.album_title}</h3>
          </div>
          <div class="g-item-cta">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/>
            </svg>
          </div>
      </div>
    </a>
  `;
}

// Handler for package card clicks
function handlePackageCardClick(e) {
  // Find the package card element (could be a child element that was clicked)
  const card = e.target.closest('.package-card');
  if (!card) return;

  // Ignore clicks on anchor links (like the gallery link)
  if (e.target.closest('a[href^="#"]')) {
    return;
  }

  // Get the slug and redirect
  const slug = card.dataset.slug;
  if (slug) {
    window.location.href = `package-details.html?slug=${slug}`;
  }
}

window.goToBookingPage = (data = {}) => {
  const params = new URLSearchParams();

  if (data.booking_type) params.set('type', data.booking_type);
  else if (data.package_id) params.set('type', 'package');
  else if (data.activity_id) params.set('type', 'tour');
  else params.set('type', 'room');

  if (data.package_id) params.set('package_id', data.package_id);
  if (data.activity_id) params.set('activity_id', data.activity_id);
  if (data.check_in) params.set('checkin', data.check_in);
  if (data.check_out) params.set('checkout', data.check_out);
  if (data.arrival_date) params.set('arrival_date', data.arrival_date);

  if (data.adults) params.set('adults', data.adults);
  if (data.children) params.set('children', data.children);

  // Package options handling
  if (data.package_options) {
    params.set('package_options', data.package_options);
    params.set('options', data.package_options);
  } else if (data.options) {
    params.set('package_options', data.options);
    params.set('options', data.options);
  }

  window.location.href = `booking.html?${params.toString()}`;
};
