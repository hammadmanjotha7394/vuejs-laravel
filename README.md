# Boutique E-Commerce Web App

A production-ready ecommerce platform built with **Vue 3** and **Laravel API**, designed for high performance, modern aesthetic, and shared hosting compatibility (Hostinger Business plan).

---

## Tech Stack

- **Frontend:** Vue 3 (Composition API, `<script setup>`), Vite, Vue Router, Pinia, Tailwind CSS, Lucide icons, `@unhead/vue`.
- **Backend:** Laravel 12 (PHP 8.2+), Laravel Sanctum (cookie-based SPA auth), Spatie Laravel Permission, Stripe SDK (`stripe/stripe-php`), Intervention Image.
- **Database:** MySQL.
- **Email:** Hostinger SMTP via Laravel database queue.
- **Design Tokens:** Clean, light theme, Inter font (self-hosted WOFF2), 16px radius cards, pill buttons, `#2F5D50` brand color via CSS variable `--brand`.

---

## Directory Structure

```
Vuejs + Laravel/
├── shop/
│   ├── backend/          # Laravel 12 API backend
│   └── frontend/         # Vue 3 + Vite SPA
├── ecommerce-ai-build-prompts.md
└── README.md
```

---

## Prerequisites

- **PHP:** 8.2 or higher (with `pdo_mysql`, `mbstring`, `openssl`, `curl`, `gd`, `intl`, `zip`)
- **Composer:** 2.x
- **Node.js:** 18+ and **npm**
- **MySQL:** Running on port 3306 (e.g. via XAMPP)

---

## Getting Started Locally

### 1. Backend Setup (Laravel)

Open a terminal and run:

```bash
cd shop/backend

# Copy environment configuration
copy .env.example .env

# Generate application encryption key (if not already set)
php artisan key:generate

# Start the MySQL service (e.g. XAMPP MySQL) and create database:
# CREATE DATABASE ecommerce_db;

# Run database migrations
php artisan migrate

# Start the Laravel backend development server
php artisan serve
```

The backend API will be available at: **http://127.0.0.1:8000**
- Health Check: `GET http://127.0.0.1:8000/api/health`

---

### 2. Frontend Setup (Vue 3 + Vite)

Open a second terminal and run:

```bash
cd shop/frontend

# Install dependencies (already installed during Step 1)
npm install

# Start the Vite development server
npm run dev
```

The frontend will be available at: **http://localhost:5173**

> **Note on Local Proxy:** The Vite dev server proxies `/api` and `/sanctum` requests to `http://127.0.0.1:8000`, enabling seamless cookie-based SPA authentication without CORS complications.

---

### 3. Production Build

To compile the Vue frontend for production deployment:

```bash
cd shop/frontend
npm run build
```

This compiles optimized assets directly into `shop/backend/public/app`. Laravel's SPA fallback in `routes/web.php` serves the frontend automatically.

---

### 4. Code Quality & Formatting

- **Backend (Laravel Pint):**
  ```bash
  cd shop/backend
  php vendor/bin/pint
  ```

- **Frontend (ESLint & Prettier):**
  ```bash
  cd shop/frontend
  npx eslint src/
  npx prettier --write src/
  ```
