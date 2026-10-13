# Manual QA & Release Verification Checklist

Use this checklist before every major release or after changing core checkout, pricing, or RBAC rules.

---

## 1. Cross-Browser Matrix
- [ ] **Google Chrome (Desktop & Android):** Verify hero carousel transitions, hover video playback on product cards, sticky header shadow on scroll, and slide-over cart drawer.
- [ ] **Safari (macOS & iOS 16+):** Verify `100dvh` mobile drawers, smooth horizontal scroll on circle category strip, WebP image rendering, and Apple Pay / Stripe Checkout redirect.
- [ ] **Mozilla Firefox (Desktop):** Verify custom focus rings (`2px solid var(--brand)`), table scrollbars in Admin, and filter range inputs.
- [ ] **Microsoft Edge (Desktop):** Verify Admin `Ctrl+K` quick command palette, CSV exports (Orders, Customers, Newsletter), and PDF/print invoice view.
- [ ] **Samsung Internet (Android):** Verify light-mode forced rendering (`color-scheme: light`) even when OS dark mode is active, and 44x44px minimum tap targets.

---

## 2. Responsive Breakpoints (Zero Horizontal Scroll)
- [ ] **360px (Small Android):**
  - Header collapses to Hamburger + Centered Logo + Search + Bag badge.
  - Product grid renders 2 columns with `12px` gap and 2-line title clamp.
  - Sticky bottom "Add to Cart" bar appears on Product Detail page when main CTA scrolls out of view.
  - Admin sidebar collapses into slide-over drawer.
- [ ] **390px (iPhone 14/15/16):**
  - Circle categories scroll horizontally with peek of the next item.
  - Slide-over cart drawer fits 100% width without clipping quantity steppers.
- [ ] **768px (iPad Mini / Portrait Tablet):**
  - Product grid shows 3 columns; filter drawer opens cleanly as a slide-over.
  - Checkout displays stacked form with collapsible order summary.
- [ ] **1024px (Landscape Tablet / Compact Laptop):**
  - Desktop mega menu opens on hover with 120ms intent delay and closes on `Esc`.
  - Admin layout shows persistent 240px left sidebar.
- [ ] **1440px+ (Desktop Wide):**
  - Content stays centered inside `1280px` max-width container with generous whitespace.

---

## 3. Accessibility (Keyboard & Screen Reader)
- [ ] **Keyboard-Only Navigation:**
  - Press `Tab` from top of homepage: "Skip to content" link appears and jumps to `<main>`.
  - Open Mega Menu, Cart Drawer, Quick View Modal, and Admin Confirm Dialogs; verify `Esc` closes each one and returns focus.
  - Every interactive button/link displays a visible `2px` brand-color outline on `:focus-visible`.
- [ ] **Screen Reader Basics (NVDA / VoiceOver):**
  - Icon-only buttons (Search, Wishlist Heart, Cart Bag, Close `X`, Chat Launcher) announce clear `aria-label` descriptions.
  - Form validation errors announce below inputs with `role="alert"` or `aria-live="polite"`.

---

## 4. Network & Edge-Case States
- [ ] **Slow 3G Throttling:**
  - Skeleton placeholders render immediately without layout shift (`CLS < 0.1`).
  - Double-clicking "Proceed to Payment" or "Add to Bag" is prevented by disabled/loading button state.
- [ ] **Empty States:**
  - Empty Cart Drawer shows friendly illustration/icon + "Continue Shopping" CTA.
  - Empty Wishlist shows link to `/shop`.
  - Zero search results show helpful message + clear filters button.
- [ ] **Content Edge Cases:**
  - **Long Product Names (100+ chars):** Truncates cleanly to 2 lines on product cards (`line-clamp-2`) and wraps gracefully on Product Detail.
  - **Product With No Image:** Falls back to neutral `#F3F4F6` placeholder without broken image icon.
  - **0 Stock Product / Variant:** Displays "Out of stock" badge, disables "Add to Bag", and reveals the **Notify Me When Available** waitlist form.
  - **Big Carts (20+ distinct line items):** Cart drawer scrolls internally while header and footer subtotal/checkout CTA stay pinned.

---

## 5. Checkout, Coupons, Stripe & Refunds
- [ ] **Expired / Invalid Coupon:** Entering an expired code, unreached `min_spend`, or unreached `min_qty` shows a clear inline validation message without clearing the cart.
- [ ] **Stock Race Condition:** If stock drops below cart quantity before checkout completes, server returns `422` and adjusts cart quantity with an explanatory notice.
- [ ] **Failed / Canceled Payment:** Clicking "Back" on Stripe Hosted Checkout lands on `/checkout/cancel`, preserves the customer's cart, and allows one-click retry.
- [ ] **Successful Payment & Webhook Idempotency:** Completing payment with Stripe test card `4242 4242 4242 4242` marks the order `paid` once even if the `checkout.session.completed` webhook is delivered multiple times.
- [ ] **Full & Partial Refunds:** Issuing a refund from `Admin > Orders > Detail` calls Stripe Refund API, updates `refunded_amount`, optionally restores variant stock, and sends the Customer Refund email.

---

## 6. Role-Based Access Control (RBAC) Verification
- [ ] **`super_admin`:** Full access to all modules, Settings, Stripe keys, Staff/Roles, and One-Click DB Backup.
- [ ] **`product_manager`:** Can manage Products, Categories, Brands, Menus, Pages, Marketing, and Reviews; receives `403` on Finance, Orders, Settings, and Users.
- [ ] **`order_manager`:** Can manage Orders, Shipments, and Customers; cannot issue Stripe refunds or view Finance/Settings.
- [ ] **`support_agent`:** Can reply in Live Chat, view Orders/Customers, and moderate Reviews; cannot edit Products or Settings.
- [ ] **`finance_manager`:** Can view Finance reports, export CSV, and process Refunds; cannot edit Products or Site Settings.
- [ ] **`customer`:** Redirected away from `/admin/*` with `403`; can only access own orders and addresses under `/account`.
