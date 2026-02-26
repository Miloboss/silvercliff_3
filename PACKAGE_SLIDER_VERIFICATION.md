# Package Slider Verification Checklist

## 1. Structure Restoration
- [ ] In `index.html`, `#packageGrid` is now a `<div class="scroll-row">` (horizontal scroll container) instead of `<div class="row">` (grid).
- [ ] In `app-v2.js`, `packageCard()` renders `<div class="scroll-item">` instead of Bootstrap cols.

## 2. Layout & Design
- [ ] Card styles (glass-card, img-card) are preserved.
- [ ] All package cards are now vertical for consistent slider height.
- [ ] "Ultimate Jungle" package still highlighted with `border-primary` and "FEATURED" badge, but follows the same vertical layout as others for slider consistency.

## 3. Functionality
- [ ] **Mobile**: Swipe left/right works smoothly (native CSS scroll snapping).
- [ ] **Desktop**: Horizontal scrollbar is available, and mouse wheel (shift+wheel) works.
- [ ] **Links**: Clicking anywhere on the card (or the "View Details" button) redirects to `package-details.html?slug=...`.
- [ ] **Nav**: Navigation logic (click handler) remains robust via event delegation on `#packageGrid`.

## 4. How to Test
1. Open homepage.
2. Scroll to "Packages".
3. Verify cards are in a single horizontal row, not a multi-row grid.
4. On Mobile: Swipe the cards. They should snap into place.
5. On Desktop: Use shift+scroll or drag the scrollbar.
6. Click the "Ultimate Jungle Experience" card. Verify it goes to the details page.
