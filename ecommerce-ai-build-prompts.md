# Ecommerce Web App: Step-by-Step AI Build Prompts

**Stack:** Vue 3 (frontend) · Laravel (API + admin backend) · MySQL · Stripe · Hostinger SMTP email
**Hosting:** Hostinger Business plan
**Style:** Light, clean, modern, rounded. No dark mode. One simple font.

---

## 0. Read this first

### How to use this file
1. Open a new chat with your AI coding tool (Claude Code, Cursor, or similar) in an empty project folder.
2. Paste **Section 2 (Master Context)** first. Re-paste it at the start of every new session. The AI forgets.
3. Run the **Build Prompts in order** (Section 4). Do not skip ahead. Each step depends on the one before it.
4. After each prompt, run the app and test the **Acceptance Check** before moving on.
5. Commit to Git after every step that passes. If a later step breaks things, you can roll back.

### Important Hostinger Business plan limits (the AI must respect these)
| Limit | What it means for this build |
|---|---|
| Shared hosting, no long-running processes | **No WebSockets server** (no Reverb/Soketi). Live chat uses fast polling instead. |
| No permanent queue worker | Queue runs through a **cron job** every minute (`queue:work --stop-when-empty`). |
| Node.js may not be available for builds | **Build Vue on your computer**, upload the compiled files. |
| SSH + Composer + Cron are available | Use SSH for `composer install`, `artisan migrate`, cache commands. |
| MySQL included | Use MySQL with proper indexes. No Redis, so use file or database cache. |

### Architecture decision (already made for you)
- **Vue 3 SPA** (Vite, Vue Router, Pinia, Tailwind CSS) talks to a **Laravel REST API**.
- Both live on the **same domain**. Laravel serves the built Vue app. This avoids CORS problems and lets Laravel Sanctum use secure cookie login.
- **Spatie Laravel Permission** handles roles and permissions.
- **Stripe Checkout (hosted)** takes payments. Stripe **webhooks** are the source of truth for "paid".
- Images are converted to **WebP** and resized on upload for speed.

---

## 1. Design System (paste into the AI when it asks about design)

**Feel:** Calm, bright, trustworthy, like a well-run boutique store. Not a template.

| Token | Value |
|---|---|
| Page background | `#FFFFFF` |
| Soft surface (sections, cards) | `#F7F7F5` |
| Text | `#111827` |
| Muted text | `#6B7280` |
| Border | `#E5E7EB` |
| Brand color (default) | `#2F5D50` (admin can change it in Site Settings, stored as CSS variable) |
| Accent (stars, sale tags) | `#E8A317` |
| Success / Error | `#15803D` / `#B91C1C` |
| Font | **Inter** only, weights 400, 500, 600, 700. Self-hosted WOFF2, `font-display: swap`. Fallback: system-ui |
| Spacing scale | 4 / 8 / 12 / 16 / 24 / 32 / 48 / 64 / 96 px |
| Radius | Cards 16px · Inputs 12px · Buttons pill (999px) · Category tiles fully circular |
| Shadow | One soft shadow only: `0 6px 24px rgba(17,24,39,0.06)` |
| Icons | **Lucide** only. No emoji as icons. |
| Container | max-width 1280px, side padding 16px mobile / 32px desktop |

**Layout rules**
- Mobile first. Test at 360, 768, 1024, 1440 px.
- Tap targets at least 44px.
- Every image has fixed aspect ratio (product cards 4:5, hero 16:7 desktop / 4:5 mobile) so nothing jumps while loading.
- Skeleton loaders instead of spinners.

**"No AI slop" rules (the AI must follow all of these)**
1. No purple/blue gradients, no glassmorphism, no glowing blobs, no neon.
2. No lorem ipsum and no fake-sounding copy like "Elevate your lifestyle" or "Unlock the power of". Write short, plain, human sentences.
3. Real-looking placeholder content (real product names, believable prices) in the seed data.
4. One icon set, one font, one brand color, one shadow style. Consistency over decoration.
5. No animations that block the user. Subtle only: 150–250ms ease on hover, fade on image change.
6. Respect `prefers-reduced-motion`.
7. Buttons say what they do: "Add to cart", "Place order", "Save changes". Not "Submit".
8. Empty states and error messages are written for humans ("Your cart is empty. Let's fix that.").

---

## 2. Master Context Prompt (paste at the start of every session)

```
You are a senior full-stack engineer and a professional UI/UX designer. We are building a production-ready ecommerce website that will be hosted on a Hostinger Business shared hosting plan.

STACK
- Frontend: Vue 3 (Composition API, <script setup>), Vite, Vue Router, Pinia, Tailwind CSS, Lucide icons, @unhead/vue for SEO meta.
- Backend: Laravel (latest stable that supports PHP 8.2), Laravel Sanctum (cookie SPA auth), Spatie Laravel Permission, MySQL.
- Payments: Stripe Checkout (hosted) + webhooks, using stripe/stripe-php.
- Email: Hostinger SMTP, configured from the admin dashboard and applied at runtime.
- Image handling: Intervention Image, convert uploads to WebP, create thumb/medium/large sizes.

HOSTING LIMITS (must respect)
- No WebSocket server, so live chat uses short polling (every 3-5 seconds, lightweight "since_id" endpoint).
- No permanent queue worker, so queues use the database driver and a cron job runs "php artisan queue:work --stop-when-empty" every minute.
- No Redis. Use file/database cache.
- Vue is built locally and the compiled files are served by Laravel from the public folder.

DESIGN
- Light theme only. No dark mode.
- Clean, modern, rounded UI. Font: Inter only. Icons: Lucide only.
- Brand color is a CSS variable that the admin can change.
- Category tiles are circles. Buttons are pills. Cards have 16px radius.
- No AI-looking design: no gradients blobs, no glassmorphism, no emoji icons, no lorem ipsum, no corporate buzzwords.
- Fully responsive, mobile first.

ENGINEERING RULES
- Write clean, typed-as-far-as-reasonable, well-structured code. Use Laravel Form Requests, API Resources, Policies, Services, and Eloquent relationships with eager loading (no N+1 queries).
- Every admin action is protected by permissions, not just by role names.
- Never expose admin-only fields in public API responses.
- Validate everything server-side. Escape/sanitize rich text.
- Add database indexes for every foreign key and every column used in filtering or sorting.
- Keep dependencies minimal. Do not add a package if 20 lines of code can do it.
- After each task: list the files created/changed, how to run it, and how to test it. Do not move on until I confirm.
- If something I ask conflicts with the hosting limits, tell me and propose the closest stable alternative.

Reply "Ready" and wait for my first task.
```

---

## 3. Planned folder structure (give this to the AI in Step 1)

```
shop/
├── backend/                      # Laravel app
│   ├── app/
│   │   ├── Http/Controllers/Api/         # Public storefront API
│   │   ├── Http/Controllers/Admin/       # Admin API
│   │   ├── Http/Requests/
│   │   ├── Http/Resources/
│   │   ├── Models/
│   │   ├── Policies/
│   │   ├── Services/                     # Stripe, Mail, Images, Cart, Orders
│   │   ├── Mail/
│   │   └── Jobs/
│   ├── database/migrations, seeders
│   ├── routes/api.php, admin.php, web.php
│   └── public/ (built Vue lands here)
└── frontend/                     # Vue app
    └── src/
        ├── api/            # axios client + endpoint files
        ├── stores/         # Pinia: auth, cart, settings, wishlist, chat
        ├── router/
        ├── layouts/        # StoreLayout, AdminLayout, AuthLayout
        ├── components/     # ui/, store/, admin/
        ├── views/          # store/ and admin/ pages
        ├── composables/
        └── assets/fonts, css/
```

---

## 4. The Build Prompts (run in order)

> Format of every prompt: **Goal → Requirements → Deliverables → Acceptance Check**.
> Copy only the text inside the code block.

---

### STEP 1: Project setup

```
TASK: Set up the project foundation.

1. Create the folder structure: shop/backend (Laravel) and shop/frontend (Vue 3 + Vite).
2. Backend: install Laravel, Sanctum, Spatie Laravel Permission, stripe/stripe-php, Intervention Image. Configure for MySQL. Set up .env.example with every variable we will need (APP_*, DB_*, SESSION_DOMAIN, SANCTUM_STATEFUL_DOMAINS, QUEUE_CONNECTION=database, CACHE_STORE=file, STRIPE_*, MAIL_*).
3. Frontend: Vue 3, Vue Router, Pinia, Tailwind CSS, Lucide (lucide-vue-next), axios, @unhead/vue. Self-host the Inter font (400/500/600/700, woff2).
4. Set up Tailwind with the design tokens from my design system: colors, radius, shadow, spacing, container. Brand color must read from a CSS variable --brand so it can change at runtime.
5. Configure Vite so that "npm run build" outputs into backend/public/app (or similar) and Laravel serves index for all non-API, non-admin-asset routes (SPA fallback).
6. Dev setup: Vite dev server proxies /api and /sanctum to Laravel so cookies work locally.
7. Add ESLint + Prettier, and a Laravel Pint config.
8. Create a README with the exact commands to run both apps locally.

DELIVERABLES: working empty app where the Vue home page loads and calls GET /api/health which returns {"status":"ok"}.

ACCEPTANCE CHECK: I can run both servers, see the Vue page, and the health call succeeds.
```

---

### STEP 2: Database schema

```
TASK: Design and create all database migrations and models with relationships. Do not build UI yet.

TABLES (use sensible columns, soft deletes where noted, indexes on all FKs, slugs unique):

- users (name, email, phone, password, avatar, is_active, last_login_at, soft deletes). Both customers and staff use this table. Role decides who is who.
- settings (key, value (text), group, is_encrypted boolean). Key-value store for site settings. Secrets (Stripe keys, SMTP password) are stored encrypted.
- categories (parent_id nullable, name, slug, description, image, icon, sort_order, is_active, is_featured, seo_title, seo_description). Unlimited nesting, but UI uses up to 3 levels.
- brands (name, slug, logo)
- products (category_id, brand_id, name, slug, sku, short_description, description (rich text), status [draft, active, archived], is_featured, base_price, compare_at_price, cost_price, track_stock, stock_qty, low_stock_threshold, weight, length, width, height, tax_class, seo_title, seo_description, video_url, video_path, soft deletes)
- product_images (product_id, variant_id nullable, path, external_url nullable, alt, sort_order, is_primary). Supports uploaded images AND external image URLs.
- product_option_types (product_id, name e.g. "Size", sort_order)
- product_option_values (option_type_id, value e.g. "Large", color_hex nullable)
- product_variants (product_id, sku, price, compare_at_price, stock_qty, image_id nullable, is_active, option_values_json)
- product_variant_option_values (pivot)
- product_details_sections (product_id, title, content (rich text), sort_order, is_open_by_default). These power the click-to-open accordion "Item Description & Details".
- product_specs (product_id, label, value, sort_order)
- product_categories pivot (so a product can appear in more than one category)
- tags and product_tag pivot
- reviews (product_id, user_id nullable, author_name, author_avatar nullable, rating 1-5, title, body, is_approved, is_admin_created boolean default false, created_by_admin_id nullable, review_date, helpful_count). IMPORTANT: public API must never reveal is_admin_created or created_by_admin_id.
- carts, cart_items (guest carts by token, user carts by user_id)
- wishlists
- addresses (user_id, label, name, phone, line1, line2, city, state, postal_code, country, is_default_shipping, is_default_billing)
- orders (order_number, user_id nullable, guest_email, status [pending, paid, processing, shipped, delivered, cancelled, refunded], payment_status, subtotal, discount_total, shipping_total, tax_total, grand_total, currency, coupon_code, shipping_address_json, billing_address_json, stripe_session_id, stripe_payment_intent_id, notes, tracking_number, tracking_url, placed_at)
- order_items (order_id, product_id, variant_id, name_snapshot, sku_snapshot, image_snapshot, unit_price, qty, line_total)
- order_status_history (order_id, status, note, changed_by)
- coupons (code, type [percent, fixed], value, min_subtotal, max_uses, used_count, per_user_limit, starts_at, expires_at, is_active)
- shipping_zones, shipping_rates (country list, flat rate or free over amount)
- hero_slides (title, subtitle, button_text, button_url, image_desktop, image_mobile, text_position, overlay_opacity, sort_order, is_active, starts_at, ends_at)
- popups (name, type [image, content, newsletter, announcement], title, body, image, button_text, button_url, trigger [on_load, delay, scroll, exit_intent], trigger_value, show_on_pages_json, frequency [once_per_session, once_per_day, always, once_ever], starts_at, ends_at, is_active, sort_order)
- menus, menu_items (menu_id, parent_id, label, type [category, page, custom_url, mega_group], target_id, url, open_in_new_tab, image, badge_text, sort_order, column_index, is_active). Powers the mega menu.
- pages (title, slug, content, is_published, seo fields): About, FAQ, Shipping, Returns, Privacy, Terms.
- chat_conversations (visitor_token, user_id nullable, visitor_name, visitor_email, status [open, closed], assigned_admin_id, last_message_at, unread_admin_count, unread_visitor_count)
- chat_messages (conversation_id, sender_type [visitor, admin], sender_id nullable, body, attachment_path nullable, read_at)
- newsletter_subscribers (email, is_active, subscribed_at)
- email_templates (key, subject, body_html, is_active). Editable from admin.
- email_logs (to, subject, template_key, status, error, sent_at)
- activity_logs (user_id, action, subject_type, subject_id, properties_json, ip)
- contact_messages (name, email, subject, message, status)

ALSO:
- Eloquent models with relationships, casts, and fillable lists.
- Factories and a DatabaseSeeder that creates: 1 super admin, roles and permissions (see Step 3), 8 categories with 2-4 sub-categories each, 40 realistic products with variants and images (use placeholder image URLs), 3 hero slides, 1 popup, default menu, default pages, default settings, default email templates, 20 reviews.

ACCEPTANCE CHECK: "php artisan migrate:fresh --seed" runs without errors, and I can see all tables and seeded data in MySQL.
```

---

### STEP 3: Authentication, roles and permissions

```
TASK: Build authentication and the role/permission system.

AUTH (customers + staff):
- Sign up, sign in, sign out, forgot password, reset password, email verification (optional toggle in settings), update profile, change password.
- Laravel Sanctum cookie-based SPA auth. Rate limit login (5 attempts per minute per email+IP).
- Vue: login and register pages with clean inline validation, show/hide password, loading states, friendly error messages. Pinia auth store with "fetch current user on app start".
- Route guards in Vue Router (auth required, guest only, permission required).

ROLES (Spatie):
- super_admin: full control. Bypasses all permission checks (Gate::before).
- product_manager: can create/edit products, categories, brands, images, hero slides, reviews. CANNOT see finance, revenue, Stripe settings, payouts, SMTP settings, or user management.
- order_manager: view/update orders, customers, coupons, shipping. Cannot see revenue reports or settings.
- support_agent: live chat, contact messages, reviews moderation, view orders (read only).
- finance_manager: revenue reports, orders (read), refunds, Stripe info (read only).
- customer: storefront only.

PERMISSIONS: Create granular permissions such as products.view, products.create, products.update, products.delete, categories.manage, orders.view, orders.update, orders.refund, finance.view, reports.view, settings.view, settings.update, stripe.manage, mail.manage, users.view, users.create, users.update, roles.manage, reviews.manage, chat.manage, popups.manage, hero.manage, menus.manage, pages.manage, coupons.manage, shipping.manage, activity.view.

SUPER ADMIN FEATURES:
- Users page: create staff user, choose role, enable/disable, reset password, delete.
- Role editor: super admin can create NEW custom roles and tick which permissions each role has (checkbox matrix grouped by area).
- A staff user must never be able to give themselves or others more permissions than they have. Only super_admin can manage roles.

API: /api/me returns the user plus a flat array of permission strings so the Vue UI can hide menu items and buttons. Hiding in the UI is for comfort only; every admin API endpoint must enforce permissions on the server with middleware/policies.

ACCEPTANCE CHECK: Log in as each role. A product_manager cannot open finance pages (403 on API, hidden in UI). Super admin can create a new role and a new user with it.
```

---

### STEP 4: Site settings, logo, theme and Stripe/mail settings backend

```
TASK: Build the settings system (backend API + admin UI later). Settings must be cached (file cache) and cache must clear when saved.

SETTING GROUPS:
1. General: site name, tagline, logo (upload), favicon (upload), brand color, support email, phone, address, currency (code + symbol), timezone.
2. Announcement bar: enabled, text, link, background color, text color, dismissible, start/end date.
3. Social links: Instagram, Facebook, TikTok, YouTube, X, WhatsApp number.
4. Header/footer: footer text, footer columns (link groups), payment icons toggle, copyright text.
5. Stripe: publishable key, secret key (encrypted), webhook signing secret (encrypted), mode (test/live), currency, enable/disable payments. A "Test connection" button that calls Stripe and reports success or the exact error.
6. Email (Hostinger SMTP): host, port, encryption, username, password (encrypted), from name, from address, reply-to. "Send test email" button. Mail config must be applied at runtime from these settings (override Laravel mail config in a service provider, with fallback to .env).
7. Shipping and tax: flat rates, free shipping threshold, tax percentage, prices include tax toggle.
8. SEO: default title, description, social share image, Google Analytics ID, Meta Pixel ID, custom head/body script fields (super admin only).
9. Store: maintenance mode toggle with custom message, guest checkout on/off, review moderation on/off, require email verification on/off, low stock alert email.

RULES:
- Secrets are NEVER returned to the browser in plain text. Return a masked value like "sk_live_••••••1234" and only overwrite when a new value is sent.
- GET /api/public-settings returns only safe values (name, logo, colors, social, announcement, currency, Stripe publishable key, feature toggles).
- Admin endpoints require the right permissions: settings.update for general, stripe.manage for Stripe, mail.manage for SMTP. A product_manager gets 403 on all of them.
- Image uploads for logo and favicon: validate type/size, store, and return URL. SVG logo allowed only if sanitized.
- Log every settings change in activity_logs (without secret values).

ACCEPTANCE CHECK: I can save and read settings through the API, secrets come back masked, the SMTP test email arrives, and the Stripe test button reports properly.
```

---

### STEP 5: Categories, menus and mega menu backend

```
TASK: Build the category tree and menu builder APIs.

CATEGORIES:
- CRUD with image upload (for the circular category tiles), parent selection, sort order (drag-and-drop reorder endpoint), featured toggle, active toggle, SEO fields.
- Public endpoints: GET /api/categories (tree, cached), GET /api/categories/{slug} (with children, breadcrumbs, product count).

MENU BUILDER (for the mega menu):
- A menu has items. Item types: category, page, custom URL, and "mega group" (a titled column).
- Top-level item can be "mega" style: it shows up to 5 columns. Each column has a heading and a list of links (sub-categories, pages, custom links). It can also show one featured image card (image, title, button) on the right side.
- Item fields: label, type, target, URL, open in new tab, badge text (e.g. "New", "Sale"), image, column index, sort order, active.
- Reorder endpoint (drag-and-drop in admin).
- Public endpoint: GET /api/menu/main and /api/menu/footer returns nested JSON, cached and cleared on change.

ACCEPTANCE CHECK: I can create a top-level "Women" item with 4 columns of sub-categories plus a featured image, and the public menu API returns it in the right structure.
```

---

### STEP 6: Products backend (advanced)

```
TASK: Build the full products API with advanced features.

ADMIN PRODUCT FORM (data it must accept and save):
1. Basics: name, slug (auto, editable), SKU, brand, primary category + extra categories, status, featured, tags.
2. Descriptions: short description, full rich description (sanitized HTML).
3. Pricing: base price, compare-at (sale) price, cost price, tax class. Sale badge is auto-calculated.
4. Inventory: track stock toggle, quantity, low-stock threshold, allow backorder toggle.
5. Variations: define option types (Size, Color, Material, etc.) and values (color values can have a hex swatch). Auto-generate the variant combinations. Each variant has its own SKU, price, compare-at price, stock, optional image, active toggle. Bulk edit tool: "set price for all", "increase by %", "set stock for all".
6. Images: multiple images, drag to reorder, set primary, alt text, upload files OR paste an external image URL. Optionally link an image to a variant (when the customer picks "Red", the gallery jumps to red images). Uploads are converted to WebP in 3 sizes (thumb 300px, medium 800px, large 1600px).
7. Video: upload an MP4 (max size configurable, validated) OR paste a video URL (MP4 direct link). Optional poster image. This is the video that plays when a customer hovers over the product card.
8. Item Description & Details sections: repeatable blocks (title + rich content + "open by default" toggle + order). Examples: "Description", "Materials & Care", "Shipping & Returns". These show as click-to-open / click-to-close accordions on the product page.
9. Specifications: label/value rows (Weight, Dimensions, Material...).
10. Shipping: weight and dimensions.
11. SEO: title, description, auto slug, social image.
12. Extras: "Related products" manual picker, "Frequently bought together" picker.

OTHER ENDPOINTS:
- Duplicate product, bulk actions (publish, archive, delete, change category), CSV import/export.
- Public: GET /api/products with filters (category incl. children, price range, brand, in stock, on sale, rating, option values like color/size, search text), sorting (newest, price asc/desc, popular, rating), cursor or page pagination. Index every filterable column.
- Public: GET /api/products/{slug} returns product with variants, images, video, sections, specs, rating summary, related products.
- Search suggestions endpoint: GET /api/search/suggest?q= (fast, limited to 6 results, products + categories).
- Product list API returns only what a card needs (id, name, slug, price, compare price, primary image, second image, video URL, rating, badges). Keep payload small.

ACCEPTANCE CHECK: Create a product with 3 colors x 3 sizes, different prices per variant, 4 images, 1 video and 3 detail sections via API. Filters and search return correct results in under 200ms with the seeded data.
```

---

### STEP 7: Storefront foundation (layout, header, mega menu, footer)

```
TASK: Build the storefront shell in Vue following the design system exactly.

HEADER:
- Slim announcement bar on top (from settings, dismissible).
- Main header: logo (from settings, or site name as text fallback), mega menu navigation, search icon (opens a clean search overlay with live suggestions), wishlist icon, account icon, cart icon with item count badge.
- MEGA MENU (desktop): opens on hover with a short delay and closes with a short delay (no flicker), also opens on keyboard focus. A white panel with rounded bottom corners, 3-5 columns of links, an optional featured image card on the right, and a soft shadow. Closes on Escape and outside click.
- MOBILE: hamburger opens a full-height slide-in drawer. Menu items expand/collapse like an accordion (tap to expand sub-categories). Search bar sits at the top of the drawer. Body scroll is locked while open.
- Header is sticky, shrinks slightly on scroll, never covers anchored content.

FOOTER:
- Link columns from the footer menu, newsletter signup (email only, with success message), social icons, payment icons, copyright. Clean and light.

OTHER SHELL PIECES:
- Toast notifications, global loading bar, 404 page, error boundary, scroll to top on route change, SEO meta per page using @unhead/vue.
- Popup manager component (reads active popups, applies trigger rules and frequency using localStorage, never shows two at once, never shows on checkout, accessible with focus trap and Escape to close).
- Maintenance mode page when enabled.
- Pinia settings store loads /api/public-settings once and applies the brand color CSS variable.

ACCEPTANCE CHECK: The header, mega menu, mobile drawer and footer look great at 360px, 768px and 1440px. Lighthouse accessibility score of 95 or more on this shell.
```

---

### STEP 8: Home page

```
TASK: Build the home page with real, designed sections. It must look like a professionally designed store, not a template.

SECTIONS (in order):
1. HERO SLIDER: full-width images from hero_slides. Large clean headline, one short line of subtext, one pill button. Text sits on a soft overlay for readability (opacity set per slide in admin). Fade transition (not slide), auto-play every 6 seconds, pauses on hover, dots + arrows, swipe on mobile, uses the mobile image on small screens. First image is loaded with priority (no lazy loading) so LCP is fast. Respects reduced motion.
2. CIRCULAR CATEGORIES: a row of round category images (about 96px mobile, 140px desktop) with the name underneath. Horizontal scroll with snap on mobile, centered grid on desktop. Subtle ring on hover. Data from featured categories.
3. FEATURED / NEW ARRIVALS: product grid (2 columns mobile, 3 tablet, 4 desktop) using the ProductCard component.
4. PROMO BANNERS: two rounded banner tiles side by side (stack on mobile), editable from admin (add a "banners" settings block if needed).
5. BEST SELLERS: horizontal scroll carousel of ProductCards.
6. WHY SHOP WITH US: 4 short trust points with Lucide icons (fast delivery, easy returns, secure payment, real support). Text editable from admin settings.
7. CUSTOMER REVIEWS: a calm strip of 3-4 featured reviews with star rating, name, short text.
8. NEWSLETTER: simple one-line signup on a soft surface background.

PRODUCT CARD (reusable):
- 4:5 image with rounded corners, second image fades in on hover.
- If the product has a video: on desktop hover, the video plays muted, looped, inline (preload only on first hover, pause and reset on mouse leave). On mobile do NOT autoplay video; show a small play badge instead and show the video on the product page.
- Wishlist heart (top right), sale badge (top left), "Quick add" button for products with no variants, "Choose options" for products with variants.
- Name, price (with compare-at price struck through), star rating.
- Whole card is one link. Fully keyboard accessible.

PERFORMANCE: lazy load everything below the fold, width/height on all images, srcset from medium/large sizes, skeletons while loading, route-level code splitting.

ACCEPTANCE CHECK: Home page loads fast on a throttled 4G connection, hero has no layout shift (CLS under 0.05), and the video preview plays on hover without lag.
```

---

### STEP 9: Shop, category and search pages

```
TASK: Build the product listing experience.

- Pages: /shop, /category/:slug (with sub-category circles at the top), /search?q=, /sale, /new.
- Breadcrumbs, result count, sort dropdown.
- FILTERS: left sidebar on desktop, bottom-sheet drawer on mobile with "Apply" and "Clear all". Filters: price range slider, brand, color swatches, size chips, rating, in stock, on sale. Filters sync to the URL query string so pages are shareable and the back button works.
- Product grid with pagination or a "Load more" button (not infinite scroll, because it hurts footer access and SEO).
- Empty state with suggestions. Skeleton loaders. Keep previous results visible while new ones load (no flashing).
- SEO: meta title/description per category, canonical URL, structured data (BreadcrumbList).

ACCEPTANCE CHECK: Filtering, sorting and pagination work, the URL updates, and refreshing the page restores the same state.
```

---

### STEP 10: Product detail page

```
TASK: Build the product page. This is the most important page for sales, so make it excellent.

LAYOUT (desktop): gallery on the left (about 55%), purchase panel on the right, sticky while scrolling. Mobile: swipeable gallery with dots, then details.

GALLERY: main image + thumbnails, images and video in one gallery (video shows a play icon on its thumbnail and plays inline with controls). Click to open a full-screen lightbox with zoom. When a color variant is chosen, the gallery jumps to that variant's image.

PURCHASE PANEL: name, rating summary (links to reviews), price with compare-at and "Save X%", short description, option selectors (color swatches as circles, size as pill chips, disabled style for out-of-stock combinations), quantity stepper, "Add to cart" (primary pill button), wishlist button, stock message ("Only 3 left"), delivery estimate text from settings, share button. The price, stock, SKU and image update instantly when the variant changes without a page reload.

ITEM DESCRIPTION & DETAILS (click to open):
- Accordion sections from product_details_sections. Each section is a row with a title and a plus/minus (chevron) icon. Click opens it, click again closes it. Smooth height animation. Sections marked "open by default" start open. Fully keyboard and screen-reader accessible (button, aria-expanded, aria-controls).
- A "Specifications" accordion with a clean two-column table.

REVIEWS SECTION: rating breakdown bars (5 to 1 stars), sort (newest, highest, lowest, helpful), list of reviews with name, stars, date, title, text, "helpful" button, pagination. Logged-in customers who purchased can write a review (goes to moderation if enabled). All reviews look identical whether written by a customer or created by admin.

BELOW: related products, recently viewed (stored in localStorage).

EXTRAS: sticky "Add to cart" bar on mobile that appears after scrolling past the main button. Product JSON-LD structured data (Product, Offer, AggregateRating). Open Graph tags.

ACCEPTANCE CHECK: Variant switching updates price, stock and images correctly, the accordion opens and closes with mouse and keyboard, and the page looks great on a 360px screen.
```

---

### STEP 11: Cart, wishlist and coupons

```
TASK: Build cart, wishlist and coupon logic.

- Cart works for guests (token in cookie) and logged-in users. When a guest logs in, merge the guest cart into the user cart.
- Slide-in mini cart drawer opens when something is added (shows items, quantity change, remove, subtotal, "View cart" and "Checkout" buttons). Also a full /cart page.
- SERVER IS THE SOURCE OF TRUTH for prices and stock. The client never sends prices. Re-validate stock and price on every cart read and at checkout. Show a clear message if an item changed price or went out of stock.
- Free-shipping progress bar ("Add $12 more for free shipping").
- Coupon code field: percent or fixed, minimum subtotal, expiry, usage limits, per-user limit. Clear error messages.
- Shipping estimate based on shipping zones and rates from admin.
- Wishlist: heart toggle everywhere, /wishlist page, guests can wishlist in localStorage and it syncs to the account on login.

ACCEPTANCE CHECK: Add/remove/update works for guest and user, merge works after login, tampering with prices in the browser has no effect, and coupons apply correctly.
```

---

### STEP 12: Checkout and Stripe

```
TASK: Build checkout with Stripe Checkout (hosted) and webhooks.

CHECKOUT PAGE (single page, 2 columns on desktop, stacked on mobile):
- Contact (email), shipping address (saved address picker for logged-in users), shipping method, order notes, order summary with coupon field, totals.
- Guest checkout supported (if enabled in settings), with the option to create an account after.
- Inline validation, address autofill attributes, clear button text: "Continue to payment".

PAYMENT FLOW:
1. Frontend posts address + shipping method. Backend recalculates everything from the database and creates a PENDING order with order_items (snapshot of names, prices, images).
2. Backend creates a Stripe Checkout Session with line items, shipping, discount, success_url, cancel_url, metadata {order_id}, customer_email. Returns the session URL. Frontend redirects.
3. STRIPE WEBHOOK (POST /api/stripe/webhook): verify signature using the webhook secret from settings. Handle checkout.session.completed (mark order paid, reduce stock, clear the cart, send emails), checkout.session.expired (mark cancelled, release anything held), charge.refunded (mark refunded). Webhook handler must be idempotent (safe if Stripe sends the same event twice) and must be excluded from CSRF.
4. Success page: shows order summary by order number (verified by session id, never trust just the URL). Cancel page returns the user to the cart with items intact.
- Stripe keys are loaded from the settings table (decrypted at runtime), with test/live mode.
- Stock is decreased only after payment is confirmed. Handle the race condition when two people buy the last item (use database transaction with row locking).
- Admin can issue full or partial refunds from the order page (calls Stripe refund API, updates order).

ACCEPTANCE CHECK: With Stripe test card 4242 4242 4242 4242, the order becomes paid via webhook only, stock decreases once, emails are queued, duplicate webhooks do nothing, and a failed payment leaves the cart intact.
```

---

### STEP 13: Customer account area

```
TASK: Build the customer account pages (clean, simple, mobile friendly).

- /account overview, /account/orders (list with status pills), /account/orders/:number (details, tracking link, "Buy again", invoice download as printable page/PDF), /account/addresses (add/edit/delete/default), /account/profile (name, email, phone, password), /account/wishlist, /account/reviews.
- Customer can cancel an unshipped order request (creates a note/notification for admin).
- Delete my account option with confirmation.

ACCEPTANCE CHECK: A customer can see only their own data. Changing an order number in the URL never reveals someone else's order.
```

---

### STEP 14: Admin dashboard shell

```
TASK: Build the admin dashboard layout (under /admin in the same Vue app, lazy loaded so it does not slow the storefront).

DESIGN: Same light, clean look. Left sidebar (collapsible, drawer on mobile), top bar with search, notifications bell (new orders, new chat, low stock), profile menu. Content area on a soft grey background with white rounded cards. Tables are clean with sticky headers, row hover, and responsive card view on mobile.

SIDEBAR (items hidden when the user lacks permission):
Dashboard · Orders · Products · Categories · Brands · Customers · Reviews · Coupons · Chat · Marketing (Hero slides, Popups, Newsletter, Banners) · Content (Menus, Pages) · Finance (Revenue, Payouts, Refunds) · Staff & Roles · Site Settings (General, Stripe, Email, Shipping & Tax, SEO) · Email Templates · Activity Log

DASHBOARD HOME (content depends on permissions):
- Cards: today's orders, new customers, low-stock products. Revenue cards ONLY for users with finance.view.
- Charts (lightweight, no heavy chart library: use small SVG/canvas or a tiny library): sales last 30 days, top products, orders by status.
- Latest orders table, open chats count, pending reviews count.

SHARED ADMIN COMPONENTS: DataTable (search, sort, filter, pagination, bulk select), ConfirmDialog, SlideOverForm, ImageUploader (drag and drop, preview, progress, reorder), RichTextEditor (use a small, stable editor such as Tiptap with limited toolbar), ColorPicker, ToggleSwitch, Tabs, EmptyState, FormField with errors.

ACCEPTANCE CHECK: A product_manager sees no finance or settings menu items. Super admin sees everything. Sidebar and tables work on mobile.
```

---

### STEP 15: Admin product management

```
TASK: Build the admin product list and the advanced product form using the Step 6 API.

PRODUCT LIST: image thumbnail, name, SKU, category, price, stock (red if low), status pill, quick toggle for active/featured, search, filters, bulk actions, duplicate, delete with confirmation.

PRODUCT FORM: a clean page with a sticky right-side summary card (status, save button, preview link) and tabs or stacked cards:
1. General (name, slug, brand, categories, tags, status)
2. Description (rich text)
3. Pricing and Inventory
4. Variations: step 1 add option types and values, step 2 table of generated variants with inline editing of price, compare price, stock, SKU, image, active. Bulk edit toolbar. Warn before deleting a variant that has orders.
5. Media: multi-upload with drag reorder, set primary, alt text, "add by URL" field, link image to variant, video upload or URL with poster
6. Item Details sections: add, remove and drag reorder accordion blocks, each with title, rich text and "open by default"
7. Specifications rows
8. Shipping
9. SEO with live Google-style preview
10. Related products picker

UX: autosave draft warning on leaving unsaved changes, inline validation, upload progress, success toasts, keyboard friendly.

ACCEPTANCE CHECK: A product_manager can create a complete product with variants, images and video, and the product appears correctly on the storefront. They still cannot see any finance data.
```

---

### STEP 16: Admin orders, customers and finance

```
TASK: Build order, customer and finance management.

ORDERS: list with filters (status, date, payment, search by number/email), order detail page (items, addresses, payment info, timeline), change status with optional note and "notify customer" checkbox, add tracking number/URL, print packing slip and invoice, refund (full/partial), internal notes, resend confirmation email.
CUSTOMERS: list, profile with order history, notes, enable/disable, export CSV.
FINANCE (permission finance.view only): revenue by day/week/month, average order value, refunds total, tax collected, shipping collected, top products and categories by revenue, Stripe balance and recent payouts (read from Stripe API, cached), export CSV.
INVENTORY: low-stock list with quick stock edit, email alert to the configured address.

ACCEPTANCE CHECK: Changing an order to shipped sends the right email, order manager can manage orders but gets 403 on finance endpoints.
```

---

### STEP 17: Admin hero, popups, menus and pages

```
TASK: Build the marketing and content tools in the admin.

HERO SLIDES: list with drag to reorder, create/edit form (desktop image, mobile image, title, subtitle, button text/link, text position left/center/right, overlay opacity slider, start/end date, active toggle) with a live preview that matches the real hero.

POPUPS: create/edit form with type (image only, content, newsletter signup, announcement), image upload, title, text, button, trigger (on load, after X seconds, after scrolling X%, exit intent), pages to show on (all, home only, specific pages), frequency (once per session, once per day, always, once ever), schedule, active toggle, and a live "preview popup" button. Track views, clicks and signups per popup.

MENU BUILDER: visual tree editor. Drag items to reorder and nest. Add category, page or custom link items. For a top-level item switch "Mega menu" on, then arrange up to 5 columns, add headings and links per column, and add a featured image card. Live preview of the mega menu panel. Separate editors for Main menu and Footer menu.

PAGES: create/edit CMS pages (About, FAQ, Shipping, Returns, Privacy, Terms, Contact) with rich text and SEO fields.

BANNERS and TRUST POINTS for the home page: editable text, image and link.

ACCEPTANCE CHECK: Changes appear on the storefront right after saving (cache cleared), and a product_manager can edit hero and popups only if given the permission.
```

---

### STEP 18: Reviews system

```
TASK: Build reviews in the admin and storefront.

CUSTOMER REVIEWS: purchased customers can submit a review with rating, title, text and optional photos. If moderation is on, status is pending until approved.

ADMIN REVIEW MANAGER:
- List with filters (pending, approved, rating, product), approve/reject/delete, reply publicly as the store (optional).
- "Create review" form for admins: choose product(s), type ANY custom reviewer name, optional avatar/initials, star rating, title, text, and the review DATE. These reviews appear in the storefront exactly like normal customer reviews. The public API and the frontend must not reveal that an admin created them (no flag, no admin id, no "verified" difference). Only the admin panel can see the hidden flag.
- Bulk import reviews from CSV.
- Product rating averages and counts are recalculated and cached when reviews change.

Add a short, clear note inside the admin form reminding the site owner to follow local consumer-protection and advertising laws about review content. Do not remove the feature, just add the note.

ACCEPTANCE CHECK: An admin-created review looks identical to a customer review on the product page, and the public JSON contains no admin-identifying fields.
```

---

### STEP 19: Live chat (works on shared hosting)

```
TASK: Build a live chat between visitors and admin staff WITHOUT WebSockets (Hostinger shared hosting).

VISITOR WIDGET: floating round button bottom-right (brand color). Opens a clean rounded chat panel. First message asks for name and email (optional for logged-in users). Shows "We usually reply within a few minutes" from settings, offline message with an email form when no admin is online (configurable working hours). Message bubbles, timestamps, unread badge, sound toggle, optional image attachment. Conversation persists using a visitor token cookie and merges with the user account on login.

TRANSPORT (polling that is gentle on the server):
- Poll GET /api/chat/messages?since_id=X every 4 seconds while the panel is open, every 30 seconds when closed (only to update the unread badge), and pause when the browser tab is hidden.
- The endpoint must be extremely cheap: single indexed query, return an empty 204/JSON quickly if nothing is new, no heavy middleware.
- Use exponential backoff on errors. Rate limit per visitor.

ADMIN CHAT INBOX (chat.manage permission): left list of conversations (unread first, search, filter open/closed/mine), right pane with the conversation, visitor info (name, email, current page, cart contents, past orders if logged in), quick replies (canned responses managed in admin), assign to staff, close/reopen, internal notes. New message sound and browser notification and sidebar badge. Admin polls every 3 seconds on the chat page and every 20 seconds elsewhere.
- If a visitor leaves an email and the admin replies while they are offline, send the reply by email.

SECURITY: sanitize all messages, limit message length and attachment size/type, throttle spam, visitors can only access their own conversation.

ACCEPTANCE CHECK: Two browsers (visitor and admin) can chat with under 5 seconds delay, server load stays low with 20 open visitor tabs, and chat stops polling in hidden tabs.
```

---

### STEP 20: Email notifications (Hostinger SMTP)

```
TASK: Build the email system.

TRANSPORT: Use the SMTP settings saved in the admin (host, port, encryption, user, password, from name/address). Typical Hostinger values: host smtp.hostinger.com, port 465 with SSL (or 587 with TLS). Apply them at runtime. If sending fails, log the error in email_logs and show it in the admin. All emails are queued (database queue). Provide the cron command for the queue.

EMAILS (each is a Mailable using editable templates from email_templates with placeholders like {{customer_name}}, {{order_number}}, {{order_total}}, {{items_table}}, {{tracking_link}}):
Customer: welcome, email verification, password reset, order confirmation, payment failed, order shipped (with tracking), order delivered (ask for review), order cancelled, refund issued, back-in-stock (optional), chat reply when offline.
Admin/staff: new order, low stock alert, new chat message when offline, new contact message, new review pending.

DESIGN OF EMAILS: single column, 600px, white card on a soft grey background, logo at top, brand-color button, plain readable copy, mobile friendly, tested for Gmail/Outlook (table-based, inline CSS). Footer with store address and contact.

ADMIN UI: Email Templates page (list, edit subject and HTML with placeholder helper, preview, send test to myself, enable/disable per template). Email Log page (status, error, resend). Settings page for SMTP with "Send test email" button and clear success/error output.

ACCEPTANCE CHECK: Placing a test order sends the confirmation to the customer and a new-order alert to the admin through Hostinger SMTP. Templates edited in admin change the real emails.
```

---

### STEP 21: Extra features that make the store feel complete

```
TASK: Add these features one at a time, each stable and tested.

1. Newsletter: subscribe, unsubscribe link, admin list + CSV export, double opt-in toggle.
2. Contact page with form (spam protection with a honeypot field + rate limit), messages inbox in admin.
3. Back-in-stock notify me (email when stock returns).
4. Abandoned cart email (cron, one reminder after 24 hours, with unsubscribe).
5. Gift/promo: free shipping threshold and "buy X get Y percent" style coupon rules (keep simple).
6. Order tracking page: enter order number + email to see status (for guests).
7. Product badges (New, Best seller, Low stock) automatically calculated, with manual override.
8. Compare products (up to 4) is optional: skip unless I ask.
9. Cookie notice banner (simple, accept/decline, controls analytics scripts).
10. Sitemap.xml and robots.txt generated by Laravel (cached), canonical tags, 301 redirect manager for changed product/category slugs.
11. Activity log viewer for super admin (who changed what and when, with IP).
12. Admin "Quick actions" command palette (Ctrl+K) to jump to any admin page or product.
13. Database and uploads backup reminder card on admin dashboard with a one-click DB export (super admin).
14. Multi-currency or multi-language: do NOT build now. Keep code ready (all text in one place) for later.

ACCEPTANCE CHECK: Each feature has an admin on/off or editable setting where it makes sense and works on mobile.
```

---

### STEP 22: Speed and SEO

```
TASK: Make the site ultra fast and search-friendly.

FRONTEND:
- Route-level code splitting, admin bundle fully separate. Tree-shake Lucide (import individual icons only). Preload the Inter font files used above the fold. Lazy load images and below-the-fold components. Use width/height and aspect-ratio on all images. WebP everywhere with srcset/sizes.
- Debounce search, cache API GET responses in memory for a short time (stale-while-revalidate for categories, menu, settings).
- Target: Lighthouse mobile Performance 90+, LCP under 2.5s, CLS under 0.05, total JS for the home page under 200KB gzipped.

BACKEND:
- Eager load all relations, add missing indexes, cache public settings, menus, categories, home sections (file cache) with cache busting on admin save.
- Response caching headers for public GETs (ETag/Cache-Control short TTL).
- Run: config:cache, route:cache, event:cache, view:cache in production, and composer install --optimize-autoloader --no-dev.
- Provide a production .htaccess for Hostinger (Apache): force HTTPS, gzip/brotli, long cache for hashed assets (1 year, immutable), short cache for index.html, security headers, SPA fallback to index.html, deny access to .env, storage, vendor.

SEO (SPA-friendly):
- Per-page title/description/canonical/Open Graph via @unhead/vue.
- For product and category pages, have Laravel inject server-rendered <title>, meta description, Open Graph tags and JSON-LD into the HTML shell when the URL matches /product/{slug} or /category/{slug}, so social previews and search crawlers see correct data without needing JavaScript.
- Sitemap, robots.txt, structured data (Product, BreadcrumbList, Organization, AggregateRating).

ACCEPTANCE CHECK: Run Lighthouse on home, category and product pages (mobile) and fix everything below 90. Share the report.
```

---

### STEP 23: Security hardening

```
TASK: Do a security pass over the whole project and fix every issue found.

CHECK AND FIX:
- Every admin route has permission middleware; test with each role. IDOR checks on orders, addresses, chat, carts.
- Mass assignment safe, all inputs validated with Form Requests, file uploads validated by MIME and extension, stored with random names, executable files blocked, uploads folder cannot run PHP (add .htaccess).
- Rich text sanitized on save and on output (HTMLPurifier or equivalent).
- Rate limiting on login, register, forgot password, chat, contact, newsletter, coupon check, search suggest.
- CSRF working for SPA, secure cookies (HttpOnly, Secure, SameSite=Lax), session regeneration on login.
- Security headers (CSP that allows Stripe js/checkout, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy).
- Secrets encrypted, never logged, never in API responses. APP_DEBUG=false in production. Custom error pages.
- Stripe webhook signature verified, idempotent.
- Admin: optional two-factor authentication (TOTP) for staff, forced for super admin if possible, login alerts, session timeout.
- Dependency audit (composer audit, npm audit).

Deliver a short report listing what you found and what you fixed.
```

---

### STEP 24: Testing and QA

```
TASK: Add automated tests and a manual QA checklist.

AUTOMATED (Laravel Pest/PHPUnit): auth and rate limits, permissions per role (matrix test), product create with variants, cart math, coupon rules, checkout creating an order, Stripe webhook idempotency (mock Stripe), stock race condition, review hiding of admin flag, settings secret masking, chat access control.
FRONTEND: unit tests for cart store and price formatting (Vitest). Optional Playwright smoke test: home -> product -> add to cart -> checkout (to Stripe redirect).

MANUAL QA CHECKLIST (generate as a markdown file): browsers (Chrome, Safari iOS, Firefox, Edge, Samsung Internet), devices (360, 390, 768, 1024, 1440 px), keyboard-only navigation, screen reader basics, slow 3G, empty states, error states, long product names, products with no image, 0 stock, big carts, expired coupon, failed payment, refund, role restrictions.

Fix every bug found and re-run.
```

---

### STEP 25: Deploy to Hostinger Business

```
TASK: Prepare and document the production deployment on Hostinger Business shared hosting. Give me exact, copy-paste steps.

PREPARE LOCALLY:
1. Frontend: npm run build (output goes to backend/public).
2. Backend: composer install --no-dev --optimize-autoloader.
3. Zip the backend (without node_modules, .git, tests, .env).

HOSTINGER SETUP (hPanel):
1. Choose PHP 8.2 or higher in PHP Configuration. Enable required extensions (mbstring, openssl, pdo_mysql, tokenizer, xml, ctype, json, fileinfo, gd or imagick, curl, bcmath, zip, intl). Raise upload_max_filesize and post_max_size (for product videos), memory_limit 256M+, max_execution_time 120.
2. Create the MySQL database and user in hPanel. Note host, name, user, password.
3. Enable free SSL and force HTTPS.
4. Create the business email accounts (e.g. orders@yourdomain.com) in hPanel Emails and note SMTP settings.
5. Upload with File Manager or Git/SSH. Recommended structure: put the Laravel app OUTSIDE public_html (for example /home/USER/shop-app), and put the contents of Laravel's public folder inside public_html. Edit public_html/index.php so the paths point to ../shop-app (vendor/autoload.php and bootstrap/app.php). The compiled Vue assets are in the same public folder.
6. Create the .env on the server with production values (APP_ENV=production, APP_DEBUG=false, APP_URL, DB_*, SESSION_DOMAIN, SANCTUM_STATEFUL_DOMAINS, QUEUE_CONNECTION=database, CACHE_STORE=file, SESSION_SECURE_COOKIE=true).
7. Via SSH: php artisan key:generate, php artisan migrate --force, php artisan db:seed --class=ProductionSeeder (creates roles, permissions, super admin, default settings only; no demo products), php artisan storage:link (or create the symlink manually / copy to public_html/storage if symlinks are blocked), php artisan config:cache, route:cache, view:cache.
8. Cron jobs in hPanel:
   * * * * * cd /home/USER/shop-app && php artisan schedule:run >> /dev/null 2>&1
   * * * * * cd /home/USER/shop-app && php artisan queue:work --stop-when-empty --max-time=55 >> /dev/null 2>&1
9. Stripe Dashboard: add the webhook endpoint https://yourdomain.com/api/stripe/webhook, select events (checkout.session.completed, checkout.session.expired, charge.refunded), copy the signing secret into Admin > Settings > Stripe.
10. Log in to /admin, change the super admin password, enable 2FA, fill in Site Settings, upload logo, set SMTP and send a test email, switch Stripe to live mode only after a full test-mode order.

ALSO PROVIDE:
- A production ProductionSeeder (no fake data).
- A deploy checklist and a rollback plan (backup DB + files before every update).
- An update procedure (build locally, upload changed files, php artisan migrate --force, clear caches).
- Daily database backup instructions (hPanel backups + a scheduled command that exports the DB).
- Uptime monitoring suggestion and a log rotation note.

ACCEPTANCE CHECK: Live site loads over HTTPS, a real test order in Stripe test mode completes, emails arrive, cron jobs run, and /admin is reachable only with valid login.
```

---

### STEP 26: Final audit prompt (run last)

```
TASK: Act as a strict reviewer. Audit the entire project against this brief and produce a report with PASS / FAIL / FIX for each item, then fix all FAIL items.

CHECK:
1. Design: light theme only, Inter only, Lucide only, brand color variable works, circle category tiles, pill buttons, no gradients blobs/glass/emoji icons/lorem ipsum, copy sounds human.
2. Responsive at 360 / 768 / 1024 / 1440 with no horizontal scroll anywhere, including admin.
3. Storefront: hero slider, circle categories, mega menu (desktop hover + mobile accordion), product hover video, accordion details (click to open/close), variants with price changes, reviews, wishlist, cart, checkout, account, popups, announcement bar, live chat, search suggestions.
4. Admin: roles (super admin full control, product manager cannot see finance/settings), product form with all advanced fields, variations, images by upload and URL, video, details sections, hero, popups, menu/mega menu builder, pages, reviews with custom names (hidden admin flag), site settings, logo upload, Stripe settings, SMTP settings, email templates, chat inbox, coupons, shipping, activity log.
5. Stripe: webhook is the source of truth, idempotent, refunds work.
6. Email: all notifications send via Hostinger SMTP through the queue.
7. Performance: Lighthouse mobile 90+ on key pages.
8. Security: the Step 23 list is complete.
9. Hostinger: nothing in the code needs WebSockets, Redis, a permanent worker or Node on the server.
10. Documentation: README, deploy guide, admin user guide (short, with screenshots placeholders), .env.example complete.

Deliver the report, then fix and re-test.
```

---

## 5. Prompts for when something goes wrong

**A bug**
```
Bug: [what you did] → [what happened] → [what you expected]. Error message / console output: [paste]. Find the root cause first, explain it in two sentences, then fix it. Do not change unrelated files. Add a test so it never comes back.
```

**Design looks off**
```
This page looks generic. Compare it to the design system. Fix spacing (use the 8px scale), type sizes, alignment, image ratios and contrast. Remove anything decorative that does not help the user. Keep one brand color, one shadow, one font. Show me before/after at 360px and 1440px.
```

**Slow page**
```
This page is slow. Measure first (Lighthouse and the Laravel query log). List the top 5 causes by impact, fix them in order, and re-measure. Do not add new packages unless necessary.
```

**Before adding a new idea**
```
I want to add [feature]. Before coding: tell me if it fits Hostinger Business shared hosting, which tables/routes/components change, the risk of breaking existing features, and the simplest stable version of it. Wait for my approval.
```

**Refactor safety**
```
Before changing [area], list every file that depends on it and write tests for the current behavior first. Then change it in small steps and run all tests after each step.
```

---

## 6. Tips to get better results from the AI

1. **One step at a time.** Giant prompts that ask for everything produce fragile code.
2. **Always test before the next step.** Fixing bugs early is cheap; fixing them after 10 steps is not.
3. **Give real content.** Put your real logo, categories, product photos and copy into the seed data as early as possible. Real content shows layout problems fast.
4. **Use Git.** Commit after every passing step with a clear message.
5. **Keep the Master Context handy.** Paste it again whenever the AI starts drifting (using dark mode, adding random packages, ignoring permissions).
6. **Ask for explanations of risky parts** (payments, permissions, uploads) and read them.
7. **Test with real Stripe test cards** and every role before going live.
8. **Back up before every production update.**

---

## 7. Quick reference

**Stripe test cards:** `4242 4242 4242 4242` (success) · `4000 0000 0000 9995` (declined) · any future expiry, any CVC.

**Default staff roles:** super_admin · product_manager · order_manager · support_agent · finance_manager · customer

**Cron jobs on Hostinger:**
```
* * * * * cd /home/USER/shop-app && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /home/USER/shop-app && php artisan queue:work --stop-when-empty --max-time=55 >> /dev/null 2>&1
```

**Hostinger SMTP (verify in your hPanel email settings):** host `smtp.hostinger.com` · port `465` (SSL) or `587` (TLS) · username = full email address.

**Pre-launch checklist**
- [ ] Super admin password changed, 2FA on
- [ ] APP_DEBUG=false, HTTPS forced
- [ ] Stripe live keys + live webhook set, one real small order tested and refunded
- [ ] SMTP test email received, order emails look right in Gmail and Outlook
- [ ] Cron jobs running (queue + schedule)
- [ ] Logo, favicon, hero images, categories, products and policy pages added
- [ ] Sitemap submitted to Google Search Console
- [ ] Backups confirmed
- [ ] Tested on a real phone

---

*Build in order, test every step, and keep the design simple. A fast, clean store beats a fancy slow one.*
