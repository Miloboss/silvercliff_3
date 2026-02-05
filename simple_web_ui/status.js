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

        try {
            const response = await fetch('/api/bookings/status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    booking_code: bookingCode,
                    whatsapp_last4: whatsappLast4 || null,
                    email: email || null
                })
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
