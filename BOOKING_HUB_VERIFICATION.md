# Booking Hub Verification Checklist

## 1. Booking Types & Blocks
- [ ] **Room Mode**: `booking.html?type=room`
    - [ ] `roomBookingBlock` is visible.
    - [ ] Check-in/Check-out dates are prefilled (today/tomorrow).
    - [ ] Guest steppers change "Summary" sidebar.
    - [ ] Submit works -> Returns booking code (type=room).

- [ ] **Package Mode**: `booking.html?type=package&package_id=1&arrival_date=2026-10-25&package_options=1,2`
    - [ ] `packageBookingBlock` is visible.
    - [ ] Package Name is displayed (fetched from API).
    - [ ] Arrival Date is 2026-10-25 (readonly).
    - [ ] Options 1 & 2 are checked in the grid.
    - [ ] Try unchecking 1 option -> Error message should disappear (valid allows 1 or 2 options? No, requirement says "exactly 2").
    - [ ] Try checking a 3rd option -> Alert "You can only select 2 activities".
    - [ ] Try submitting with 1 option -> Alert "Please select exactly 2 activities".
    - [ ] Submit with 2 valid options -> Returns booking code (type=package).

- [ ] **Tour Mode**: `booking.html?type=tour&activity_id=1`
    - [ ] `tourBookingBlock` is visible.
    - [ ] Activity selector is populated (fetched from API).
    - [ ] Time slot selector updates based on activity.
    - [ ] Guest stepper updates "Summary" price (Activity Price * Guests).
    - [ ] Submit works -> Returns booking code (type=tour).

## 2. Mobile Layout
- [ ] View on mobile (resize browser < 992px).
- [ ] Sticky top bar shows "Total Estimate" and "Item Name".
- [ ] Desktop sidebar summary is hidden.
- [ ] Floating "Checkmark" button is visible bottom-right.
- [ ] Clicking Floating button submits form (triggers validation first).

## 3. Summary Updates
- [ ] Change inputs in any block.
- [ ] Verify Desktop Summary updates instantly.
- [ ] Verify Mobile Top Bar updates instantly.

## 4. API & Controller Checks
- [ ] **Package Submit**: Check payload includes `package_id`, `check_in` (arrival), `check_out` (arrival+duration), `package_options`.
- [ ] **Controller**: Verify `BookingController` saves `package_options` to DB pivot table.
- [ ] **Voucher**: Verify generated voucher PDF/HTML displays correct package options.

## 5. Navigation Checks
- [ ] **Footer Buttons**: Clicking "Book Now" works and goes to correct type.
- [ ] **Activities Page**: "Book/Enquire" goes to `booking.html?type=tour&activity_id=...`.
- [ ] **Package Details**: "Book This Package" passes all params (id, date, options) correctly.
