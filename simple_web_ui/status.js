// status.js - Booking Status Lookup
(function () {
    const form = document.getElementById('statusLookupForm');
    const statusBtn = document.getElementById('statusBtn');
    const resultArea = document.getElementById('resultArea');
    const errorMsg = document.getElementById('lookupError');

    // Result elements
    const statusBadge = document.getElementById('statusBadge');
    const resPackage = document.getElementById('resPackage');
    const resCode = document.getElementById('resCode');
    const resDates = document.getElementById('resDates');
    const resGuests = document.getElementById('resGuests');
    const resUpdated = document.getElementById('resUpdated');
    const tripPlanSection = document.getElementById('tripPlanSection');
    const tripPlanMessage = document.getElementById('tripPlanMessage');
    const tripPlanList = document.getElementById('tripPlanList');

    function renderTripPlan(status, tripPlan) {
        if (!tripPlanSection || !tripPlanMessage || !tripPlanList) return;

        tripPlanList.innerHTML = '';
        tripPlanMessage.textContent = '';

        if (status !== 'confirmed') {
            tripPlanSection.classList.add('d-none');
            return;
        }

        tripPlanSection.classList.remove('d-none');

        if (!Array.isArray(tripPlan) || tripPlan.length === 0) {
            tripPlanMessage.textContent = 'Your booking is confirmed. Detailed activity times will be shared soon.';
            return;
        }

        tripPlan.forEach((item, index) => {
            const li = document.createElement('li');
            li.className = 'list-group-item bg-transparent text-white border-secondary px-0 py-3';

            const title = item.title || `Day ${item.day_no || index + 1}`;
            const dateText = item.date || 'Date to be confirmed';
            const timeText = item.time || 'Time to be confirmed';
            const dayPrefix = item.day_no ? `Day ${item.day_no} · ` : '';
            const description = item.description || '';

            li.innerHTML = `
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <div class="fw-semibold">${dayPrefix}${title}</div>
                        <div class="small text-muted-soft mt-1">${dateText} · ${timeText}</div>
                        ${description ? `<div class="small mt-1">${description}</div>` : ''}
                    </div>
                    <span class="badge ${item.status === 'completed' ? 'bg-success' : 'bg-secondary'}">${(item.status || 'planned').toUpperCase()}</span>
                </div>
            `;

            tripPlanList.appendChild(li);
        });
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const bookingCode = document.getElementById('bookingCode').value.trim();
        const whatsappLast4 = document.getElementById('whatsappLast4').value.trim();
        const email = document.getElementById('email').value.trim();

        if (!whatsappLast4 && !email) {
            errorMsg.textContent = "Please provide either your WhatsApp's last 4 digits or email.";
            return;
        }

        errorMsg.textContent = "";
        statusBtn.disabled = true;
        statusBtn.innerHTML = "Searching...";
        resultArea.classList.add('d-none');
        if (tripPlanSection) tripPlanSection.classList.add('d-none');

        try {
            const query = new URLSearchParams();
            if (whatsappLast4) query.set('whatsapp_last4', whatsappLast4);
            if (email) query.set('email', email);

            const response = await fetch(`/api/bookings/check/${encodeURIComponent(bookingCode)}?${query.toString()}`, {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            });

            const result = await response.json();

            if (response.ok) {
                // Success
                resPackage.textContent = result.package_title;
                resCode.textContent = result.booking_code;
                resDates.textContent = result.check_out
                    ? `${result.check_in} to ${result.check_out}`
                    : `On ${result.check_in} (Tour)`;
                resGuests.textContent = `${result.adults} Adults, ${result.children} Children`;
                resUpdated.textContent = result.updated_at;

                // Status Badge
                statusBadge.textContent = result.status.toUpperCase();
                statusBadge.className = 'badge'; // Reset
                if (result.status === 'confirmed') statusBadge.classList.add('bg-success');
                else if (result.status === 'cancelled') statusBadge.classList.add('bg-danger');
                else statusBadge.classList.add('bg-warning', 'text-dark');

                renderTripPlan(result.status, result.trip_plan || []);

                resultArea.classList.remove('d-none');
                resultArea.scrollIntoView({ behavior: 'smooth' });
            } else {
                errorMsg.textContent = result.message || "Lookup failed. Please verify your details.";
            }
        } catch (err) {
            console.error(err);
            errorMsg.textContent = "Connection error. Please try again.";
        } finally {
            statusBtn.disabled = false;
            statusBtn.innerHTML = "Check Status";
        }
    });
})();
