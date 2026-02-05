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
  if (packageGrid || activitiesGrid || galleryGrid) {
    loadSettings();
    loadPackages();
    loadActivities();
    loadGallery();
  }

  // Category filters setup
  document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      renderGallery(btn.dataset.filter);
    });
  });

  // Year in footer
  const yearEl = document.getElementById("year");
  if (yearEl) yearEl.textContent = new Date().getFullYear();
});

// ===== GLOBAL HELPERS & API LOGIC =====
let currentPackages = [];
let currentGallery = [];
let currentActivities = [];
const ACTIVITIES_LIMIT = 6;
const GALLERY_LIMIT = 12;

async function loadSettings() {
  try {
    const res = await fetch('/api/settings');
    const data = await res.json();
    if (data.tagline) {
      const taglineEl = document.getElementById('heroTagline');
      if (taglineEl) taglineEl.textContent = data.tagline;
    }
    if (data.hero_text) {
      const titleEl = document.getElementById('heroTitle');
      if (titleEl) titleEl.innerHTML = data.hero_text;
    }
    const waNum = data.whatsapp_number || '';
    const cleanWa = waNum.replace(/\D/g, '');
    const waEl = document.getElementById('quickWhatsapp');
    if (waEl) waEl.textContent = waNum;
    const emailEl = document.getElementById('quickEmail');
    if (emailEl) emailEl.textContent = data.email || '';
    const locEl = document.getElementById('quickLocation');
    if (locEl) locEl.textContent = data.map_location || '';
  } catch (err) { console.error("Failed to load settings", err); }
}

async function loadPackages() {
  try {
    const res = await fetch('/api/packages');
    const data = await res.json();
    currentPackages = data;
    const packageGrid = document.getElementById("packageGrid");
    if (packageGrid) packageGrid.innerHTML = data.map(packageCard).join("");
    const packageSelect = document.getElementById("packageSelect");
    if (packageSelect) {
      packageSelect.innerHTML = '<option value="" disabled selected>Select a package</option>';
      data.forEach(pkg => {
        const o = document.createElement("option");
        o.value = pkg.id; o.textContent = pkg.title;
        packageSelect.appendChild(o);
      });
    }
  } catch (err) { console.error("Failed to load packages", err); }
}

async function loadActivities() {
  try {
    const res = await fetch('/api/activities');
    const data = await res.json();
    currentActivities = data;
    const grid = document.getElementById("activitiesGrid");
    if (grid) {
      // If on index page (has carousel nav), limit and init carousel
      const isIndex = !!document.getElementById('actNext');
      const displayedItems = isIndex ? data.slice(0, ACTIVITIES_LIMIT) : data;
      grid.innerHTML = displayedItems.map(activityCard).join("");
      if (isIndex) initActivitiesCarousel();
    }
  } catch (err) { console.error("Failed to load activities", err); }
}

async function loadGallery() {
  try {
    const res = await fetch('/api/gallery');
    const data = await res.json();
    currentGallery = data;
    renderGallery('all');
  } catch (err) { console.error("Failed to load gallery", err); }
}

function renderGallery(filter = 'all') {
  const grid = document.getElementById("galleryGrid");
  if (!grid) return;

  let filtered = filter === 'all' ? currentGallery : currentGallery.filter(i => i.category.toLowerCase() === filter.toLowerCase());

  // If on home page (detected by View More button existence), limit items
  const isHome = !!document.querySelector('.view-more-wrap');
  if (isHome) {
    filtered = filtered.slice(0, GALLERY_LIMIT);
  }

  grid.innerHTML = filtered.map(galleryItem).join("");
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
    const cardWidth = cards[0].offsetWidth + 16; // width + gap
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
    if (Math.abs(diff) > 50) { // threshold
      if (diff > 0) {
        // swipe left -> next
        const visibleCards = getVisibleCardsCount();
        if (actCurrentIndex < cards.length - visibleCards) {
          actCurrentIndex++;
          updateCarousel();
        }
      } else {
        // swipe right -> prev
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
  const imgUrl = p.image_path ? `/storage/${p.image_path}` : 'https://images.unsplash.com/photo-1502672023488-70e25813eb80?auto=format&fit=crop&w=1200&q=80';
  const badge = p.is_best_offer ? 'BEST OFFER' : 'HOT';
  return `
    <div class="col-12 col-lg-4">
      <div class="img-card">
        <img src="${imgUrl}" alt="${p.title}">
        <div class="img-overlay">
          <div class="img-overlay-title">${badge} • THB ${new Intl.NumberFormat().format(p.price_thb)}</div>
          <div class="img-overlay-sub">${p.title}</div>
        </div>
      </div>
      <div class="glass-card p-3 mt-2">
        <div class="fw-bold">${p.title}</div>
        <div class="text-muted-soft small mt-1">${p.subtitle || p.description.substring(0, 100) + '...'}</div>
        <div class="d-flex gap-2 mt-3 flex-wrap">
          <button class="btn btn-danger fw-bold" onclick="goToBookingPage({ package_id: '${p.id}', adults: 2, children: 0 })">Reserve</button>
          <a class="btn btn-outline-light fw-bold" href="#gallery">See Photos</a>
        </div>
      </div>
    </div>
  `;
}

function activityCard(a) {
  return `
    <article class="cardx">
      <div class="cardx-media" style="height: 200px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.05);">
        <div style="font-size: 3rem;">🌴</div>
        <div class="cardx-tag">TOUR</div>
        ${a.price_thb ? `<div class="cardx-price">THB ${new Intl.NumberFormat().format(a.price_thb)}</div>` : ''}
      </div>
      <div class="cardx-body">
        <div class="cardx-code">ACT-${a.id}</div>
        <h3 class="cardx-title">${a.title}</h3>
        <p class="cardx-desc" style="font-size: 0.85rem; color: var(--muted);">${a.description}</p>
        <div class="cardx-actions">
          <button class="btn btn-danger btn-mini w-100" onclick="goToBookingPage({ booking_type: 'tour', activity_id: '${a.id}' })">Book</button>
        </div>
      </div>
    </article>
  `;
}

function galleryItem(item, i) {
  const imgUrl = `/storage/${item.image_path}`;
  return `
    <a class="g-item glightbox" href="${imgUrl}" data-gallery="resort">
      <img src="${imgUrl}" alt="Gallery Image">
    </a>
  `;
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
  window.location.href = `booking.html?${params.toString()}`;
};
