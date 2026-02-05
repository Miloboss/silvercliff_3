// booking.js - Precise Booking Flow Integration
(function () {
    // 1. URL Parameter Parsing
    const urlParams = new URLSearchParams(window.location.search);
    const bookingType = urlParams.get('type') || 'room';

    // 2. DOM State Management
    const state = {
        booking_type: bookingType,
        package_id: urlParams.get('package_id') || '',
        activity_id: urlParams.get('activity_id') || '',
        check_in: urlParams.get('checkin') || new Date().toISOString().split('T')[0],
        check_out: urlParams.get('checkout') || '',
        adults: parseInt(urlParams.get('adults')) || 2,
        children: parseInt(urlParams.get('children')) || 0,
        tour_date: urlParams.get('checkin') || new Date().toISOString().split('T')[0],
        tour_time: urlParams.get('tour_time') || '',
        packages: [],
        activities: []
    };

    // Calculate default checkout (tomorrow) if missing
    if (!state.check_out && (state.booking_type === 'room' || state.booking_type === 'package')) {
        const d = new Date(state.check_in);
        d.setDate(d.getDate() + 1);
        state.check_out = d.toISOString().split('T')[0];
    }

    // 3. UI Selectors
    const form = document.getElementById('finalBookingForm');
    const submitBtn = document.getElementById('submitBooking');
    const successModal = document.getElementById('successModal');
    const finalCodeDisplay = document.getElementById('finalCode');

    // Summary elements
    const summTitle = document.getElementById('summTitle');
    const summCheckIn = document.getElementById('summCheckIn');
    const summCheckOut = document.getElementById('summCheckOut');
    const summGuests = document.getElementById('summGuests');
    const summPrice = document.getElementById('summPrice');
    const summTypeBadge = document.getElementById('summType');
    const checkOutCol = document.getElementById('checkOutCol');

    // 4. Initial UI Setup
    function setupTypeVisibility() {
        document.querySelectorAll('.booking-type-section').forEach(s => s.classList.add('d-none'));
        const activeSection = document.getElementById(`${state.booking_type}Fields`);
        if (activeSection) activeSection.classList.remove('d-none');

        if (state.booking_type === 'tour') {
            checkOutCol?.classList.add('d-none');
        } else {
            checkOutCol?.classList.remove('d-none');
        }
    }

    function syncStateToInputs() {
        document.getElementById('adults').value = state.adults;
        document.getElementById('children').value = state.children;

        if (state.booking_type === 'room') {
            document.getElementById('checkin').value = state.check_in;
            document.getElementById('checkout').value = state.check_out;
        } else if (state.booking_type === 'package') {
            document.getElementById('pkgCheckin').value = state.check_in;
            document.getElementById('pkgCheckout').value = state.check_out;
        } else if (state.booking_type === 'tour') {
            document.getElementById('tourDate').value = state.tour_date;
        }
    }

    // 5. API Data Loading
    async function loadData() {
        try {
            // Packages
            const pkgRes = await fetch('/api/packages');
            state.packages = await pkgRes.json();
            const pkgSelect = document.getElementById('packageSelect');
            if (pkgSelect) {
                pkgSelect.innerHTML = state.packages.map(p =>
                    `<option value="${p.id}" ${p.id == state.package_id ? 'selected' : ''}>${p.title}</option>`
                ).join('');
                if (!state.package_id && state.packages.length) {
                    state.package_id = state.packages[0].id;
                }
            }

            // Activities
            const actRes = await fetch('/api/activities');
            state.activities = await actRes.json();
            const actSelect = document.getElementById('activitySelect');
            if (actSelect) {
                actSelect.innerHTML = state.activities.map(a =>
                    `<option value="${a.id}" ${a.id == state.activity_id ? 'selected' : ''}>${a.title}</option>`
                ).join('');
                if (!state.activity_id && state.activities.length) {
                    state.activity_id = state.activities[0].id;
                }
                updateTimeSlots();
            }

            updateSummary();
        } catch (err) {
            console.error("Failed to load catalog data", err);
        }
    }

    function updateTimeSlots() {
        const tourTimeSelect = document.getElementById('tourTime');
        if (!tourTimeSelect) return;

        const activity = state.activities.find(a => a.id == state.activity_id);
        if (activity && activity.time_slots && activity.time_slots.length > 0) {
            tourTimeSelect.innerHTML = activity.time_slots.map(slot =>
                `<option value="${slot}" ${state.tour_time === slot ? 'selected' : ''}>${slot}</option>`
            ).join('');
            if (!state.tour_time || !activity.time_slots.includes(state.tour_time)) {
                state.tour_time = activity.time_slots[0];
            }
        } else {
            tourTimeSelect.innerHTML = '<option value="">Flexible (Admin will confirm)</option>';
            state.tour_time = '';
        }
    }

    // 6. Summary Logic
    function updateSummary() {
        summTypeBadge.textContent = state.booking_type;

        if (state.booking_type === 'package') {
            const pkg = state.packages.find(p => p.id == state.package_id);
            summTitle.textContent = pkg ? pkg.title : 'Selected Package';
            summPrice.textContent = pkg ? 'THB ' + new Intl.NumberFormat().format(pkg.price_thb) : '--';
            summCheckIn.textContent = state.check_in;
            summCheckOut.textContent = state.check_out;
        } else if (state.booking_type === 'tour') {
            const act = state.activities.find(a => a.id == state.activity_id);
            summTitle.textContent = act ? act.title : 'Selected Tour';
            summPrice.textContent = act && act.price_thb ? 'THB ' + new Intl.NumberFormat().format(act.price_thb) : 'Enquiry Only';
            summCheckIn.textContent = state.tour_date + (state.tour_time ? ' @ ' + state.tour_time : '');
        } else {
            summTitle.textContent = 'Room Stay (Bungalow)';
            summPrice.textContent = 'Pay on arrival';
            summCheckIn.textContent = state.check_in;
            summCheckOut.textContent = state.check_out;
        }

        summGuests.textContent = `${state.adults} Adults, ${state.children} Children`;
    }

    // 7. Event Listeners
    function initListeners() {
        // Form field changes
        const fields = [
            { id: 'checkin', key: 'check_in' },
            { id: 'checkout', key: 'check_out' },
            { id: 'pkgCheckin', key: 'check_in' },
            { id: 'pkgCheckout', key: 'check_out' },
            { id: 'tourDate', key: 'tour_date' },
            { id: 'tourTime', key: 'tour_time' },
            { id: 'packageSelect', key: 'package_id' },
            { id: 'activitySelect', key: 'activity_id' }
        ];

        fields.forEach(f => {
            const el = document.getElementById(f.id);
            if (el) {
                el.addEventListener('change', (e) => {
                    state[f.key] = e.target.value;
                    if (f.id === 'activitySelect') {
                        updateTimeSlots();
                    }
                    updateSummary();
                });
            }
        });

        // Steppers (Adults/Children) - Handled in app.js but we listen for change
        document.getElementById('adults').addEventListener('change', (e) => {
            state.adults = parseInt(e.target.value);
            updateSummary();
        });
        document.getElementById('children').addEventListener('change', (e) => {
            state.children = parseInt(e.target.value);
            updateSummary();
        });

        // Final Submit
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (state.booking_type !== 'tour' && new Date(state.check_out) <= new Date(state.check_in)) {
                alert("Check-out must be after check-in.");
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Sending...';

            const payload = {
                booking_type: state.booking_type,
                full_name: document.getElementById('fullName').value,
                whatsapp: document.getElementById('phone').value,
                email: document.getElementById('email').value,
                notes: document.getElementById('notes').value,
                adults: state.adults,
                children: state.children
            };

            if (state.booking_type === 'room') {
                payload.check_in = state.check_in;
                payload.check_out = state.check_out;
            } else if (state.booking_type === 'package') {
                payload.package_id = state.package_id;
                payload.check_in = state.check_in;
                payload.check_out = state.check_out;
            } else if (state.booking_type === 'tour') {
                payload.activity_id = state.activity_id;
                payload.tour_date = state.tour_date;
                payload.tour_time = state.tour_time;
            }

            try {
                const res = await fetch('/api/bookings', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await res.json();

                if (res.ok) {
                    finalCodeDisplay.textContent = result.booking_code;
                    const statusLink = document.getElementById('statusLink');
                    if (statusLink) statusLink.href = `booking-status.html?code=${result.booking_code}`;
                    successModal.classList.add('is-open');
                    document.body.style.overflow = 'hidden';
                } else {
                    alert("Error: " + (result.message || "Failed to submit booking. Check all fields."));
                }
            } catch (err) {
                alert("Connection error. Please try again.");
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Confirm & Send Request';
            }
        });
    }

    // 8. Lifecycle
    setupTypeVisibility();
    syncStateToInputs();
    loadData();
    initListeners();

})();
