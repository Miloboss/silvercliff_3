// booking.js - 3-Block Booking Hub
(function () {
    // 1. URL Parameter Parsing
    const urlParams = new URLSearchParams(window.location.search);
    const normalizeBookingType = (value) => {
        const clean = String(value || '').trim().toLowerCase();
        return ['room', 'tour', 'package'].includes(clean) ? clean : '';
    };

    // Prefer explicit booking_type first, then type, then safe fallback.
    const bookingType = normalizeBookingType(urlParams.get('booking_type'))
        || normalizeBookingType(urlParams.get('type'))
        || 'room';

    // 2. DOM State Management
    const state = {
        type: bookingType, // room | package | tour
        // Room Params
        check_in: urlParams.get('check_in') || urlParams.get('checkin') || new Date().toISOString().split('T')[0],
        check_out: urlParams.get('check_out') || urlParams.get('checkout') || '',
        adults: parseInt(urlParams.get('adults')) || 2,
        children: parseInt(urlParams.get('children')) || 0,
        room_slug: urlParams.get('room_slug') || urlParams.get('room_type') || '',

        // Package Params
        package_id: urlParams.get('package_id') || '',
        arrival_date: urlParams.get('arrival_date') || urlParams.get('check_in') || '',
        // Accept options via correct key
        package_options: (urlParams.get('package_options') || urlParams.get('options') || '').split(',').filter(Boolean).map(Number),

        // Tour Params
        tour_id: urlParams.get('activity_id') || '',
        tour_date: urlParams.get('tour_date') || new Date().toISOString().split('T')[0],
        tour_time: urlParams.get('tour_time') || '',
        tour_pax: parseInt(urlParams.get('adults')) || 2,

        // Data
        packages: [],
        activities: []
    };

    // Default checkout calculation for Room
    if (!state.check_out && state.type === 'room') {
        const d = new Date(state.check_in);
        d.setDate(d.getDate() + 1);
        state.check_out = d.toISOString().split('T')[0];
    }

    // 3. UI Selectors
    const form = document.getElementById('finalBookingForm');
    const submitBtn = document.getElementById('submitBtn');

    // Blocks
    const blocks = {
        room: document.getElementById('roomBookingSection'),
        package: document.getElementById('packageBookingSection'),
        tour: document.getElementById('tourBookingSection')
    };

    // Summary Elements (Desktop & Mobile)
    const ui = {
        desktopTitle: document.getElementById('summItemTitle'),
        desktopDate: document.getElementById('summDate'),
        desktopDetails: document.getElementById('summDetails'),
        desktopPrice: document.getElementById('summPrice'),

        mobileTitle: document.getElementById('mobileSummTitle'),
        mobileDate: document.getElementById('mobileSummDate'),
        mobilePrice: document.getElementById('mobileSummPrice')
    };

    // 4. Initialization
    function init() {
        showActiveBlock();
        syncInputs();
        updateSummary();
        loadCatalog();
        initListeners();
    }

    function showActiveBlock() {
        Object.values(blocks).forEach(b => {
            if (b) b.classList.add('d-none');
        });

        if (blocks[state.type]) {
            blocks[state.type].classList.remove('d-none');
        } else {
            // fallback
            state.type = 'room';
            if (blocks.room) blocks.room.classList.remove('d-none');
        }
    }

    function syncInputs() {
        // Room
        const rCin = document.getElementById('roomCheckin');
        if (rCin) rCin.value = state.check_in;
        const rCout = document.getElementById('roomCheckout');
        if (rCout) rCout.value = state.check_out;
        const rAd = document.getElementById('roomAdultsDisplay');
        if (rAd) rAd.textContent = state.adults;
        const rCh = document.getElementById('roomChildrenDisplay');
        if (rCh) rCh.textContent = state.children;

        // Package
        if (state.type === 'package') {
            // No packageId input is currently in HTML (read-only text is used), so we skip setting value
            const pArr = document.getElementById('pkgArrivalDate');
            if (pArr) pArr.value = state.arrival_date;
        }

        // Tour
        const tDate = document.getElementById('tourDate');
        if (tDate) tDate.value = state.tour_date;
        const tPax = document.getElementById('tourPaxDisplay');
        if (tPax) tPax.textContent = state.tour_pax;
    }

    // 5. Data Loading & Rendering
    async function loadCatalog() {
        try {
            // Load Packages
            if (state.type === 'package') {
                const res = await fetch('/api/packages');
                state.packages = await res.json();
                const pkg = state.packages.find(p => p.id == state.package_id);

                if (pkg) {
                    const nameEl = document.getElementById('pkgNameDisplay');
                    if (nameEl) nameEl.value = pkg.title;
                    renderPackageOptions(pkg);
                }
            }

            // Load Activities (for Tour type)
            if (state.type === 'tour') {
                const res = await fetch('/api/activities');
                state.activities = await res.json();

                const select = document.getElementById('tourSelect');
                if (select) {
                    select.innerHTML = state.activities.map(a =>
                        `<option value="${a.id}" ${state.tour_id == a.id ? 'selected' : ''}>${a.title}</option>`
                    ).join('');

                    if (!state.tour_id && state.activities.length) {
                        state.tour_id = state.activities[0].id;
                    }
                    updateTimeSlots();
                }
            }

            updateSummary();
        } catch (e) {
            console.error("Data load failed", e);
        }
    }

    function renderPackageOptions(pkg) {
        const container = document.getElementById('pkgOptionsContainer');
        if (!container) return;

        // Filter options to only show SELECTED ones for the package booking summary
        // Since package booking page is mostly for confirmation, we show readonly state or selected state
        // But user might want to see what they picked.

        if (!pkg.options || pkg.options.length === 0) {
            container.innerHTML = '<div class="alert alert-secondary py-2 small">Standard itinerary included.</div>';
            return;
        }

        // We only render the ones that are in state.package_options
        const selectedOpts = pkg.options.filter(opt => state.package_options.includes(opt.id));

        if (selectedOpts.length === 0) {
            container.innerHTML = '<div class="alert alert-warning py-2 small">No specific activities selected.</div>';
            return;
        }

        container.innerHTML = selectedOpts.map(opt => `
            <div class="p-3 border rounded border-danger bg-dark bg-opacity-25 mb-2">
                <div class="fw-bold text-danger">✓ ${opt.name}</div>
                <div class="small text-muted-soft">${opt.description || ''}</div>
            </div>
        `).join('');

        // Hide error since we are just displaying
        const err = document.getElementById('pkgOptionsError');
        if (err) err.style.display = 'none';
    }

    window.updateGuests = function (type, delta) {
        if (type === 'adults') {
            state.adults = Math.max(1, state.adults + delta);
            document.getElementById('roomAdultsDisplay').textContent = state.adults;
        } else {
            state.children = Math.max(0, state.children + delta);
            document.getElementById('roomChildrenDisplay').textContent = state.children;
        }
        updateSummary();
    };

    window.updateTourPax = function (delta) {
        state.tour_pax = Math.max(1, state.tour_pax + delta);
        document.getElementById('tourPaxDisplay').textContent = state.tour_pax;
        updateSummary();
    }

    function updateTimeSlots() {
        const select = document.getElementById('tourTime');
        const act = state.activities.find(a => a.id == state.tour_id);
        if (!select) return;

        if (act && act.time_slots && act.time_slots.length) {
            select.innerHTML = act.time_slots.map(t =>
                `<option value="${t}" ${state.tour_time === t ? 'selected' : ''}>${t}</option>`
            ).join('');
            if (!state.tour_time) state.tour_time = act.time_slots[0];
        } else {
            select.innerHTML = '<option value="">Flexible (Confirm later)</option>';
            state.tour_time = '';
        }
    }

    // 6. Summary Logic
    function updateSummary() {
        let title = '', date = '', details = '', price = '';

        if (state.type === 'package') {
            const pkg = state.packages.find(p => p.id == state.package_id);
            title = pkg ? pkg.title : 'Selected Package';
            date = state.arrival_date ? `Arrival: ${state.arrival_date}` : 'Select Date';
            details = `${state.package_options.length} Activities Included`;

            // Calculate total price if possible (Package price is per person usually, but let's assume total for now or price_thb * 2)
            // Package logic in controller uses price_thb as total or per pax? Controller uses price_thb.
            // Usually packages are per person. Let's assume 2 pax for display if we want to be fancy, but keeping it simple as per Controller
            price = pkg ? `THB ${new Intl.NumberFormat().format(pkg.price_thb)}` : '--';
        } else if (state.type === 'tour') {
            const act = state.activities.find(a => a.id == state.tour_id);
            title = act ? act.title : 'Selected Tour';
            date = `${state.tour_date} ${state.tour_time ? '@ ' + state.tour_time : ''}`;
            details = `${state.tour_pax} Guests`;
            price = act ? `THB ${new Intl.NumberFormat().format(act.price_thb * state.tour_pax)}` : 'On Request';
        } else {
            title = 'Room Stay (Bungalow)';
            date = `${state.check_in} ➝ ${state.check_out}`;
            details = `${state.adults} Adults, ${state.children} Child`;
            price = 'Pay on Arrival';
        }

        if (ui.desktopTitle) ui.desktopTitle.textContent = title;
        if (ui.desktopDate) ui.desktopDate.textContent = date;
        if (ui.desktopDetails) ui.desktopDetails.textContent = details;
        if (ui.desktopPrice) ui.desktopPrice.textContent = price;

        if (ui.mobileTitle) ui.mobileTitle.textContent = title;
        if (ui.mobileDate) ui.mobileDate.textContent = date;
        if (ui.mobilePrice) ui.mobilePrice.textContent = price;
    }

    // 7. Event Listeners
    function initListeners() {
        // Room inputs
        const rCin = document.getElementById('roomCheckin');
        if (rCin) rCin.addEventListener('change', e => { state.check_in = e.target.value; updateSummary(); });
        const rCout = document.getElementById('roomCheckout');
        if (rCout) rCout.addEventListener('change', e => { state.check_out = e.target.value; updateSummary(); });

        // Tour inputs
        const tSel = document.getElementById('tourSelect');
        if (tSel) tSel.addEventListener('change', e => {
            state.tour_id = e.target.value;
            updateTimeSlots();
            updateSummary();
        });
        const tDate = document.getElementById('tourDate');
        if (tDate) tDate.addEventListener('change', e => { state.tour_date = e.target.value; updateSummary(); });
        const tTime = document.getElementById('tourTime');
        if (tTime) tTime.addEventListener('change', e => { state.tour_time = e.target.value; updateSummary(); });

        // Submit
        if (form) form.addEventListener('submit', handleSubmit);
    }

    function buildPayloadFromForm() {
        const basePayload = {
            booking_type: state.type,
            full_name: document.getElementById('fullName').value.trim(),
            whatsapp: document.getElementById('phone').value.trim(),
            email: document.getElementById('email').value.trim() || null,
            notes: document.getElementById('notes').value.trim() || null
        };

        if (!basePayload.full_name || !basePayload.whatsapp) {
            throw new Error("Please provide your Name and WhatsApp contact.");
        }

        if (state.type === 'package') {
            const pkg = state.packages.find(p => p.id == state.package_id);
            if (!pkg) throw new Error("Invalid Package selected.");
            if (!state.arrival_date) throw new Error("Arrival date is missing.");

            // Construct payload strictly for backend
            return {
                ...basePayload,
                package_id: state.package_id,
                arrival_date: state.arrival_date,
                package_options: state.package_options,
                // Optional defaults if backend requires them, though we don't show inputs
                adults: 2,
                children: 0
            };
        }
        else if (state.type === 'tour') {
            if (!state.tour_id) throw new Error("Please select an activity.");
            if (!state.tour_date) throw new Error("Please select a date.");

            return {
                ...basePayload,
                activity_id: state.tour_id,
                tour_date: state.tour_date,
                tour_time: state.tour_time || null,
                adults: state.tour_pax,
                children: 0
            };
        }
        else { // room
            if (!state.check_in || !state.check_out) throw new Error("Please select valid dates.");

            return {
                ...basePayload,
                check_in: state.check_in,
                check_out: state.check_out,
                adults: state.adults,
                children: state.children
            };
        }
    }

    async function handleSubmit(e) {
        e.preventDefault();

        try {
            const payload = buildPayloadFromForm();
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';
            }

            const res = await fetch('/api/bookings', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            });

            const result = await res.json();

            if (!res.ok) {
                if (res.status === 422) {
                    let msg = result.message || "Validation failed.";
                    if (result.errors) {
                        msg += "\n" + Object.values(result.errors).flat().join("\n");
                    }
                    alert(msg);
                } else {
                    alert("Booking Failed: " + (result.message || "Unknown error"));
                }
                throw new Error("API Error");
            }

            // Success
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="bi bi-check-lg me-2"></i>Request Sent';
                submitBtn.classList.remove('btn-danger');
                submitBtn.classList.add('btn-success');
                submitBtn.disabled = true;
            }

            const codeEl = document.getElementById('finalCode');
            if (codeEl) codeEl.textContent = result.booking_code;

            // If it was a duplicate, maybe update modal text
            if (result.is_duplicate) {
                const modalTitle = document.querySelector('#successModal h2');
                if (modalTitle) modalTitle.textContent = "Booking Already Received";
            }

            document.getElementById('statusLink').href = `booking-status.html?code=${result.booking_code}`;
            document.getElementById('successModal').classList.add('is-open');

        } catch (err) {
            console.warn(err);
            if (err.message && err.message !== "API Error") {
                alert(err.message);
            }
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Confirm Booking';
            }
        }
    }

    // Run
    init();

})();
