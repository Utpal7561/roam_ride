# Rome & Ride — Pick. Ride. Explore.

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://php.net)
[![Database](https://img.shields.io/badge/Database-MySQL%20%2F%20MariaDB-orange.svg)](https://mysql.com)
[![Architecture](https://img.shields.io/badge/Architecture-PHP%20MVC%20%2B%20REST%20API-green.svg)](#)
[![PWA](https://img.shields.io/badge/PWA-Installable%20Web%20App-yellow.svg)](#)
[![License](https://img.shields.io/badge/License-MIT-black.svg)](#)

> **"Your Ride. Your Rules. Your City."**  
> Pick your scooter. Book your ride. Explore without limits.

Rome & Ride is a complete, production-ready, mobile-first scooter and vehicle rental and booking platform designed for concerts, city exploration, tourism, and daily travel. It includes two seamlessly connected applications:

1. **Customer Website** — Vehicle browsing, booking wizard (Steps 1–7), payment options, customer accounts, and **My Rental Documents** with authenticated downloads.
2. **Admin Operations Dashboard** — Fleet management, bookings lifecycle, immutable payment ledger, vehicle handover/return checklists, document verification, and reporting with CSV export.

---

## 🎨 Brand Identity

- **Brand Name**: Rome & Ride (Roam & Ride)
- **Tagline**: **PICK. RIDE. EXPLORE.**
- **Color Palette**:
  - Primary: Bright Yellow (`#FFE500` / `#FACC15`)
  - Secondary: Deep Surface Black (`#09090B` / `#000000`)
  - Accent: Vibrant Crimson Red (`#EF4444`)
  - Neutral: White (`#FFFFFF`) and Zinc Gray (`#A1A1AA`)
- **Logo Asset**: `public/assets/images/logo.jpg`

---

## 🚀 Key Features

### 🛵 Customer Website & Booking
- **Mobile-First Responsive Interface**: Touch-friendly buttons (min 44px), sticky mobile booking navigation, and no horizontal scroll.
- **Hero & Search Widget**: Filter by pickup hub, dates/times, and vehicle category.
- **Dedicated Fleet Listing & Filters**: Search, category pills, price range slider, fuel type (Petrol/EV), transmission, and sorting.
- **Vehicle Details**: High-resolution gallery, specifications, helmet allowance, deposit information, and cancellation terms.
- **7-Step Booking Flow**:
  1. Vehicle Selection
  2. Rental Period & Location selection (with overlap collision prevention)
  3. Customer Info (Full Name **REQUIRED**, Phone **REQUIRED**, Email Optional)
  4. Driving License upload (Front & Back with JPG/PNG/PDF validation)
  5. Summary & Coupon Calculation (e.g. `ROMERIDE100`, `CONCERT20`, `WEEKEND15`)
  6. Payment Options: **Option A: Pay Advance (25%)** or **Option B: Pay Later reservation**
  7. Booking Confirmation & Receipt Screen
- **Customer Account Portal**:
  - Dashboard with live counters (Upcoming, Active, Pending Pay, Completed)
  - Active and Past Booking details
  - **My Rental Documents (Section 8)**: Confirmed Rental Agreements, RC, Insurance, and Pickup Instructions automatically delivered to the customer account with authenticated private file downloads.
  - Profile & password management.

### 🛡️ Admin Operations Dashboard (`/admin`)
- **Modern Responsive Dashboard**: Collapsible sidebar, mobile drawer, live metric summary cards, and 6-month monthly revenue charts.
- **Role-Based Access Control (RBAC)**: Super Admin, Admin, Booking Manager, Payment Manager, Vehicle Manager, and Staff.
- **Booking Management**: Lifecycle transitions (`pending_booking` ➔ `documents_approved` ➔ `booking_confirmed` ➔ `vehicle_assigned` ➔ `rental_active` ➔ `completed`).
- **Vehicle Assignment**: Prevents double-booking and validates availability before assignment.
- **Dedicated Payment Module (Section 13)**: Immutable transaction ledger, manual cash/UPI entry, partial payments, and refund processing.
- **Vehicle Handover & Return Checklists (Section 16)**: Inspect fuel %, odometer, scratches, helmets, customer signatures, and calculate return damage/cleaning adjustments.
- **Document Verification**: Approve or reject driving licenses with audit trail and customer notification.
- **Reports & Analytics**: Vehicle utilization and revenue reports with one-click **Excel-compatible CSV download**.
- **Customer Support Inbox**: Support ticket submission, triage, and reply.
- **Promotions Engine**: Configurable promo codes with date windows, customer limits, and minimum booking rules.
- **Audit Logging**: Comprehensive log of administrative actions, entity IDs, timestamps, and IP addresses.

### 🔒 Security & Performance
- **Token Bucket Rate Limiting (Section 25)**: Database-backed token bucket algorithm for login, registration, bookings, uploads, and APIs.
- **JWT Authentication (Section 24)**: Stateless HS256 JWT tokens with expiration and cookie fallback for private document viewing.
- **Private Document Storage**: Confidential files stored outside the public directory (`storage/private_documents/`) and accessed exclusively through authenticated streaming endpoints.
- **Apache `.htaccess` Hardening**: Blocks direct web access to `.env`, `.sql`, storage, logs, and hidden files.
- **PWA Ready**: Offline fallback (`offline.html`), `manifest.json`, and service worker caching static assets.

---

## 🛠️ Technology Stack

| Layer | Technologies |
| :--- | :--- |
| **Frontend** | HTML5, CSS3, Vanilla JavaScript, Tailwind CSS (via CDN), Bootstrap Icons |
| **Backend** | PHP 8.2+ (MVC Architecture, RESTful API, PDO, Password Hashing) |
| **Database** | MySQL 8.0+ / MariaDB 10.4+ |
| **Security** | JWT (HMAC-SHA256), Token Bucket Rate Limiting, Apache `.htaccess` |
| **PWA** | Service Worker, Manifest, Standalone Display, Offline Fallback |

---

## 📋 System Requirements

- **Web Server**: Apache 2.4+ (with `mod_rewrite` enabled) or PHP Built-in Server
- **PHP**: PHP 8.1 or higher (PHP 8.2+ recommended) with `pdo_mysql`, `fileinfo`, `mbstring`, `json` extensions enabled
- **Database**: MySQL 8.0+ or MariaDB 10.4+
- **Environment**: WAMP, XAMPP, LAMP, or Docker

---

## ⚙️ Installation & Setup (WAMP / XAMPP / Local)

### 1. Clone or Copy the Repository
Place the project into your local server root:
- **WAMP**: `C:\wamp64\www\roam_ride`
- **XAMPP**: `C:\xampp\htdocs\roam_ride`

### 2. Configure Environment (`.env`)
A default `.env` is included in the project root:
```env
APP_NAME="Rome & Ride"
APP_TAGLINE="PICK. RIDE. EXPLORE."
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/roam_ride

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rome_and_ride
DB_USERNAME=root
DB_PASSWORD=

JWT_SECRET=rome_and_ride_super_secure_jwt_secret_key_2026_xyz987
JWT_ACCESS_TTL=3600
JWT_REFRESH_TTL=604800

RATE_LIMIT_ENABLED=true
RATE_LIMIT_STORAGE=database
```

### 3. Run Database Migrations & Seed Data
Execute the migration script using your PHP CLI:
```bash
php database/migrate.php
```
*This automatically creates the `rome_and_ride` database, executes `schema.sql`, and seeds 10 vehicles, locations, promo codes, demo users, sample bookings, payments, and rental documents.*

Generate the initial vehicle vector graphics and sample documents:
```bash
php database/generate_assets.php
```

### 4. Run the Automated Test Suite
Verify your installation:
```bash
php tests/test_flow.php
```
*Expected: 14/14 Tests Passed (100%).*

---

## 🌐 Accessing the Application

### Option A: Using WAMP / Apache (Port 80)
Ensure Apache and MySQL services are started in WAMP. Then open your browser:
- **Customer Website**: `http://localhost/roam_ride/` (or `http://localhost/roam_ride/public/`)
- **Admin Dashboard**: `http://localhost/roam_ride/admin` (or `http://localhost/roam_ride/public/admin`)

### Option B: Using PHP Built-in Development Server
From the project root:
```bash
php -S localhost:8000 -t public
```
Then navigate to:
- **Customer Website**: `http://localhost:8000/`
- **Admin Dashboard**: `http://localhost:8000/admin`

---

## 🔑 Default Credentials (Development)

| Role | Email / Login | Phone | Password |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin@romeandride.com` | `+919876543210` | `admin123` |
| **Booking Manager** | `booking@romeandride.com` | `+919876543211` | `manager123` |
| **Payment Manager** | `payment@romeandride.com` | `+919876543212` | `manager123` |
| **Vehicle Manager** | `fleet@romeandride.com` | `+919876543213` | `manager123` |
| **Staff** | `staff@romeandride.com` | `+919876543214` | `manager123` |
| **Customer** | `rahul@gmail.com` | `+919876543220` | `customer123` |

---

## 🎟️ Active Demo Promo Codes

- `ROMERIDE100` — Flat ₹100 off on minimum booking of ₹499
- `CONCERT20` — 20% off for stadium and concert event rentals
- `WEEKEND15` — 15% off for weekend rentals (Friday – Sunday)
- `EXPLORE50` — Flat ₹50 instant discount

---

## 📡 REST API Reference

All API responses follow consistent JSON formatting:
```json
{
  "success": true,
  "message": "Operation successful",
  "data": {},
  "errors": null
}
```

### Authentication
- `POST /api/v1/auth/register` — Register customer (phone, name, email, password)
- `POST /api/v1/auth/login` — Sign in and receive JWT token
- `POST /api/v1/auth/logout` — Invalidate session
- `GET  /api/v1/auth/me` — Current authenticated user profile
- `PUT  /api/v1/auth/profile` — Update user profile

### Vehicles
- `GET /api/v1/vehicles` — Vehicle fleet listing with filters (category, price, search, fuel)
- `GET /api/v1/vehicles/{id}` — Vehicle details, specs, and gallery
- `GET /api/v1/vehicles/{id}/availability` — Check booking window availability
- `GET /api/v1/categories` — Fleet categories
- `GET /api/v1/locations` — Pickup and drop-off hubs

### Bookings
- `POST /api/v1/bookings` — Create a new 7-step booking (advance or pay-later)
- `GET  /api/v1/bookings` — Customer's active and past bookings
- `GET  /api/v1/bookings/{id}` — Full booking summary and timeline
- `POST /api/v1/bookings/{id}/cancel` — Cancel booking with refund request
- `POST /api/v1/bookings/apply-promo` — Validate and apply promo code

### Payments
- `POST /api/v1/payments/process` — Process customer online advance/balance payment
- `POST /api/v1/admin/payments/manual` — Record counter manual/cash/UPI payment
- `POST /api/v1/admin/payments/refund` — Record and process customer refund
- `GET  /api/v1/admin/payments/summary` — Financial overview metrics

### Documents
- `POST /api/v1/documents/upload` — Upload driving license (front/back)
- `GET  /api/v1/documents` — Customer's "My Rental Documents"
- `GET  /api/v1/documents/{id}/download` — Authenticated secure file download/view
- `POST /api/v1/admin/documents/{id}/verify` — Staff approve or reject license
- `POST /api/v1/admin/bookings/{id}/documents` — Staff attach rental agreement / RC to booking

### Admin & Operations
- `GET  /api/v1/admin/dashboard` — Live operations overview and counters
- `GET  /api/v1/admin/bookings` — Filterable bookings management
- `PUT  /api/v1/admin/bookings/{id}/status` — Advance booking workflow status
- `POST /api/v1/admin/bookings/{id}/assign-vehicle` — Assign or swap physical scooter
- `POST /api/v1/admin/vehicles` — Add new vehicle to fleet
- `PUT  /api/v1/admin/vehicles/{id}` — Edit vehicle specifications
- `DELETE /api/v1/admin/vehicles/{id}` — Decommission vehicle
- `POST /api/v1/admin/handover` — Record pickup or return inspection checklist
- `GET  /api/v1/admin/reports/bookings?export=csv` — Export bookings CSV
- `GET  /api/v1/admin/reports/revenue?export=csv` — Export revenue CSV
- `GET  /api/v1/admin/audit-logs` — Administrative audit trail

---

## 📁 Directory Structure

```text
roam_ride/
│
├── app/
│   ├── Controllers/         # Auth, Vehicle, Booking, Payment, Document, Admin, Report, Support, Setting
│   ├── Models/              # User, Vehicle, Booking, Payment, Document, Promotion, Handover, SupportTicket
│   ├── Services/            # RateLimitService (Token Bucket), etc.
│   ├── Middleware/          # AuthMiddleware, RoleMiddleware, RateLimitMiddleware
│   ├── Core/                # Router, Controller, Model, Database (PDO), Request, Response, JWT, Validator
│   └── Helpers/             # Security sanitization, Code generators, File validation
│
├── config/
│   ├── app.php              # App settings, paths, JWT keys, rate limit capacities
│   └── database.php         # PDO connection parameters
│
├── database/
│   ├── schema.sql           # 21 Normalized tables schema
│   ├── migrate.php          # Automated migration and seeding script
│   └── generate_assets.php  # Vector graphics and seed documents generator
│
├── storage/
│   ├── private_documents/   # Protected confidential customer licenses & agreements
│   └── logs/                # System application logs
│
├── routes/
│   ├── api.php              # Version 1 REST API routes
│   └── web.php              # Customer and Admin web routes
│
├── public/
│   ├── index.php            # Front Controller & bootstrapper
│   ├── manifest.json        # PWA configuration
│   ├── sw.js                # Service Worker
│   ├── offline.html         # Branded offline fallback screen
│   ├── views/
│   │   ├── index.php        # Customer website view
│   │   └── admin.php        # Admin dashboard view
│   ├── assets/
│   │   ├── css/custom.css   # Rome & Ride yellow/black theme
│   │   ├── js/              # api.js, auth.js, booking.js, app.js, admin.js
│   │   └── images/          # Logo & vehicle illustrations
│   └── uploads/             # Public vehicle photos
│
├── tests/
│   └── test_flow.php        # Automated verification test suite
│
├── .env                     # Sensitive environment configuration
├── .env.example             # Example configuration template
├── .htaccess                # Apache root rewrite and file protection
└── README.md                # Project master documentation
```

---

## 🔒 Security Checklist

- [x] Passwords hashed using `password_hash()` with `PASSWORD_BCRYPT`.
- [x] SQL Injection prevented using PDO prepared statements.
- [x] XSS protection via input sanitization and secure output escaping.
- [x] Token Bucket rate limiting applied to login, registration, bookings, and document uploads.
- [x] Sensitive customer documents stored in private non-public directories (`storage/private_documents/`).
- [x] Document download API enforces ownership check and role validation.
- [x] Direct browser access to `.env`, `.sql`, storage, and logs denied via `.htaccess`.
- [x] Financial ledger enforces immutable transactions (no silent updates).

---

## 📄 License
This project is proprietary and built for **Rome & Ride — Pick. Ride. Explore.**
All rights reserved.
