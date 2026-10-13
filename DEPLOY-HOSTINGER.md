# Hostinger Business Shared Hosting — Production Deployment & Operations Guide

This guide covers exact, copy-paste steps for deploying, updating, backing up, and maintaining the **ATELIER (Laravel 12 + Vue 3 SPA)** ecommerce platform on **Hostinger Business Shared Hosting**.

---

## 1. Local Build & Packaging

1. **Compile the Vue 3 Storefront & Admin SPA into `shop/backend/public/app`:**
   ```bash
   cd shop/frontend
   npm run build
   ```
2. **Optimize Composer Autoloader (Production):**
   ```bash
   cd ../backend
   composer install --no-dev --optimize-autoloader
   ```
3. **Upload Structure:**
   - All compiled frontend assets live directly inside `shop/backend/public/app/`.
   - Root `.htaccess` and `index.php` in `shop/backend/` automatically forward requests to `public/` on shared hosting when uploaded directly into `public_html`, **or** you can place `shop/backend` outside `public_html` (`/home/USER/shop-app`) and place `shop/backend/public/*` inside `public_html`.

---

## 2. Hostinger hPanel Setup

### A. PHP Configuration (`hPanel > Advanced > PHP Configuration`)
- **PHP Version:** PHP `8.2` or `8.3`
- **Required Extensions:** `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `gd`, `curl`, `bcmath`, `zip`, `intl`
- **PHP Options:**
  - `upload_max_filesize = 64M` (for product hover videos & high-res images)
  - `post_max_size = 64M`
  - `memory_limit = 256M`
  - `max_execution_time = 120`

### B. MySQL Database (`hPanel > Databases > Management`)
- Create your MySQL database and user in hPanel.
- Import `shop/backend/database/ecommerce_db.sql` via **phpMyAdmin** (or run `php artisan migrate --force` via SSH).

### C. Free SSL & HTTPS (`hPanel > Security > SSL`)
- Activate Free SSL for your domain and enable **Force HTTPS**.

### D. Hostinger Business Email (`hPanel > Emails`)
- Create your store mailbox (e.g., `orders@yourdomain.com`).
- **SMTP Host:** `smtp.hostinger.com`
- **SMTP Port:** `465` (`ssl`) or `587` (`tls`)
- **Username:** Full email address (`orders@yourdomain.com`)

---

## 3. Production `.env` Configuration

Ensure `shop/backend/.env` on the server contains:

```ini
APP_NAME="ATELIER"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_APP_KEY
APP_DEBUG=false
APP_URL=https://mistyrose-seal-239968.hostingersite.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u553267937_hammad3434
DB_USERNAME=u553267937_hammad3434
DB_PASSWORD="YOUR_DB_PASSWORD"

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SANCTUM_STATEFUL_DOMAINS=mistyrose-seal-239968.hostingersite.com

CACHE_STORE=file
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public
```

---

## 4. SSH / Terminal Post-Upload Commands

If using Hostinger SSH or after uploading updated files:

```bash
# 1. Fix file & directory permissions (prevents 403 Forbidden on static assets)
find public -type d -exec chmod 755 {} \;
find public -type f -exec chmod 644 {} \;
chmod -R 775 storage bootstrap/cache

# 2. Run database migrations
php artisan migrate --force

# 3. (Clean Production Install Only) Seed roles, permissions, super admin & templates without demo products:
php artisan db:seed --class=ProductionSeeder --force

# 4. Link public storage & warm caches
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 5. Hostinger Cron Jobs (`hPanel > Advanced > Cron Jobs`)

Add these two cron jobs running **every minute** (`* * * * *`):

```cron
* * * * * cd /home/u553267937/domains/YOUR_DOMAIN/public_html && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /home/u553267937/domains/YOUR_DOMAIN/public_html && /usr/bin/php artisan queue:work --stop-when-empty --max-time=55 >> /dev/null 2>&1
```
*(Note: `schedule:run` automatically runs the hourly 24h abandoned cart reminder command `shop:abandoned-carts`.)*

---

## 6. Stripe Webhook Configuration

1. Open **Stripe Dashboard > Developers > Webhooks**.
2. Click **Add endpoint** and enter:
   ```text
   https://YOUR_DOMAIN/api/stripe/webhook
   ```
3. Select events to listen to:
   - `checkout.session.completed`
   - `checkout.session.expired`
   - `charge.refunded`
4. Copy the **Signing secret (`whsec_...`)** and paste it into **Admin > Settings > Stripe Payment**.

---

## 7. Update, Backup & Rollback Procedure

### Before Every Update (Backup)
1. Log in to `/admin/dashboard` as Super Admin and click **Download `.sql` Backup** in the **Database Backup** card (or export via phpMyAdmin).
2. Download a copy of `storage/app/public/` if new media was uploaded.

### Deploying an Update
1. Run `npm run build` in `shop/frontend` locally.
2. Upload the updated `app/`, `routes/`, `database/migrations/`, and `public/app/` folders to Hostinger.
3. Run `php artisan migrate --force` and `php artisan optimize:clear`.

### Rollback Plan
1. Restore the previous code archive in File Manager.
2. Import the pre-update `.sql` backup file in phpMyAdmin.
3. Run `php artisan optimize:clear`.

---

## 8. Short Admin User Guide

- **Login URL:** `/login` (Default Super Admin: `admin@shop.test` / `Password123!` — change immediately after first login under Account/Users).
- **Quick Search (`Ctrl+K`):** Press `Ctrl+K` anywhere in `/admin` to jump directly to any product by name/SKU or order by order number.
- **Catalog Management:**
  - **Products (`/admin/products`):** Create simple or multi-variant products (`Size × Color`), upload WebP images or paste external image URLs, attach hover videos (`.mp4`/`.webm`), and build custom collapsible accordion sections (*Materials, Shipping, Size Guide*).
  - **Categories (`/admin/categories`):** Manage parent/subcategory trees and circle homepage category tiles.
  - **Brands (`/admin/brands`):** Manage brand directory and logos.
- **Navigation & Storefront Content:**
  - **Menus (`/admin/menus`):** Configure Header & Footer links and 4-column Mega Menus with promotional cards.
  - **Marketing (`/admin/marketing`):** Manage Hero Slides, Promotional Popups, Newsletter Subscribers (with CSV export), Contact Form Inbox, and 301 URL Redirects.
  - **Reviews (`/admin/reviews`):** Moderate customer reviews, post official Store Replies, bulk-import CSV reviews, or create curated reviews with custom author names and dates.
- **Operations & Settings:**
  - **Orders (`/admin/orders`):** Filter orders, print thermal/A4 packing invoices, update tracking numbers, and process full/partial Stripe refunds.
  - **Coupons (`/admin/coupons`):** Create percentage, fixed-amount, free-shipping, or *"Buy X items get Y% off"* (`min_qty`) promo codes.
  - **Email Templates (`/admin/email-templates`):** Customize all 16 Customer & Staff email templates with live HTML preview and one-click Test Send.
  - **Settings (`/admin/settings`):** Update store name, brand color (`--brand`), logo, free shipping threshold, Stripe keys, and Hostinger SMTP credentials.
