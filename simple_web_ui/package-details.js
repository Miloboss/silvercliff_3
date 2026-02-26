// Package Details Page - RECOVERED Version with Full Content
document.addEventListener('DOMContentLoaded', () => {
  if (typeof AOS !== 'undefined') {
    AOS.init({ duration: 650, once: true });
  }
  document.getElementById('year').textContent = new Date().getFullYear();

  const urlParams = new URLSearchParams(window.location.search);
  const slug = urlParams.get('slug');

  if (!slug) {
    showErrorState('No package specified');
    return;
  }

  loadPackageDetails(slug);
});

let currentPackage = null;
let selectedOptionIds = [];

// Form state
let formData = {
  checkIn: '',
  checkOut: '',
  adults: 2,
  children: 0
};

// Image URLs for content sections
const activityImages = {
  0: 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?auto=format&fit=crop&w=400&q=80',
  1: 'https://images.unsplash.com/photo-1519904981063-b0cf448d479e?auto=format&fit=crop&w=400&q=80',
  2: 'https://images.unsplash.com/photo-1511497584788-876760111969?auto=format&fit=crop&w=400&q=80',
  3: 'https://images.unsplash.com/photo-1475924156734-496f6cac6ec1?auto=format&fit=crop&w=400&q=80'
};

const day2LakeImage = 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?auto=format&fit=crop&w=1200&q=80';
const day3JungleImage = 'https://images.unsplash.com/photo-1542273917363-3b1817f69a2d?auto=format&fit=crop&w=800&q=80';

const accommodationImages = [
  { url: 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?auto=format&fit=crop&w=600&q=80', caption: 'Octagon River Room Exterior' },
  { url: 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=600&q=80', caption: 'Cozy Interior View' },
  { url: 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?auto=format&fit=crop&w=600&q=80', caption: 'River Balcony View' }
];

async function loadPackageDetails(slug) {
  try {
    let packageData;

    // Try slug-based endpoint first
    try {
      const response = await fetch(`/api/packages/${slug}`);
      if (response.ok) {
        packageData = await response.json();
      }
    } catch (e) {
      console.log('Slug fetch failed, trying list approach');
    }

    // Fallback: get all packages and find by slug
    if (!packageData) {
      const response = await fetch('/api/packages');
      const packages = await response.json();
      packageData = packages.find(p => p.slug === slug);
    }

    if (!packageData) {
      showErrorState('Package not found');
      return;
    }

    currentPackage = packageData;
    renderPackagePage(packageData);
    hideLoading();
  } catch (error) {
    console.error('Error loading package:', error);
    showErrorState('Failed to load package details');
  }
}

function renderPackagePage(pkg) {
  // Update page title
  document.getElementById('pageTitle').textContent = `${pkg.title} — Silver Cliff Resort`;

  // Render Hero
  renderHero(pkg);

  // Render all content sections
  renderDay1Section(pkg);
  renderDay2Section();
  renderDay3Section();
  renderAccommodationSection();
  renderFinalDaySection(pkg);

  // Render What's Included
  renderIncludes(pkg);

  // Set default dates for form
  setDefaultDates();
}

function renderHero(pkg) {
  const heroSection = document.getElementById('heroSection');
  const imgUrl = pkg.image_url || 'https://images.unsplash.com/photo-1542273917363-3b1817f69a2d?auto=format&fit=crop&w=1400&q=80';
  heroSection.style.backgroundImage = `linear-gradient(to bottom, rgba(5, 10, 4, 0.7), rgba(5, 10, 4, 0.9)), url('${imgUrl}')`;

  document.getElementById('packageTitle').textContent = pkg.title;
  document.getElementById('packageSubtitle').textContent = pkg.subtitle || '';
  document.getElementById('packageDescription').textContent = pkg.description || '';

  const formattedPrice = `THB ${new Intl.NumberFormat().format(pkg.price_thb)}`;
  document.getElementById('packagePrice').textContent = formattedPrice;
  document.getElementById('mobilePriceDisplay').textContent = formattedPrice;
}

function renderDay1Section(pkg) {
  const container = document.getElementById('day1Container');
  if (!container) return;

  const hasOptions = pkg.options && pkg.options.length > 0;

  let optionsHTML = '';
  if (hasOptions) {
    optionsHTML = `
            <div class="day1-options-box mt-4">
                <h5 class="mb-3">⚡ Choose Your Day 1 Adventures</h5>
                <p class="text-muted-soft mb-4">Select any <strong>two</strong> activities for your first day:</p>
                
                <div class="activity-grid">
                    ${pkg.options.map((opt, idx) => `
                        <div class="activity-card" data-option-id="${opt.id}" onclick="toggleOption(${opt.id})">
                            <img src="${activityImages[idx % 4]}" alt="${opt.name}" loading="lazy">
                            <div class="activity-info">
                                <div class="activity-name">${opt.name}</div>
                                <div class="activity-desc">${opt.description || 'Experience nature'}</div>
                            </div>
                            <div class="activity-check">✓</div>
                        </div>
                    `).join('')}
                </div>
                
                <div class="selection-status mt-3">
                    <span class="selected-count" id="selectedCount">0</span> / 2 selected
                </div>
            </div>
        `;
  }

  container.innerHTML = `
        <div class="timeline-item">
            <div class="timeline-dot">1</div>
            <div class="timeline-content">
                <div class="timeline-day">Day 1</div>
                <div class="timeline-title">Arrival & Adventure</div>
                <div class="timeline-desc">
                    Begin your jungle experience with a warm welcome at Silver Cliff Resort. 
                    After settling into your accommodation, embark on your chosen adventure activities. 
                    Whether you opt for a serene canoe safari, an invigorating jungle trek, or a mystical 
                    night exploration, each experience offers a unique perspective of Khao Sok's incredible biodiversity.
                </div>
                ${optionsHTML}
            </div>
        </div>
    `;
}

function renderDay2Section() {
  const container = document.getElementById('day2Container');
  if (!container) return;

  container.innerHTML = `
        <div class="timeline-item">
            <div class="timeline-dot">2</div>
            <div class="timeline-content">
                <div class="timeline-day">Day 2</div>
                <div class="timeline-title">Cheow Lan Lake Exploration</div>
                <div class="timeline-desc">
                    Experience the breathtaking beauty of Cheow Lan Lake, often called the "Guilin of Thailand." 
                    Cruise through emerald waters surrounded by towering limestone cliffs that rise dramatically 
                    from the lake. Visit hidden caves, spot exotic wildlife, and immerse yourself in one of 
                    Thailand's most stunning natural wonders. This full-day excursion includes longtail boat tours, 
                    swimming in pristine waters, and a delicious local lunch.
                </div>
                <div class="day-image-large mt-4">
                    <img src="${day2LakeImage}" alt="Cheow Lan Lake" loading="lazy">
                    <div class="image-caption">The stunning Cheow Lan Lake with its emerald waters and limestone karsts</div>
                </div>
            </div>
        </div>
    `;
}

function renderDay3Section() {
  const container = document.getElementById('day3Container');
  if (!container) return;

  container.innerHTML = `
        <div class="timeline-item">
            <div class="timeline-dot">3</div>
            <div class="timeline-content">
                <div class="timeline-day">Day 3</div>
                <div class="timeline-title">Deep Jungle Immersion</div>
                <div class="timeline-desc">
                    Dive deeper into the ancient rainforest with guided treks through pristine trails. 
                    Discover towering trees that have stood for centuries, exotic flora with medicinal properties, 
                    and if you're lucky, glimpses of gibbons, hornbills, and other rare wildlife. 
                    Cool off in natural jungle pools and learn about the delicate ecosystem from expert guides 
                    who call this forest home.
                </div>
                <div class="day-image mt-3">
                    <img src="${day3JungleImage}" alt="Jungle Trek" loading="lazy">
                </div>
            </div>
        </div>
    `;
}

function renderAccommodationSection() {
  const container = document.getElementById('accommodationContainer');
  if (!container) return;

  container.innerHTML = `
        <div class="accommodation-section glass-card p-4 mb-4">
            <h3 class="h5 fw-bold mb-3">🏡 Your Jungle Retreat</h3>
            <p class="text-muted-soft mb-4">
                Enjoy 1 night in our exclusive <strong>Octagon River Room</strong> at Silver Cliff Resort. 
                These unique eight-sided rooms blend seamlessly with nature while offering modern comfort. 
                Wake up to the symphony of the jungle, watch the mist rise over the river from your private balcony, 
                and fall asleep to the soothing sounds of flowing water and nocturnal wildlife.
            </p>
            
            <div class="accommodation-gallery">
                ${accommodationImages.map(img => `
                    <div class="accommodation-img-card">
                        <img src="${img.url}" alt="${img.caption}" loading="lazy">
                        <div class="img-caption">${img.caption}</div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
}

function renderFinalDaySection(pkg) {
  const container = document.getElementById('finalDayContainer');
  if (!container || !pkg.itineraries) return;

  const lastDay = pkg.itineraries[pkg.itineraries.length - 1];
  if (!lastDay) return;

  container.innerHTML = `
        <div class="timeline-item">
            <div class="timeline-dot">${lastDay.day_no}</div>
            <div class="timeline-content">
                <div class="timeline-day">Day ${lastDay.day_no}</div>
                <div class="timeline-title">${lastDay.title}</div>
                <div class="timeline-desc">${lastDay.description}</div>
            </div>
        </div>
    `;
}

function renderIncludes(pkg) {
  const container = document.getElementById('includesList');
  if (!pkg.includes || pkg.includes.length === 0) return;

  container.innerHTML = pkg.includes.map(item => `
        <li class="col-md-6">${item}</li>
    `).join('');
}

// Option selection
function toggleOption(optionId) {
  const idx = selectedOptionIds.indexOf(optionId);

  if (idx > -1) {
    // Deselect
    selectedOptionIds.splice(idx, 1);
  } else {
    // Select (max 2)
    if (selectedOptionIds.length >= 2) {
      showInlineError('You can only select 2 activities. Deselect one first.');
      return;
    }
    selectedOptionIds.push(optionId);
  }

  updateOptionsUI();
}

function updateOptionsUI() {
  // Update card states
  document.querySelectorAll('.activity-card').forEach(card => {
    const optionId = parseInt(card.dataset.optionId);
    if (selectedOptionIds.includes(optionId)) {
      card.classList.add('selected');
    } else {
      card.classList.remove('selected');
    }
  });

  // Update counter
  const countEl = document.getElementById('selectedCount');
  if (countEl) {
    countEl.textContent = selectedOptionIds.length;
    countEl.style.color = selectedOptionIds.length === 2 ? '#7fa631' : '#dc3545';
  }
}

function showInlineError(message) {
  // Create temporary error toast
  const toast = document.createElement('div');
  toast.className = 'error-toast';
  toast.textContent = '⚠️ ' + message;
  document.body.appendChild(toast);

  setTimeout(() => {
    toast.classList.add('show');
  }, 100);

  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

// Booking Form Modal
function openBookingForm() {
  renderBookingFormModal();
  const modal = document.getElementById('bookingFormModal');
  modal.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeBookingForm() {
  const modal = document.getElementById('bookingFormModal');
  modal.classList.remove('active');
  document.body.style.overflow = '';
}

function renderBookingFormModal() {
  const formContainer = document.getElementById('bookingFormContainer');
  const hasOptions = currentPackage && currentPackage.options && currentPackage.options.length > 0;

  let optionsSection = '';
  if (hasOptions) {
    optionsSection = `
            <div class="form-section">
                <h5 class="form-section-title">Selected Activities</h5>
                <div class="selected-options-display" id="selectedOptionsDisplay">
                    ${selectedOptionIds.length === 0 ?
        '<p class="text-muted-soft text-center py-3">Please select your Day 1 activities from the timeline above</p>' :
        currentPackage.options
          .filter(opt => selectedOptionIds.includes(opt.id))
          .map(opt => `<div class="selected-option-badge">✓ ${opt.name}</div>`)
          .join('')
      }
                </div>
                <div class="form-error" id="optionsError" style="display: none;"></div>
            </div>
        `;
  }

  formContainer.innerHTML = `
        <div class="form-section">
            <h5 class="form-section-title">Travel Dates</h5>
            <div class="form-row">
                <div class="form-group">
                    <label>Check-in</label>
                    <input type="date" id="checkInDate" class="form-input" onchange="updateCheckIn(this.value)">
                </div>
                <div class="form-group">
                    <label>Check-out</label>
                    <input type="date" id="checkOutDate" class="form-input" onchange="updateCheckOut(this.value)">
                </div>
            </div>
            <div class="form-error" id="datesError" style="display: none;"></div>
        </div>
        
        <div class="form-section">
            <h5 class="form-section-title">Guests</h5>
            <div class="form-row">
                <div class="form-group">
                    <label>Adults</label>
                    <div class="stepper">
                        <button type="button" class="stepper-btn" onclick="updateAdults(-1)">−</button>
                        <span class="stepper-value" id="adultsValue">2</span>
                        <button type="button" class="stepper-btn" onclick="updateAdults(1)">+</button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Children</label>
                    <div class="stepper">
                        <button type="button" class="stepper-btn" onclick="updateChildren(-1)">−</button>
                        <span class="stepper-value" id="childrenValue">0</span>
                        <button type="button" class="stepper-btn" onclick="updateChildren(1)">+</button>
                    </div>
                </div>
            </div>
        </div>
        
        ${optionsSection}
        
        <button class="btn-book-now" onclick="proceedToBooking()">
            Continue to Booking →
        </button>
    `;

  // Re-apply current form data
  setTimeout(() => {
    if (formData.checkIn) document.getElementById('checkInDate').value = formData.checkIn;
    if (formData.checkOut) document.getElementById('checkOutDate').value = formData.checkOut;
    document.getElementById('adultsValue').textContent = formData.adults;
    document.getElementById('childrenValue').textContent = formData.children;
  }, 0);
}

function setDefaultDates() {
  const today = new Date();
  const tomorrow = new Date(today);
  tomorrow.setDate(tomorrow.getDate() + 1);
  const dayAfter = new Date(today);
  dayAfter.setDate(dayAfter.getDate() + 4); // 4-day package

  formData.checkIn = tomorrow.toISOString().split('T')[0];
  formData.checkOut = dayAfter.toISOString().split('T')[0];
}

// Form handlers
function updateCheckIn(value) {
  formData.checkIn = value;
  const checkOutInput = document.getElementById('checkOutDate');
  if (checkOutInput) checkOutInput.min = value;
  hideFormError('datesError');
}

function updateCheckOut(value) {
  formData.checkOut = value;
  hideFormError('datesError');
}

function updateAdults(delta) {
  formData.adults = Math.max(1, formData.adults + delta);
  const el = document.getElementById('adultsValue');
  if (el) el.textContent = formData.adults;
}

function updateChildren(delta) {
  formData.children = Math.max(0, formData.children + delta);
  const el = document.getElementById('childrenValue');
  if (el) el.textContent = formData.children;
}

// Validation and redirect
function proceedToBooking() {
  let isValid = true;

  // Validate dates
  if (!formData.checkIn || !formData.checkOut) {
    showFormError('datesError', 'Please select both check-in and check-out dates');
    isValid = false;
  } else if (new Date(formData.checkOut) <= new Date(formData.checkIn)) {
    showFormError('datesError', 'Check-out date must be after check-in date');
    isValid = false;
  } else {
    hideFormError('datesError');
  }

  // Validate options if required
  const hasOptions = currentPackage && currentPackage.options && currentPackage.options.length > 0;
  if (hasOptions && selectedOptionIds.length !== 2) {
    showFormError('optionsError', 'Please select exactly 2 activities from the timeline above');
    isValid = false;
  } else {
    hideFormError('optionsError');
  }

  if (!isValid) {
    return;
  }

  // Build redirect URL
  const params = new URLSearchParams();
  params.set('package_id', currentPackage.id);
  params.set('type', 'package');
  params.set('check_in', formData.checkIn);
  params.set('check_out', formData.checkOut);
  params.set('adults', formData.adults);
  params.set('children', formData.children);

  if (selectedOptionIds.length > 0) {
    params.set('package_options', selectedOptionIds.join(','));
  }

  if (currentPackage.slug) {
    params.set('slug', currentPackage.slug);
  }

  window.location.href = `booking.html?${params.toString()}`;
}

// Error display
function showFormError(elementId, message) {
  const errorEl = document.getElementById(elementId);
  if (errorEl) {
    errorEl.textContent = '⚠️ ' + message;
    errorEl.style.display = 'block';
  }
}

function hideFormError(elementId) {
  const errorEl = document.getElementById(elementId);
  if (errorEl) {
    errorEl.style.display = 'none';
  }
}

function showErrorState(msg) {
  document.getElementById('loadingState').style.display = 'none';
  document.getElementById('errorState').style.display = 'block';
  document.getElementById('packageContent').style.display = 'none';
}

function hideLoading() {
  document.getElementById('loadingState').style.display = 'none';
  document.getElementById('errorState').style.display = 'none';
  document.getElementById('packageContent').style.display = 'block';
}

// Mobile sticky bar visibility
window.addEventListener('scroll', () => {
  const bar = document.getElementById('mobileBookingBar');
  if (!bar) return;

  if (window.scrollY > 500) {
    bar.classList.add('visible');
  } else {
    bar.classList.remove('visible');
  }
});
