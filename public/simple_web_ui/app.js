// ===== Init libs =====
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
  const sectionIds = ["#intro", "#products", "#info", "#packages", "#gallery", "#contact"];
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

    if (new Date(cout) <= new Date(cin)) {
      if (validationMsg) {
        validationMsg.textContent = "Check-out must be after check-in.";
        validationMsg.classList.add("show");
      }
      return;
    }
    if (validationMsg) validationMsg.classList.remove("show");

    if (pkgId) {
      const selectedPackage = currentPackages.find((p) => String(p.id) === String(pkgId));
      if (selectedPackage) {
        const detailsKey = getPackageDetailsKey(selectedPackage);
        window.location.href = `package-details.html?slug=${encodeURIComponent(detailsKey)}`;
        return;
      }
    }

    window.goToBookingPage({
      booking_type: pkgId ? 'package' : 'room',
      package_id: pkgId,
      check_in: cin,
      check_out: cout,
      adults: ad,
      children: ch
    });
  });

  // Escape key for drawer
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeDrawer();
  });

  // ===== API Loading (If elements exist) =====
  if (packageGrid || activitiesGrid || galleryGrid || productGrid) {
    loadSettings();
    loadPackages();
    loadActivities();
    loadGallery();
    loadRooms();
  }

  // Category filters setup
  document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      renderGallery(btn.dataset.filter);
    });
  });

  const filterParam = new URLSearchParams(window.location.search).get('filter');
  if (filterParam && galleryGrid) {
    const targetBtn = document.querySelector(`.filter-btn[data-filter="${filterParam}"]`);
    if (targetBtn) {
      document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      targetBtn.classList.add('active');
    }
  }

  // Year in footer
  const yearEl = document.getElementById("year");
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  // Initialize scroll progress bar
  initScrollProgress();
});

// ===== GLOBAL HELPERS & API LOGIC =====
let currentPackages = [];
let currentGallery = [];
let currentActivities = [];
let siteSettings = {};
const ACTIVITIES_LIMIT = 6;
const ROOMS_LIMIT = 6;
const PACKAGES_LIMIT = 6;
const GALLERY_LIMIT = 8;
const ACTIVITY_AUTO_SLIDE_MS = 5200;
let galleryLightbox = null;
let galleryTransitionTimer = null;

function normalizeApiImageUrl(value) {
  if (!value) return "";
  if (typeof value !== "string") return "";
  if (value.startsWith("http://") || value.startsWith("https://") || value.startsWith("/")) {
    return value;
  }
  return `/storage/${value.replace(/^\/+/, "")}`;
}

function getPackageDetailsKey(pkg) {
  if (pkg?.slug) return pkg.slug;
  if (pkg?.id != null) return String(pkg.id);
  return "";
}

async function loadSettings() {
  try {
    const res = await fetch('/api/settings');
    const data = await res.json();
    siteSettings = data || {};

    const branding = siteSettings.branding || {};
    const contact = siteSettings.contact || {};
    const hero = siteSettings.hero || {};
    const intro = siteSettings.intro || {};
    const activitiesSettings = siteSettings.activities || {};
    const gallerySettings = siteSettings.gallery || {};

    if (typeof window.applySiteBranding === "function") {
      await window.applySiteBranding(siteSettings, { source: "app.js" });
    } else {
      const siteName = (
        branding.brand_name
        ?? branding.site_name
        ?? branding.resort_name
        ?? "SILVER CLIFF RESORT"
      );
      const tagline = branding.tagline || "";
      if (siteName) {
        document.querySelectorAll(".brand-title").forEach((el) => {
          el.textContent = siteName;
        });
        const contactBrand = document.getElementById("contactBrandName");
        if (contactBrand) contactBrand.textContent = siteName;
        const footerBrandEls = document.querySelectorAll(".footer-brand-name");
        footerBrandEls.forEach((el) => {
          el.textContent = siteName;
        });
        const titleEl = document.getElementById("siteTitle");
        if (titleEl) titleEl.textContent = `${siteName}${tagline ? ` — ${tagline}` : ""}`;
      }
      if (tagline) {
        document.querySelectorAll(".brand-sub").forEach((el) => {
          el.textContent = tagline;
        });
      }

      const logoUrl = normalizeApiImageUrl(branding.logo_url || branding.navbar_logo_url);
      if (logoUrl) {
        document.querySelectorAll(".brand-logo, .brand-img").forEach((img) => {
          if (img.tagName === "IMG") img.src = logoUrl;
        });
      }
    }

    if (hero.tagline || siteSettings.tagline) {
      const taglineEl = document.getElementById('heroTagline');
      if (taglineEl) taglineEl.textContent = hero.tagline || siteSettings.tagline;
    }
    if (hero.title || siteSettings.hero_text) {
      const titleEl = document.getElementById('heroTitle');
      if (titleEl) titleEl.innerHTML = hero.title || siteSettings.hero_text;
    }
    if (hero.cta_text) {
      const ctaEl = document.getElementById("heroCtaText");
      if (ctaEl) ctaEl.textContent = hero.cta_text;
    }
    if (hero.bg_url) {
      const source = document.querySelector(".hero-video-bg video source");
      if (source) {
        source.src = hero.bg_url;
        source.parentElement?.load?.();
      }
    }

    if (intro.title) {
      const introTitle = document.getElementById("introTitle");
      if (introTitle) introTitle.textContent = intro.title;
    }
    if (intro.text) {
      const introText = document.getElementById("introText");
      if (introText) introText.textContent = intro.text;
    }

    if (activitiesSettings.title) {
      const el = document.getElementById("activitiesTitle");
      if (el) el.textContent = activitiesSettings.title;
    }
    if (activitiesSettings.subtitle) {
      const el = document.getElementById("activitiesSubtitle");
      if (el) el.textContent = activitiesSettings.subtitle;
    }
    if (gallerySettings.title) {
      const el = document.getElementById("galleryTitle");
      if (el) el.textContent = gallerySettings.title;
    }
    if (gallerySettings.subtitle) {
      const el = document.getElementById("gallerySubtitle");
      if (el) el.textContent = gallerySettings.subtitle;
    }
    if (gallerySettings.cta) {
      const el = document.getElementById("galleryCtaText");
      if (el) el.textContent = gallerySettings.cta;
    }

    const packagesSettings = siteSettings.packages || {};
    if (packagesSettings.title) {
      const el = document.getElementById("packagesTitle");
      if (el) el.textContent = packagesSettings.title;
    }
    if (packagesSettings.subtitle) {
      const el = document.getElementById("packagesSubtitle");
      if (el) el.textContent = packagesSettings.subtitle;
    }

    const waNum = contact.whatsapp || siteSettings.whatsapp_number || "";
    const waEl = document.getElementById('quickWhatsapp');
    if (waEl) waEl.textContent = waNum;

    const emailEl = document.getElementById('quickEmail');
    if (emailEl) emailEl.textContent = contact.email || siteSettings.email || "";

    const locEl = document.getElementById('quickLocation');
    if (locEl) locEl.textContent = contact.address || siteSettings.map_location || "";

    const callLink = document.getElementById("quickCallLink");
    if (callLink) {
      if (contact.whatsapp_url) {
        callLink.href = contact.whatsapp_url;
        callLink.target = "_blank";
        callLink.rel = "noopener";
      } else if (contact.phone) {
        callLink.href = `tel:${String(contact.phone).replace(/[^\d+]/g, "")}`;
      } else if (waNum) {
        callLink.href = `https://wa.me/${waNum.replace(/\D/g, "")}`;
        callLink.target = "_blank";
        callLink.rel = "noopener";
      }
    }

    const mapLink = document.getElementById("openMapLink");
    if (mapLink && contact.google_maps_url) {
      mapLink.href = contact.google_maps_url;
    }

    const mapFrame = document.getElementById("contactMap");
    if (mapFrame) {
      mapFrame.src = contact.google_maps_iframe_url
        || "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3941.938056599948!2d98.53302197352671!3d8.88535149122402!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3051264e92b2677b%3A0xea1b2e8b31958e99!2sKhao%20Sok%20Silver%20Cliff%20Resort!5e0!3m2!1sen!2sth!4v1771559137276!5m2!1sen!2sth";
    }
  } catch (err) { console.error("Failed to load settings", err); }
}

async function loadRooms() {
  try {
    const res = await fetch('/api/rooms');
    const rooms = await res.json();
    const grid = document.getElementById("productGrid");
    if (!grid || !Array.isArray(rooms)) return;

    // Detect API response shape: RoomTypes (new) vs Rooms with zone field (legacy)
    const isRoomTypes = rooms.length > 0 && (rooms[0].slug || rooms[0].name || rooms[0].cover_image_url);

    if (isRoomTypes) {
      // New API: RoomTypes - reuse the room card component markup from rooms.html
      const fallbackImage = "https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?auto=format&fit=crop&w=1200&q=80";
      grid.innerHTML = rooms.slice(0, ROOMS_LIMIT).map((room) => {
        const title = room.name || "Room";
        const subtitle = room.subtitle || (room.rooms_count ? `${room.rooms_count} rooms available` : "Jungle stay");
        const imgUrl = normalizeApiImageUrl(room.cover_image_url) || fallbackImage;
        const detailsHref = room.slug ? `room-details.html?slug=${encodeURIComponent(room.slug)}` : "rooms.html";
        const bookingHref = room.slug
          ? `booking.html?type=room&room_slug=${encodeURIComponent(room.slug)}`
          : "booking.html?type=room";
        const highlights = Array.isArray(room.highlights) ? room.highlights.slice(0, 4) : [];
        const price = room.base_price_thb
          ? `From THB ${new Intl.NumberFormat().format(room.base_price_thb)}`
          : "Price on request";
        const roomCount = room.rooms_count != null && room.rooms_count !== ""
          ? `${room.rooms_count} rooms`
          : "Rooms";

        return `
          <div class="room-card glass-card overflow-hidden" data-aos="fade-up">
            <div class="room-card-img">
              <img src="${imgUrl}" alt="${title}" loading="lazy">
              <div class="room-card-badge">&#x1F3E1; ${title}</div>
              <div class="room-card-count">${roomCount}</div>
            </div>
            <div class="room-card-body">
              <h2 class="room-card-title">${title}</h2>
              <p class="room-card-sub">${subtitle}</p>
              <div class="room-card-highlights">
                ${highlights.map((h) => `<span class="room-highlight-pill">${h.icon || ""} ${h.label || ""}</span>`).join("")}
              </div>
              <div class="room-card-price">${price} <span class="room-card-per">/ night</span></div>
              <div class="room-card-actions">
                <a href="${detailsHref}" class="btn btn-outline-light fw-bold flex-fill">View Details</a>
                <a href="${bookingHref}" class="btn btn-danger fw-bold flex-fill">Book Now</a>
              </div>
            </div>
          </div>
        `;
      }).join("");
    } else {
      // Legacy API: Individual rooms with zone - Group by zone
      const zones = [...new Set(rooms.map(r => r.zone).filter(Boolean))];
      grid.innerHTML = zones.map(zone => {
        const count = rooms.filter(r => r.zone === zone).length;
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
    }

    initHorizontalSlider(grid, isRoomTypes ? '.room-card' : '.col-12');
    observeElementsInView(grid, isRoomTypes ? '.room-card' : '.col-12');
    initTrackNavigation(
      grid,
      document.getElementById('roomsPrev'),
      document.getElementById('roomsNext')
    );

    if (typeof AOS !== "undefined") AOS.refresh();
  } catch (err) {
    console.error("Failed to load rooms", err);
  }
}

async function loadPackages() {
  try {
    const res = await fetch('/api/packages');
    const data = await res.json();
    currentPackages = Array.isArray(data) ? data : [];

    // Prioritize "Ultimate Jungle" package if present
    currentPackages.sort((a, b) => {
      if (a.code === 'ULTIMATE-JUNGLE') return -1;
      if (b.code === 'ULTIMATE-JUNGLE') return 1;
      return 0;
    });

    const packageGrid = document.getElementById("packageGrid");
    if (packageGrid) {
      const homepagePackages = currentPackages.slice(0, PACKAGES_LIMIT);
      packageGrid.innerHTML = homepagePackages.map(packageCard).join("");
      packageGrid.addEventListener("click", handlePackageCardClick);
      initHorizontalSlider(packageGrid, '.package-card');
      observeElementsInView(packageGrid, '.package-card');
    }

    const packageSelect = document.getElementById("packageSelect");
    if (packageSelect) {
      packageSelect.innerHTML = '<option value="" disabled selected>Select a package</option>';
      currentPackages.forEach(pkg => {
        const o = document.createElement("option");
        o.value = pkg.id; o.textContent = pkg.title;
        if (pkg.code === 'ULTIMATE-JUNGLE') o.selected = true;
        packageSelect.appendChild(o);
      });
    }
  } catch (err) { console.error("Failed to load packages", err); }
}

async function loadActivities() {
  try {
    const res = await fetch('/api/activities');
    const data = await res.json();
    currentActivities = Array.isArray(data) ? data : [];
    const grid = document.getElementById("activitiesGrid");
    if (grid) {
      // If on index page (has carousel nav), limit and init carousel
      const isIndex = !!document.getElementById('actNext');
      const displayedItems = isIndex ? currentActivities.slice(0, ACTIVITIES_LIMIT) : currentActivities;
      grid.innerHTML = displayedItems.map(activityCard).join("");
      observeElementsInView(grid, '.cardx');
      if (isIndex) initActivitiesCarousel();
    }
  } catch (err) { console.error("Failed to load activities", err); }
}

async function loadGallery() {
  try {
    const res = await fetch('/api/gallery');
    const data = await res.json();
    currentGallery = Array.isArray(data) ? data : [];
    const filterFromUrl = new URLSearchParams(window.location.search).get("filter") || "all";
    renderGallery(filterFromUrl, { animate: false });
  } catch (err) { console.error("Failed to load gallery", err); }
}

function renderGallery(filter = 'all', { animate = true } = {}) {
  const grid = document.getElementById("galleryGrid");
  if (!grid) return;

  const normalized = [];
  currentGallery.forEach((entry) => {
    // Supports both old flat image rows and new albums->images shape.
    if (Array.isArray(entry.images)) {
      const entryCategory = (entry.category || "all").toLowerCase();
      if (filter !== "all" && entryCategory !== String(filter).toLowerCase()) return;
      entry.images.forEach((img) => {
        const imgUrl = normalizeApiImageUrl(img.image_url || img.image_path);
        if (!imgUrl) return;
        normalized.push({
          image_url: imgUrl,
          category: entry.category || "resort",
          title: entry.title || "Gallery",
        });
      });
      return;
    }

    const itemCategory = (entry.category || "all").toLowerCase();
    if (filter !== "all" && itemCategory !== String(filter).toLowerCase()) return;
    const imgUrl = normalizeApiImageUrl(entry.image_url || entry.image_path);
    if (!imgUrl) return;
    normalized.push({
      image_url: imgUrl,
      category: entry.category || "resort",
      title: entry.title || "Gallery",
    });
  });

  // If on home page, cap visible items to keep a curated feel.
  const isHome = !!document.querySelector('.home-gallery');
  let filtered = normalized;
  if (isHome) {
    filtered = filtered.slice(0, GALLERY_LIMIT);
  }

  const applyGalleryMarkup = () => {
    grid.innerHTML = filtered.map(galleryItem).join("");
    observeElementsInView(grid, '.g-item');
    if (typeof GLightbox !== 'undefined') {
      if (galleryLightbox && typeof galleryLightbox.destroy === "function") {
        galleryLightbox.destroy();
      }
      galleryLightbox = GLightbox({ selector: ".glightbox" });
    }
  };

  if (!animate || !grid.children.length) {
    applyGalleryMarkup();
    return;
  }

  if (galleryTransitionTimer) clearTimeout(galleryTransitionTimer);
  grid.classList.remove('gallery-transition-in', 'gallery-transition-ready');
  grid.classList.add('gallery-transition-out');

  galleryTransitionTimer = setTimeout(() => {
    applyGalleryMarkup();
    grid.classList.remove('gallery-transition-out');
    grid.classList.add('gallery-transition-in');
    requestAnimationFrame(() => {
      grid.classList.add('gallery-transition-ready');
      setTimeout(() => {
        grid.classList.remove('gallery-transition-in', 'gallery-transition-ready');
      }, 360);
    });
  }, 180);
}

function initHorizontalSlider(container, itemSelector) {
  if (!container || !itemSelector) return;
  const items = Array.from(container.querySelectorAll(itemSelector));
  if (!items.length) return;

  container.classList.add('h-scroll-track');
  items.forEach((item) => item.classList.add('h-scroll-item'));
  enableDragScroll(container);
}

function initTrackNavigation(track, prevBtn, nextBtn) {
  if (!track || !prevBtn || !nextBtn) return;
  if (track.dataset.trackNavReady === "1") return;
  track.dataset.trackNavReady = "1";

  const getGap = () => {
    const styles = window.getComputedStyle(track);
    const gap = parseFloat(styles.columnGap || styles.gap || "0");
    return Number.isFinite(gap) ? gap : 0;
  };

  const getStep = () => {
    const firstItem = track.querySelector('.h-scroll-item') || track.firstElementChild;
    if (!firstItem) return track.clientWidth || 280;
    return firstItem.getBoundingClientRect().width + getGap();
  };

  const getMaxScroll = () => Math.max(0, track.scrollWidth - track.clientWidth);

  const updateState = () => {
    const maxScroll = getMaxScroll();
    const left = track.scrollLeft;
    prevBtn.disabled = left <= 2;
    nextBtn.disabled = left >= maxScroll - 2;
  };

  const slide = (direction = 1) => {
    const step = getStep();
    if (!step) return;
    track.scrollBy({ left: direction * step, behavior: 'smooth' });
  };

  prevBtn.onclick = () => slide(-1);
  nextBtn.onclick = () => slide(1);

  track.addEventListener('scroll', updateState, { passive: true });
  window.addEventListener('resize', updateState);
  updateState();
}

function observeElementsInView(container, selector) {
  if (!container || !selector || typeof IntersectionObserver === "undefined") return;
  const elements = Array.from(container.querySelectorAll(selector));
  if (!elements.length) return;

  const observer = new IntersectionObserver((entries, obs) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('is-inview');
      obs.unobserve(entry.target);
    });
  }, { threshold: 0.18, rootMargin: "0px 0px -6% 0px" });

  elements.forEach((el) => {
    el.classList.add('inview-ready');
    observer.observe(el);
  });
}

function enableDragScroll(scroller) {
  if (!scroller) return;
  if (scroller.dataset.dragReady === "1") return;
  scroller.dataset.dragReady = "1";

  let isDown = false;
  let startX = 0;
  let startLeft = 0;

  const onMove = (event) => {
    if (!isDown) return;
    const pointerX = event.clientX ?? (event.touches?.[0]?.clientX || 0);
    const walk = pointerX - startX;
    scroller.scrollLeft = startLeft - walk;
  };

  const onUp = () => {
    if (!isDown) return;
    isDown = false;
    scroller.classList.remove('is-dragging');
  };

  scroller.addEventListener('pointerdown', (event) => {
    if (event.pointerType === 'mouse' && event.button !== 0) return;
    isDown = true;
    startX = event.clientX;
    startLeft = scroller.scrollLeft;
    scroller.classList.add('is-dragging');
  });

  scroller.addEventListener('pointermove', onMove);
  scroller.addEventListener('pointerup', onUp);
  scroller.addEventListener('pointerleave', onUp);
  scroller.addEventListener('touchstart', () => scroller.classList.add('is-dragging'), { passive: true });
  scroller.addEventListener('touchend', () => scroller.classList.remove('is-dragging'), { passive: true });
}

function initActivitiesCarousel() {
  const track = document.getElementById('activitiesGrid');
  const viewport = track?.closest('.carousel-viewport');
  const prevBtn = document.getElementById('actPrev');
  const nextBtn = document.getElementById('actNext');
  if (!track || !viewport || !prevBtn || !nextBtn) return;
  if (viewport.dataset.carouselReady === "1") return;
  viewport.dataset.carouselReady = "1";

  const cards = Array.from(track.querySelectorAll('.cardx'));
  if (!cards.length) return;

  enableDragScroll(viewport);

  const getGap = () => {
    const styles = window.getComputedStyle(track);
    const gap = parseFloat(styles.columnGap || styles.gap || "0");
    return Number.isFinite(gap) ? gap : 0;
  };

  const getStep = () => {
    const firstCard = cards[0];
    if (!firstCard) return 0;
    return firstCard.getBoundingClientRect().width + getGap();
  };

  const getMaxScroll = () => Math.max(0, viewport.scrollWidth - viewport.clientWidth);

  const updateNavState = () => {
    const maxScroll = getMaxScroll();
    const left = viewport.scrollLeft;
    prevBtn.disabled = left <= 2;
    nextBtn.disabled = left >= maxScroll - 2;
  };

  const slideByStep = (direction = 1) => {
    const step = getStep();
    if (!step) return;
    viewport.scrollBy({ left: direction * step, behavior: 'smooth' });
  };

  prevBtn.onclick = () => slideByStep(-1);
  nextBtn.onclick = () => slideByStep(1);
  viewport.addEventListener('scroll', updateNavState, { passive: true });

  let autoTimer = null;
  let autoPaused = false;

  const stopAuto = () => {
    if (autoTimer) {
      clearInterval(autoTimer);
      autoTimer = null;
    }
  };

  const startAuto = () => {
    stopAuto();
    autoTimer = setInterval(() => {
      if (autoPaused) return;
      const step = getStep();
      if (!step) return;
      const maxScroll = getMaxScroll();
      if (maxScroll <= 0) return;

      const nearEnd = viewport.scrollLeft >= (maxScroll - step * 0.5);
      if (nearEnd) {
        viewport.scrollTo({ left: 0, behavior: 'smooth' });
      } else {
        viewport.scrollBy({ left: step, behavior: 'smooth' });
      }
    }, ACTIVITY_AUTO_SLIDE_MS);
  };

  const pauseAuto = () => { autoPaused = true; };
  const resumeAuto = () => { autoPaused = false; };

  viewport.addEventListener('mouseenter', pauseAuto);
  viewport.addEventListener('mouseleave', resumeAuto);
  viewport.addEventListener('touchstart', pauseAuto, { passive: true });
  viewport.addEventListener('touchend', resumeAuto, { passive: true });
  viewport.addEventListener('pointerdown', pauseAuto);
  viewport.addEventListener('pointerup', resumeAuto);
  viewport.addEventListener('pointercancel', resumeAuto);
  prevBtn.addEventListener('mouseenter', pauseAuto);
  prevBtn.addEventListener('mouseleave', resumeAuto);
  nextBtn.addEventListener('mouseenter', pauseAuto);
  nextBtn.addEventListener('mouseleave', resumeAuto);

  window.addEventListener('resize', updateNavState);
  updateNavState();
  startAuto();
}

function packageCard(p) {
  const imgUrl = normalizeApiImageUrl(p.image_url || p.image_path) || 'https://images.unsplash.com/photo-1502672023488-70e25813eb80?auto=format&fit=crop&w=1200&q=80';
  const isUltimate = p.code === 'ULTIMATE-JUNGLE';
  const isFeatured = isUltimate || !!p.is_best_offer;
  const badge = isFeatured ? '⭐ Most Popular' : '📦 Package';
  const detailsKey = getPackageDetailsKey(p);
  const detailsHref = `package-details.html?slug=${encodeURIComponent(detailsKey)}`;
  const subtitleRaw = p.subtitle || p.description || 'Curated package experience in Khao Sok jungle.';
  const subtitle = subtitleRaw.length > 120 ? `${subtitleRaw.substring(0, 117)}...` : subtitleRaw;

  return `
    <div class="col-12 col-lg-4 package-card ${isFeatured ? 'package-card-featured' : ''}" data-package-key="${detailsKey}">
      <a class="room-card glass-card overflow-hidden package-room-card" href="${detailsHref}">
        <div class="room-card-img">
          <img src="${imgUrl}" alt="${p.title}" loading="lazy">
          <div class="room-card-badge package-card-badge">${badge}</div>
          <div class="room-card-count">THB ${new Intl.NumberFormat().format(p.price_thb)}</div>
        </div>
        <div class="room-card-body">
          <h3 class="room-card-title">${p.title}</h3>
          <p class="room-card-sub">${subtitle}</p>
          <div class="room-card-highlights">
            <span class="room-highlight-pill">Curated Experience</span>
            ${isFeatured ? '<span class="room-highlight-pill">Best-Selling</span>' : ''}
          </div>
          <div class="room-card-price">THB ${new Intl.NumberFormat().format(p.price_thb)} <span class="room-card-per">/ package</span></div>
          <div class="room-card-actions">
            <span class="btn btn-outline-light fw-bold">View Details</span>
            <span class="btn btn-danger fw-bold">Reserve</span>
          </div>
        </div>
      </a>
    </div>
  `;
}

function activityCard(a) {
  const imgUrl = normalizeApiImageUrl(a.cover_image_url) || a.cover_image_url || '';
  const mediaContent = imgUrl
    ? `<img src="${imgUrl}" alt="${a.title}" style="width: 100%; height: 100%; object-fit: cover;">`
    : '<div style="font-size: 3rem;">🌴</div>';

  // Map activity titles to gallery filters
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

function galleryItem(item, i) {
  const imgUrl = item.image_url;
  return `
    <a class="g-item glightbox" href="${imgUrl}" data-gallery="resort">
      <img src="${imgUrl}" alt="Gallery Image" loading="lazy">
    </a>
  `;
}

function handlePackageCardClick(event) {
  const card = event.target.closest(".package-card");
  if (!card) return;
  if (event.target.closest("a")) return;
  const key = card.dataset.packageKey;
  if (!key) return;
  window.location.href = `package-details.html?slug=${encodeURIComponent(key)}`;
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
  if (data.adults) params.set('adults', data.adults);
  if (data.children) params.set('children', data.children);
  if (data.package_options) {
    params.set('package_options', data.package_options);
    params.set('options', data.package_options);
  }
  window.location.href = `booking.html?${params.toString()}`;
};

// ===== Package Detail Modal =====
let activePackage = null;
let selectedOptions = [];

window.openPackageModal = (id) => {
  const p = currentPackages.find(x => x.id == id);
  if (!p) return;
  activePackage = p;
  selectedOptions = [];

  document.getElementById('mdlPkgTitle').textContent = p.title;
  document.getElementById('mdlPkgSubtitle').textContent = p.subtitle || '';
  document.getElementById('mdlPkgImage').src = normalizeApiImageUrl(p.image_url) || '';
  document.getElementById('mdlPkgDesc').textContent = p.description;
  document.getElementById('mdlPkgPrice').textContent = 'THB ' + new Intl.NumberFormat().format(p.price_thb);

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
  if (activePackage.code === 'ULTIMATE-JUNGLE' && selectedOptions.length !== 2) {
    document.getElementById('mdlOptionsError').style.display = 'block';
    return;
  }
  const sel = document.getElementById('packageSelect');
  if (sel) sel.value = activePackage.id;
  const optInput = document.getElementById('heroPackageOptions');
  if (optInput) optInput.value = selectedOptions.join(',');
  closePackageModal();
  document.getElementById('heroBookingForm')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
};

// ===== Scroll Progress Bar =====
function initScrollProgress() {
  const bar = document.querySelector('.scroll-progress__bar');
  if (!bar) return;

  window.addEventListener('scroll', () => {
    const pct = (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100;
    bar.style.width = Math.min(pct, 100) + '%';
  }, { passive: true });
}
