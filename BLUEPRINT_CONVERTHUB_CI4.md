# BLUEPRINT_CONVERTHUB_CI4.md

<!-- ============================================================
AGENT CONTEXT — BACA INI SEBELUM MULAI
Stack        : CodeIgniter 4.x | PHP 8.3 | MySQL 8.x | Alpine.js CDN | Chart.js CDN | Phosphor Icons CDN
Dev Env      : Laragon (localhost)
Deploy Target: Shared Hosting plug-and-play (NO build step, vendor/ di-commit ke repo)
Auth Model   : Session CI4 database handler
Rate Mode    : Manual by Admin ONLY (no external API)
Priority     : Security & Admin Full Control
Current Phase: 0 (belum mulai)
Blueprint Ver: 1.0

CI4 RULES (WAJIB — JANGAN DILANGGAR):
- MVC murni, Query Builder / Model CI4 — ZERO raw SQL
- Semua view output wajib esc() — ZERO raw echo
- Semua form POST wajib csrf_field()
- Validation pakai CI4 Validation class
- Session: database handler, tabel ci_sessions
- Exception CSRF: endpoint AJAX /quote/calculate → gunakan HMAC-SHA256 signed token

AGENT WORKFLOW:
1. Baca section yang relevan dengan phase saat ini
2. Implementasi file per file, berurutan
3. Jangan skip deliverable
4. Tulis laporan ✅ sebelum lanjut phase berikutnya
5. Jika ada ambiguitas, putuskan sendiri dengan best practice — jangan tanya
============================================================ -->

---

## DAFTAR ISI

1. [Ringkasan Produk & Scope](#1-ringkasan-produk--scope)
2. [Struktur Folder](#2-struktur-folder)
3. [Design System UI](#3-design-system-ui)
4. [Routing Lengkap](#4-routing-lengkap)
5. [Database Schema](#5-database-schema)
6. [Migrations CI4](#6-migrations-ci4)
7. [Seeders](#7-seeders)
8. [Modul Admin](#8-modul-admin)
9. [Sistem Rate Manual](#9-sistem-rate-manual)
10. [Security Model](#10-security-model)
11. [Deployment Shared Hosting](#11-deployment-shared-hosting)
12. [Phase 1 — Core Setup + Auth + RBAC](#phase-1--core-setup--auth--rbac)
13. [Phase 2 — Categories & Assets](#phase-2--categories--assets)
14. [Phase 3 — Pairs + Converter UI + AJAX Quote](#phase-3--pairs--converter-ui--ajax-quote)
15. [Phase 4 — Rate Manager + History + Audit](#phase-4--rate-manager--history--audit)
16. [Phase 5 — Orders + Payment + Notifikasi](#phase-5--orders--payment--notifikasi)
17. [Phase 6 — Settings + Maintenance + Security Hardening](#phase-6--settings--maintenance--security-hardening)
18. [Phase 7 — Testing + Optimasi + Deployment](#phase-7--testing--optimasi--deployment)

---

## 1. Ringkasan Produk & Scope

### Visi
ConvertHub adalah platform konversi aset berbasis web — exchange fiat, stablecoin, crypto, bank transfer, e-wallet, dan pulsa. **Rate dikelola 100% manual oleh admin**, tanpa API eksternal. Admin punya kontrol penuh atas rate, pair, order, dan konfigurasi sistem.

### Role & Akses

| Role   | Deskripsi                | Akses Utama                                                  |
|--------|--------------------------|--------------------------------------------------------------|
| public | Pengunjung tanpa akun    | Lihat converter, get quote (AJAX)                           |
| user   | Pengguna terdaftar       | Buat order, upload bukti, track status, notifikasi          |
| viewer | Staff read-only          | Dashboard admin, laporan, rate history (baca saja)          |
| staff  | Operator                 | Kelola order, update rate, lihat audit                      |
| admin  | Super administrator      | Semua fitur + settings + security + user management         |

### Scope Fitur

**Public:**
- Converter UI 3-panel: Kirim | Terima | Ringkasan
- Tabs kategori dinamis (dari DB): SEMUA, USD, COIN, BANK, PULSA, STABLE
- Kalkulasi realtime via AJAX (server-side, signed token, debounce 400ms)
- Quote tanpa login; order butuh login

**User:**
- Buat order dari quote (snapshot rate/fee immutable)
- Upload bukti bayar (manual payment)
- Track status order: `pending → paid → processing → completed / cancelled / failed`
- Notifikasi in-app

**Admin:**
- CRUD Categories, Assets, Pairs, Payment Methods
- Rate Manager: update manual + history chart (Chart.js) + audit
- Order management: review, status update, admin note, export CSV
- Blast notification ke semua user
- Settings grouped + maintenance mode
- Audit log semua perubahan penting

---

## 2. Struktur Folder

<!-- AGENT: Buat struktur ini di awal Phase 1. Folder kosong cukup dengan .gitkeep -->

```
converthub/
├── app/
│   ├── Config/
│   │   ├── App.php
│   │   ├── Autoload.php
│   │   ├── Database.php
│   │   ├── Filters.php              # daftarkan semua filter
│   │   ├── Permissions.php          # RBAC permission matrix
│   │   ├── Routes.php
│   │   ├── Security.php
│   │   └── Session.php
│   │
│   ├── Controllers/
│   │   ├── BaseController.php
│   │   ├── Home.php                 # GET / → converter publik
│   │   ├── Quote.php                # POST /quote/calculate (AJAX)
│   │   ├── Auth/
│   │   │   ├── LoginController.php
│   │   │   └── RegisterController.php
│   │   ├── User/
│   │   │   ├── OrderController.php
│   │   │   └── NotificationController.php
│   │   └── Admin/
│   │       ├── DashboardController.php
│   │       ├── CategoryController.php
│   │       ├── AssetController.php
│   │       ├── PairController.php
│   │       ├── RateController.php
│   │       ├── PaymentMethodController.php
│   │       ├── OrderController.php
│   │       ├── SettingController.php
│   │       ├── NotificationController.php
│   │       └── AuditController.php
│   │
│   ├── Filters/
│   │   ├── AuthFilter.php           # cek session login
│   │   ├── AdminFilter.php          # cek role admin area
│   │   ├── RBACFilter.php           # cek permission per action
│   │   ├── MaintenanceFilter.php    # global maintenance mode
│   │   ├── RateLimitFilter.php      # throttle login & quote
│   │   └── SecurityHeadersFilter.php
│   │
│   ├── Libraries/
│   │   ├── RBACLibrary.php
│   │   ├── RateCalculator.php
│   │   ├── AuditLogger.php
│   │   ├── NotificationService.php
│   │   └── UploadSecurity.php
│   │
│   ├── Models/
│   │   ├── UserModel.php
│   │   ├── LoginAttemptModel.php
│   │   ├── CategoryModel.php
│   │   ├── AssetModel.php
│   │   ├── PairModel.php
│   │   ├── PairRateHistoryModel.php
│   │   ├── PaymentMethodModel.php
│   │   ├── PairPaymentMethodModel.php
│   │   ├── OrderModel.php
│   │   ├── NotificationModel.php
│   │   ├── SettingModel.php
│   │   └── AuditLogModel.php
│   │
│   ├── Views/
│   │   ├── layouts/
│   │   │   ├── main.php             # public layout
│   │   │   ├── auth.php             # login/register
│   │   │   ├── user.php             # user dashboard
│   │   │   └── admin.php            # admin panel
│   │   ├── partials/
│   │   │   ├── nav_public.php
│   │   │   ├── nav_admin.php
│   │   │   ├── flash_messages.php
│   │   │   └── notifications_dropdown.php
│   │   ├── home/
│   │   │   └── index.php
│   │   ├── auth/
│   │   │   ├── login.php
│   │   │   └── register.php
│   │   ├── user/
│   │   │   ├── orders/
│   │   │   │   ├── index.php
│   │   │   │   └── detail.php
│   │   │   └── notifications.php
│   │   └── admin/
│   │       ├── dashboard/index.php
│   │       ├── categories/
│   │       │   ├── index.php
│   │       │   └── form.php
│   │       ├── assets/
│   │       │   ├── index.php
│   │       │   └── form.php
│   │       ├── pairs/
│   │       │   ├── index.php
│   │       │   └── form.php
│   │       ├── rates/
│   │       │   ├── index.php
│   │       │   └── history.php
│   │       ├── payment_methods/
│   │       │   ├── index.php
│   │       │   └── form.php
│   │       ├── orders/
│   │       │   ├── index.php
│   │       │   └── detail.php
│   │       ├── settings/
│   │       │   └── index.php
│   │       ├── notifications/
│   │       │   └── blast.php
│   │       └── audit/
│   │           └── index.php
│   │
│   └── Database/
│       ├── Migrations/              # satu file per tabel
│       └── Seeds/
│
├── public/
│   ├── index.php
│   ├── .htaccess
│   └── assets/
│       ├── css/
│       │   └── app.css
│       ├── js/
│       │   └── app.js
│       └── img/
│           └── uploads/
│               ├── logos/
│               │   └── .htaccess   # DENY PHP execution — wajib
│               └── proofs/
│                   └── .htaccess   # DENY PHP execution — wajib
│
├── writable/
│   ├── cache/
│   ├── logs/
│   └── session/
│
├── vendor/                          # committed — shared hosting no composer
├── .env                             # excluded dari git
├── .env.example
└── .gitignore
```

---

## 3. Design System UI

<!-- AGENT: Implementasi di public/assets/css/app.css. NO Bootstrap, NO Tailwind. Custom CSS murni. -->

### CSS Custom Properties

```css
:root {
  /* Background */
  --bg-base:         #0d0f14;
  --bg-surface:      #161a23;
  --bg-elevated:     #1e2433;
  --bg-hover:        #252c3a;

  /* Border */
  --border:          #2a3147;
  --border-subtle:   #1f2638;

  /* Text */
  --text-primary:    #e8eaf0;
  --text-secondary:  #8a93a8;
  --text-muted:      #4a5568;

  /* Brand */
  --accent:          #3b82f6;
  --accent-hover:    #2563eb;
  --accent-dim:      rgba(59,130,246,0.12);

  /* Status */
  --success:         #22c55e;
  --warning:         #f59e0b;
  --danger:          #ef4444;
  --info:            #38bdf8;

  /* Typography */
  --font-ui:   'Inter', 'Segoe UI', sans-serif;
  --font-mono: 'Fira Code', 'Courier New', monospace;

  /* Shape */
  --radius-sm:  4px;
  --radius-md:  8px;
  --radius-lg:  12px;
  --radius-xl:  16px;

  /* Shadow */
  --shadow-card:  0 2px 16px rgba(0,0,0,0.45);
  --shadow-modal: 0 8px 48px rgba(0,0,0,0.7);
}
```

### Komponen Reusable

| Class               | Fungsi                                            |
|---------------------|---------------------------------------------------|
| `.card`             | Surface container: bg-surface + border + shadow   |
| `.btn`              | Base button                                       |
| `.btn-primary`      | Accent color button                               |
| `.btn-danger`       | Red button untuk destructive action               |
| `.btn-ghost`        | Transparent button                                |
| `.badge`            | Inline status pill                                |
| `.badge-success/warning/danger` | Warna status order/pair             |
| `.mono`             | Font monospace — WAJIB untuk semua angka & kode   |
| `.form-group`       | Label + input wrapper                             |
| `.form-input`       | Dark-styled text input                            |
| `.form-select`      | Dark-styled select                                |
| `.tab-bar`          | Container filter tabs                             |
| `.tab-item.active`  | Tab aktif dengan accent underline                 |
| `.converter-panel`  | Grid 3-kolom: Kirim | Terima | Ringkasan          |
| `.table-admin`      | Dark table dengan striped rows                    |
| `.sidebar-nav`      | Admin sidebar dengan active state                 |
| `.alert-flash`      | Flash message dismissible (success/error/warning) |
| `.modal-overlay`    | Full-screen modal backdrop                        |
| `.stat-card`        | Dashboard metric card                             |

### CDN Dependencies

```html
<!-- Alpine.js -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.x.x/dist/chart.umd.min.js"></script>

<!-- Phosphor Icons -->
<script src="https://unpkg.com/@phosphor-icons/web@2.x.x"></script>
```

### Converter UI Layout

```
┌─────────────────────────────────────────────────────────────┐
│  [SEMUA] [USD] [COIN] [BANK] [PULSA] [STABLE]   ← Tab bar  │
├──────────────┬──────────────┬──────────────────────────────-┤
│  KIRIM/JUAL  │  TERIMA/BELI │  RINGKASAN                    │
│              │              │                               │
│  [Asset ▼]   │  [Asset ▼]   │  Rate   : 16,100  (mono)      │
│              │              │  Fee    : 0.5%    (mono)      │
│  [  Amount ] │  [  Result ] │  Min    : 10 USDT (mono)      │
│    (mono)    │    (mono)    │  Max    : 50,000  (mono)      │
│              │  ── atau ──  │  Terima : 160,497 (mono)      │
│              │  [Pilih dari │                               │
│              │   list pair] │  [⚠ Warning jika out of range]│
│              │              │  [Instruksi dari admin]       │
│              │              │                               │
│              │              │  [Buat Order →]               │
└──────────────┴──────────────┴───────────────────────────────┘
```

> **AGENT NOTE:** Semua nominal, rate, kode asset wajib menggunakan class `.mono` (font monospace).
> Kalkulasi triggered on input debounce 400ms via Alpine.js → POST ke `/quote/calculate`.

---

## 4. Routing Lengkap

<!-- AGENT: Semua route didaftarkan di app/Config/Routes.php -->

### Public Routes

```php
// app/Config/Routes.php

// Public
$routes->get('/', 'Home::index');
$routes->post('quote/calculate', 'Quote::calculate'); // HMAC signed token + rate limit

// Maintenance page
$routes->get('maintenance', 'Home::maintenance');
```

### Auth Routes

```php
$routes->group('', ['filter' => 'rate_limit_login'], function($routes) {
    $routes->get('login',    'Auth\LoginController::index');
    $routes->post('login',   'Auth\LoginController::process');
});
$routes->get('register',  'Auth\RegisterController::index');
$routes->post('register', 'Auth\RegisterController::process');
$routes->get('logout',    'Auth\LoginController::logout', ['filter' => 'auth']);
```

### User Routes

```php
$routes->group('user', ['filter' => 'auth:user'], function($routes) {
    // Orders
    $routes->get('orders',                    'User\OrderController::index');
    $routes->post('orders',                   'User\OrderController::create');
    $routes->get('orders/(:segment)',          'User\OrderController::detail/$1');
    $routes->post('orders/(:segment)/proof',  'User\OrderController::uploadProof/$1');
    $routes->post('orders/(:segment)/cancel', 'User\OrderController::cancel/$1');
    // Notifications
    $routes->get('notifications',             'User\NotificationController::index');
    $routes->post('notifications/read',       'User\NotificationController::markRead');
});
```

### Admin Routes

```php
$routes->group('admin', ['filter' => 'auth,admin'], function($routes) {

    $routes->get('dashboard', 'Admin\DashboardController::index');

    // Categories (admin only)
    $routes->group('categories', ['filter' => 'rbac:admin'], function($routes) {
        $routes->get('/',              'Admin\CategoryController::index');
        $routes->get('create',         'Admin\CategoryController::create');
        $routes->post('/',             'Admin\CategoryController::store');
        $routes->get('(:num)/edit',    'Admin\CategoryController::edit/$1');
        $routes->post('(:num)',        'Admin\CategoryController::update/$1');
        $routes->post('(:num)/delete', 'Admin\CategoryController::delete/$1');
    });

    // Assets (admin only)
    $routes->group('assets', ['filter' => 'rbac:admin'], function($routes) {
        $routes->get('/',              'Admin\AssetController::index');
        $routes->get('create',         'Admin\AssetController::create');
        $routes->post('/',             'Admin\AssetController::store');
        $routes->get('(:num)/edit',    'Admin\AssetController::edit/$1');
        $routes->post('(:num)',        'Admin\AssetController::update/$1');
        $routes->post('(:num)/delete', 'Admin\AssetController::delete/$1');
    });

    // Pairs (admin only)
    $routes->group('pairs', ['filter' => 'rbac:admin'], function($routes) {
        $routes->get('/',              'Admin\PairController::index');
        $routes->get('create',         'Admin\PairController::create');
        $routes->post('/',             'Admin\PairController::store');
        $routes->get('(:num)/edit',    'Admin\PairController::edit/$1');
        $routes->post('(:num)',        'Admin\PairController::update/$1');
        $routes->post('(:num)/delete', 'Admin\PairController::delete/$1');
    });

    // Rates (staff + admin)
    $routes->group('rates', ['filter' => 'rbac:staff'], function($routes) {
        $routes->get('/',                    'Admin\RateController::index');
        $routes->post('(:num)/update',       'Admin\RateController::update/$1');
        $routes->get('(:num)/history',       'Admin\RateController::history/$1');
        $routes->get('(:num)/chart-data',    'Admin\RateController::chartData/$1');
    });

    // Payment Methods (admin only)
    $routes->group('payment-methods', ['filter' => 'rbac:admin'], function($routes) {
        $routes->get('/',              'Admin\PaymentMethodController::index');
        $routes->get('create',         'Admin\PaymentMethodController::create');
        $routes->post('/',             'Admin\PaymentMethodController::store');
        $routes->get('(:num)/edit',    'Admin\PaymentMethodController::edit/$1');
        $routes->post('(:num)',        'Admin\PaymentMethodController::update/$1');
        $routes->post('(:num)/delete', 'Admin\PaymentMethodController::delete/$1');
        $routes->post('(:num)/pairs',  'Admin\PaymentMethodController::updatePairs/$1');
    });

    // Orders (staff + admin)
    $routes->group('orders', ['filter' => 'rbac:staff'], function($routes) {
        $routes->get('/',                    'Admin\OrderController::index');
        $routes->get('export-csv',           'Admin\OrderController::exportCsv');
        $routes->get('(:segment)',           'Admin\OrderController::detail/$1');
        $routes->post('(:segment)/status',   'Admin\OrderController::updateStatus/$1');
    });

    // Settings (admin only)
    $routes->group('settings', ['filter' => 'rbac:admin'], function($routes) {
        $routes->get('/',               'Admin\SettingController::index');
        $routes->post('/',              'Admin\SettingController::update');
        $routes->post('maintenance',    'Admin\SettingController::toggleMaintenance');
    });

    // Notifications blast (admin only)
    $routes->group('notifications', ['filter' => 'rbac:admin'], function($routes) {
        $routes->get('blast',  'Admin\NotificationController::blast');
        $routes->post('blast', 'Admin\NotificationController::sendBlast');
    });

    // Audit (viewer+)
    $routes->get('audit', 'Admin\AuditController::index', ['filter' => 'rbac:viewer']);
});
```

---

## 5. Database Schema

<!-- AGENT: Gunakan ini sebagai referensi. Implementasi via CI4 Migrations (Section 6). JANGAN jalankan SQL ini langsung. -->

### `ci_sessions`

```sql
CREATE TABLE `ci_sessions` (
  `id`         varchar(128)      NOT NULL,
  `ip_address` varchar(45)       NOT NULL,
  `timestamp`  int(10) unsigned  NOT NULL DEFAULT 0,
  `data`       blob              NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `users`

```sql
CREATE TABLE `users` (
  `id`                 int unsigned    NOT NULL AUTO_INCREMENT,
  `name`               varchar(100)    NOT NULL,
  `email`              varchar(191)    NOT NULL,
  `password`           varchar(255)    NOT NULL,           -- argon2id
  `role`               varchar(20)     NOT NULL DEFAULT 'user', -- admin|staff|viewer|user
  `is_active`          tinyint(1)      NOT NULL DEFAULT 1,
  `email_verified_at`  datetime        DEFAULT NULL,
  `last_login_at`      datetime        DEFAULT NULL,
  `last_login_ip`      varchar(45)     DEFAULT NULL,
  `two_factor_secret`  varchar(255)    DEFAULT NULL,
  `two_factor_enabled` tinyint(1)      NOT NULL DEFAULT 0,
  `deleted_at`         datetime        DEFAULT NULL,       -- soft delete
  `created_at`         datetime        DEFAULT NULL,
  `updated_at`         datetime        DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### `login_attempts`

```sql
CREATE TABLE `login_attempts` (
  `id`          int unsigned  NOT NULL AUTO_INCREMENT,
  `email`       varchar(191)  NOT NULL,
  `ip_address`  varchar(45)   NOT NULL,
  `user_agent`  varchar(255)  DEFAULT NULL,
  `is_success`  tinyint(1)    NOT NULL DEFAULT 0,
  `fail_reason` varchar(100)  DEFAULT NULL,
  `created_at`  datetime      DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_la_email`   (`email`),
  KEY `idx_la_ip`      (`ip_address`),
  KEY `idx_la_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `categories`

```sql
CREATE TABLE `categories` (
  `id`         int unsigned  NOT NULL AUTO_INCREMENT,
  `name`       varchar(100)  NOT NULL,
  `slug`       varchar(100)  NOT NULL,
  `sort_order` int           NOT NULL DEFAULT 0,
  `is_active`  tinyint(1)    NOT NULL DEFAULT 1,
  `created_at` datetime      DEFAULT NULL,
  `updated_at` datetime      DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `assets`

```sql
CREATE TABLE `assets` (
  `id`            int unsigned  NOT NULL AUTO_INCREMENT,
  `category_id`   int unsigned  NOT NULL,
  `code`          varchar(50)   NOT NULL,                  -- e.g. IDR, USDT, BCA_IDR
  `name`          varchar(150)  NOT NULL,
  `symbol`        varchar(20)   DEFAULT NULL,
  `decimals`      tinyint       NOT NULL DEFAULT 2,        -- 0–18
  `logo_path`     varchar(255)  DEFAULT NULL,
  `status`        varchar(20)   NOT NULL DEFAULT 'active', -- active|inactive
  `sort_order`    int           NOT NULL DEFAULT 0,
  `metadata_json` json          DEFAULT NULL,
  `created_at`    datetime      DEFAULT NULL,
  `updated_at`    datetime      DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_assets_code` (`code`),
  KEY `idx_assets_category` (`category_id`),
  KEY `idx_assets_status`   (`status`),
  CONSTRAINT `fk_assets_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `pairs`

```sql
CREATE TABLE `pairs` (
  `id`                    int unsigned    NOT NULL AUTO_INCREMENT,
  `from_asset_id`         int unsigned    NOT NULL,
  `to_asset_id`           int unsigned    NOT NULL,
  `status`                varchar(20)     NOT NULL DEFAULT 'active',
  `sort_order`            int             NOT NULL DEFAULT 0,
  `min_amount`            decimal(30,8)   NOT NULL DEFAULT 0.00000000,
  `max_amount`            decimal(30,8)   NOT NULL DEFAULT 0.00000000,
  `fee_type`              varchar(10)     NOT NULL DEFAULT 'fixed',    -- fixed|percent
  `fee_value`             decimal(20,8)   NOT NULL DEFAULT 0.00000000,
  `spread_type`           varchar(10)     NOT NULL DEFAULT 'none',     -- fixed|percent|none
  `spread_value`          decimal(20,8)   NOT NULL DEFAULT 0.00000000,
  `rounding_mode`         varchar(10)     NOT NULL DEFAULT 'floor',    -- ceil|floor|round
  `rounding_precision`    tinyint         NOT NULL DEFAULT 2,
  `rate_mode`             varchar(20)     NOT NULL DEFAULT 'manual',
  `last_rate`             decimal(30,12)  NOT NULL DEFAULT 0.000000000000,
  `last_rate_updated_at`  datetime        DEFAULT NULL,
  `last_rate_updated_by`  int unsigned    DEFAULT NULL,
  `notes`                 text            DEFAULT NULL,
  `created_at`            datetime        DEFAULT NULL,
  `updated_at`            datetime        DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pairs_from_to` (`from_asset_id`, `to_asset_id`),
  KEY `idx_pairs_status`  (`status`),
  CONSTRAINT `fk_pairs_from`    FOREIGN KEY (`from_asset_id`)        REFERENCES `assets` (`id`),
  CONSTRAINT `fk_pairs_to`      FOREIGN KEY (`to_asset_id`)          REFERENCES `assets` (`id`),
  CONSTRAINT `fk_pairs_updater` FOREIGN KEY (`last_rate_updated_by`) REFERENCES `users`  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `pair_rate_history`

```sql
CREATE TABLE `pair_rate_history` (
  `id`            int unsigned    NOT NULL AUTO_INCREMENT,
  `pair_id`       int unsigned    NOT NULL,
  `rate`          decimal(30,12)  NOT NULL,
  `changed_by`    int unsigned    NOT NULL,
  `change_reason` varchar(500)    DEFAULT NULL,
  `created_at`    datetime        DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_prh_pair`    (`pair_id`),
  KEY `idx_prh_changer` (`changed_by`),
  KEY `idx_prh_created` (`created_at`),
  CONSTRAINT `fk_prh_pair` FOREIGN KEY (`pair_id`)    REFERENCES `pairs` (`id`),
  CONSTRAINT `fk_prh_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `payment_methods`

```sql
CREATE TABLE `payment_methods` (
  `id`           int unsigned  NOT NULL AUTO_INCREMENT,
  `name`         varchar(150)  NOT NULL,
  `slug`         varchar(150)  NOT NULL,
  `type`         varchar(50)   NOT NULL DEFAULT 'manual',  -- manual|gateway
  `logo_path`    varchar(255)  DEFAULT NULL,
  `instructions` text          DEFAULT NULL,
  `status`       varchar(20)   NOT NULL DEFAULT 'active',
  `sort_order`   int           NOT NULL DEFAULT 0,
  `created_at`   datetime      DEFAULT NULL,
  `updated_at`   datetime      DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pm_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `pair_payment_methods`

```sql
CREATE TABLE `pair_payment_methods` (
  `id`                int unsigned  NOT NULL AUTO_INCREMENT,
  `pair_id`           int unsigned  NOT NULL,
  `payment_method_id` int unsigned  NOT NULL,
  `rules_json`        json          DEFAULT NULL,  -- limit/text per pair
  `is_active`         tinyint(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ppm_pair_pm` (`pair_id`, `payment_method_id`),
  CONSTRAINT `fk_ppm_pair` FOREIGN KEY (`pair_id`)           REFERENCES `pairs`           (`id`),
  CONSTRAINT `fk_ppm_pm`   FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `orders`

```sql
CREATE TABLE `orders` (
  `id`                  int unsigned    NOT NULL AUTO_INCREMENT,
  `code`                varchar(30)     NOT NULL,             -- CH-YYYYMMDD-XXXXXX
  `user_id`             int unsigned    NOT NULL,
  `pair_id`             int unsigned    NOT NULL,
  `payment_method_id`   int unsigned    DEFAULT NULL,
  -- SNAPSHOT immutable saat order dibuat
  `snap_rate`           decimal(30,12)  NOT NULL,
  `snap_fee_type`       varchar(10)     NOT NULL,
  `snap_fee_value`      decimal(20,8)   NOT NULL,
  `snap_spread_type`    varchar(10)     NOT NULL,
  `snap_spread_value`   decimal(20,8)   NOT NULL,
  `snap_min_amount`     decimal(30,8)   NOT NULL,
  `snap_max_amount`     decimal(30,8)   NOT NULL,
  -- Hasil kalkulasi
  `amount_sent`         decimal(30,8)   NOT NULL,
  `amount_received`     decimal(30,8)   NOT NULL,
  -- Status & tracking
  `status`              varchar(30)     NOT NULL DEFAULT 'pending',
  `proof_path`          varchar(255)    DEFAULT NULL,
  `proof_uploaded_at`   datetime        DEFAULT NULL,
  `admin_note`          text            DEFAULT NULL,
  `cancelled_reason`    varchar(500)    DEFAULT NULL,
  `expires_at`          datetime        DEFAULT NULL,
  `completed_at`        datetime        DEFAULT NULL,
  `created_at`          datetime        DEFAULT NULL,
  `updated_at`          datetime        DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_orders_code`  (`code`),
  KEY `idx_orders_user`    (`user_id`),
  KEY `idx_orders_pair`    (`pair_id`),
  KEY `idx_orders_status`  (`status`),
  KEY `idx_orders_created` (`created_at`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_orders_pair` FOREIGN KEY (`pair_id`) REFERENCES `pairs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `notifications`

```sql
CREATE TABLE `notifications` (
  `id`         int unsigned  NOT NULL AUTO_INCREMENT,
  `user_id`    int unsigned  DEFAULT NULL,  -- NULL = broadcast (tidak dipakai, blast insert per user)
  `type`       varchar(50)   NOT NULL,      -- order_status|rate_update|blast|system
  `title`      varchar(255)  NOT NULL,
  `body`       text          NOT NULL,
  `data_json`  json          DEFAULT NULL,
  `is_read`    tinyint(1)    NOT NULL DEFAULT 0,
  `read_at`    datetime      DEFAULT NULL,
  `created_at` datetime      DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user`    (`user_id`),
  KEY `idx_notif_is_read` (`is_read`),
  KEY `idx_notif_created` (`created_at`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `settings`

```sql
CREATE TABLE `settings` (
  `id`          int unsigned  NOT NULL AUTO_INCREMENT,
  `group`       varchar(50)   NOT NULL,   -- general|smtp|security|converter|system
  `key`         varchar(100)  NOT NULL,
  `value`       text          DEFAULT NULL,
  `type`        varchar(20)   NOT NULL DEFAULT 'string', -- string|bool|json|int
  `label`       varchar(200)  DEFAULT NULL,
  `description` varchar(500)  DEFAULT NULL,
  `created_at`  datetime      DEFAULT NULL,
  `updated_at`  datetime      DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_group_key` (`group`, `key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `audit_logs`

```sql
CREATE TABLE `audit_logs` (
  `id`         int unsigned  NOT NULL AUTO_INCREMENT,
  `user_id`    int unsigned  DEFAULT NULL,
  `action`     varchar(100)  NOT NULL,   -- rate_update|pair_edit|setting_change|order_status|etc
  `model`      varchar(100)  DEFAULT NULL,
  `model_id`   int unsigned  DEFAULT NULL,
  `old_values` json          DEFAULT NULL,
  `new_values` json          DEFAULT NULL,
  `ip_address` varchar(45)   DEFAULT NULL,
  `user_agent` varchar(255)  DEFAULT NULL,
  `created_at` datetime      DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_al_user`    (`user_id`),
  KEY `idx_al_action`  (`action`),
  KEY `idx_al_model`   (`model`, `model_id`),
  KEY `idx_al_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 6. Migrations CI4

<!-- AGENT: Buat satu file migration per tabel. Jalankan sesuai urutan dependency. -->

### Urutan Migration

```
001_create_ci_sessions.php
002_create_users.php
003_create_login_attempts.php
004_create_settings.php
005_create_audit_logs.php
006_create_categories.php
007_create_assets.php
008_create_pairs.php
009_create_pair_rate_history.php
010_create_payment_methods.php
011_create_pair_payment_methods.php
012_create_orders.php
013_create_notifications.php
```

### Contoh Migration: `008_create_pairs.php`

```php
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePairs extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'from_asset_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'to_asset_id'   => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'sort_order'    => ['type' => 'INT', 'default' => 0],
            'min_amount'    => ['type' => 'DECIMAL', 'constraint' => '30,8', 'default' => '0.00000000'],
            'max_amount'    => ['type' => 'DECIMAL', 'constraint' => '30,8', 'default' => '0.00000000'],
            'fee_type'      => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'fixed'],
            'fee_value'     => ['type' => 'DECIMAL', 'constraint' => '20,8', 'default' => '0.00000000'],
            'spread_type'   => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'none'],
            'spread_value'  => ['type' => 'DECIMAL', 'constraint' => '20,8', 'default' => '0.00000000'],
            'rounding_mode'      => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'floor'],
            'rounding_precision' => ['type' => 'TINYINT', 'default' => 2],
            'rate_mode'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'manual'],
            'last_rate'     => ['type' => 'DECIMAL', 'constraint' => '30,12', 'default' => '0.000000000000'],
            'last_rate_updated_at' => ['type' => 'DATETIME', 'null' => true],
            'last_rate_updated_by' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'notes'         => ['type' => 'TEXT', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['from_asset_id', 'to_asset_id'], 'uq_pairs_from_to');
        $this->forge->addKey('status', false, false, 'idx_pairs_status');

        $this->forge->createTable('pairs');

        // Foreign Keys
        $this->db->query('ALTER TABLE `pairs`
            ADD CONSTRAINT `fk_pairs_from`
            FOREIGN KEY (`from_asset_id`) REFERENCES `assets` (`id`)');

        $this->db->query('ALTER TABLE `pairs`
            ADD CONSTRAINT `fk_pairs_to`
            FOREIGN KEY (`to_asset_id`) REFERENCES `assets` (`id`)');

        $this->db->query('ALTER TABLE `pairs`
            ADD CONSTRAINT `fk_pairs_updater`
            FOREIGN KEY (`last_rate_updated_by`) REFERENCES `users` (`id`)');
    }

    public function down(): void
    {
        $this->forge->dropTable('pairs', true);
    }
}
```

> **AGENT NOTE:** Pola yang sama berlaku untuk semua tabel. FK ditambahkan via `$this->db->query('ALTER TABLE...')` karena CI4 Forge tidak support FK secara langsung di semua versi. Selalu ikuti urutan migration agar FK tidak error.

---

## 7. Seeders

<!-- AGENT: Implementasi di app/Database/Seeds/. Panggil semua dari DatabaseSeeder.php -->

### `DatabaseSeeder.php`

```php
<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call('AdminSeeder');
        $this->call('CategorySeeder');
        $this->call('AssetSeeder');
        $this->call('PairSeeder');
        $this->call('PaymentMethodSeeder');
        $this->call('SettingSeeder');
    }
}
```

### `AdminSeeder.php`

```php
<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->db->table('users')->insert([
            'name'       => 'Super Admin',
            'email'      => 'admin@converthub.com',
            'password'   => password_hash('Admin123!', PASSWORD_ARGON2ID),
            'role'       => 'admin',
            'is_active'  => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
```

### `CategorySeeder.php`

```php
<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['name' => 'Semua', 'slug' => 'semua', 'sort_order' => 0, 'is_active' => 1],
            ['name' => 'USD',   'slug' => 'usd',   'sort_order' => 1, 'is_active' => 1],
            ['name' => 'Coin',  'slug' => 'coin',  'sort_order' => 2, 'is_active' => 1],
            ['name' => 'Bank',  'slug' => 'bank',  'sort_order' => 3, 'is_active' => 1],
            ['name' => 'Pulsa', 'slug' => 'pulsa', 'sort_order' => 4, 'is_active' => 1],
            ['name' => 'Stable','slug' => 'stable','sort_order' => 5, 'is_active' => 1],
        ];
        foreach ($data as &$row) {
            $row['created_at'] = $row['updated_at'] = date('Y-m-d H:i:s');
        }
        $this->db->table('categories')->insertBatch($data);
    }
}
```

### `AssetSeeder.php`

```php
// Sample data — sesuaikan category_id dengan hasil CategorySeeder
$assets = [
    // category_id 4 = Bank, 2 = USD, 6 = Stable, 3 = Coin, 5 = Pulsa
    ['category_id'=>4,'code'=>'IDR',             'name'=>'Indonesian Rupiah',   'symbol'=>'Rp', 'decimals'=>0],
    ['category_id'=>2,'code'=>'USD',             'name'=>'US Dollar',           'symbol'=>'$',  'decimals'=>2],
    ['category_id'=>6,'code'=>'USDT',            'name'=>'Tether USD',          'symbol'=>'₮',  'decimals'=>2],
    ['category_id'=>3,'code'=>'BTC',             'name'=>'Bitcoin',             'symbol'=>'₿',  'decimals'=>8],
    ['category_id'=>4,'code'=>'BCA_IDR',         'name'=>'Bank BCA',            'symbol'=>'BCA','decimals'=>0],
    ['category_id'=>2,'code'=>'PAYPAL_USD',      'name'=>'PayPal USD',          'symbol'=>'PP', 'decimals'=>2],
    ['category_id'=>5,'code'=>'TELKOMSEL_PULSA', 'name'=>'Pulsa Telkomsel',     'symbol'=>'Tsel','decimals'=>0],
];
```

### `PairSeeder.php`

```php
// Sample pairs untuk demo
$pairs = [
    [
        'from_asset_id' => 3, // USDT
        'to_asset_id'   => 1, // IDR
        'status'        => 'active',
        'min_amount'    => 10,
        'max_amount'    => 50000,
        'fee_type'      => 'percent',
        'fee_value'     => 0.5,
        'spread_type'   => 'none',
        'spread_value'  => 0,
        'rounding_mode' => 'floor',
        'rounding_precision' => 0,
        'last_rate'     => 16100,
        'notes'         => 'Transfer ke rekening BCA/Mandiri. Proses 1–3 jam kerja.',
    ],
    [
        'from_asset_id' => 2, // USD
        'to_asset_id'   => 1, // IDR
        'status'        => 'active',
        'min_amount'    => 1,
        'max_amount'    => 10000,
        'fee_type'      => 'fixed',
        'fee_value'     => 5000,
        'spread_type'   => 'none',
        'spread_value'  => 0,
        'rounding_mode' => 'floor',
        'rounding_precision' => 0,
        'last_rate'     => 16050,
        'notes'         => 'USD via PayPal ke IDR. Proses 1–6 jam kerja.',
    ],
    [
        'from_asset_id' => 4, // BTC
        'to_asset_id'   => 1, // IDR
        'status'        => 'active',
        'min_amount'    => 0.0001,
        'max_amount'    => 1,
        'fee_type'      => 'percent',
        'fee_value'     => 1,
        'spread_type'   => 'none',
        'spread_value'  => 0,
        'rounding_mode' => 'floor',
        'rounding_precision' => 0,
        'last_rate'     => 980000000,
        'notes'         => 'BTC ke IDR. Rate update setiap 30 menit. Proses 1–4 jam.',
    ],
];
```

### `SettingSeeder.php`

```php
$settings = [
    // General
    ['group'=>'general','key'=>'site_name',    'value'=>'ConvertHub',      'type'=>'string', 'label'=>'Nama Situs'],
    ['group'=>'general','key'=>'site_url',     'value'=>'',                'type'=>'string', 'label'=>'URL Situs'],
    ['group'=>'general','key'=>'timezone',     'value'=>'Asia/Jakarta',    'type'=>'string', 'label'=>'Timezone'],
    // SMTP
    ['group'=>'smtp','key'=>'host',            'value'=>'smtp.mailtrap.io','type'=>'string', 'label'=>'SMTP Host'],
    ['group'=>'smtp','key'=>'port',            'value'=>'587',             'type'=>'int',    'label'=>'SMTP Port'],
    ['group'=>'smtp','key'=>'username',        'value'=>'',                'type'=>'string', 'label'=>'Username'],
    ['group'=>'smtp','key'=>'password',        'value'=>'',                'type'=>'string', 'label'=>'Password'],
    ['group'=>'smtp','key'=>'from_email',      'value'=>'',                'type'=>'string', 'label'=>'From Email'],
    ['group'=>'smtp','key'=>'encryption',      'value'=>'tls',             'type'=>'string', 'label'=>'Enkripsi'],
    // Security
    ['group'=>'security','key'=>'max_login_attempts','value'=>'5',         'type'=>'int',    'label'=>'Maks Percobaan Login'],
    ['group'=>'security','key'=>'lockout_minutes',   'value'=>'15',        'type'=>'int',    'label'=>'Durasi Lockout (menit)'],
    ['group'=>'security','key'=>'session_timeout',   'value'=>'120',       'type'=>'int',    'label'=>'Timeout Sesi (menit)'],
    ['group'=>'security','key'=>'two_factor_admin',  'value'=>'0',         'type'=>'bool',   'label'=>'Wajib 2FA untuk Admin'],
    // Converter
    ['group'=>'converter','key'=>'quote_token_secret','value'=>bin2hex(random_bytes(32)),'type'=>'string','label'=>'Quote Token Secret'],
    ['group'=>'converter','key'=>'quote_token_expiry','value'=>'300',      'type'=>'int',    'label'=>'Expiry Quote Token (detik)'],
    ['group'=>'converter','key'=>'show_rate_indicator','value'=>'1',       'type'=>'bool',   'label'=>'Tampilkan Indikator Perubahan Rate'],
    // System
    ['group'=>'system','key'=>'maintenance_mode',    'value'=>'0',         'type'=>'bool',   'label'=>'Mode Maintenance'],
    ['group'=>'system','key'=>'maintenance_message', 'value'=>'Sistem sedang dalam pemeliharaan. Coba lagi nanti.','type'=>'string','label'=>'Pesan Maintenance'],
];
```

---

## 8. Modul Admin

### RateCalculator Logic

```php
<?php
// app/Libraries/RateCalculator.php

namespace App\Libraries;

class RateCalculator
{
    /**
     * Hitung estimasi amount yang diterima.
     *
     * @param  float  $amountSent
     * @param  array  $pair  — row dari tabel pairs
     * @return array  ['gross', 'fee', 'spread', 'net', 'error']
     */
    public static function calculate(float $amountSent, array $pair): array
    {
        // Validasi min/max
        if ($amountSent < $pair['min_amount']) {
            return ['error' => 'Jumlah minimum: ' . $pair['min_amount']];
        }
        if ($pair['max_amount'] > 0 && $amountSent > $pair['max_amount']) {
            return ['error' => 'Jumlah maksimum: ' . $pair['max_amount']];
        }

        $rate  = (float) $pair['last_rate'];
        $gross = $amountSent * $rate;

        // Fee
        $fee = match($pair['fee_type']) {
            'fixed'   => (float) $pair['fee_value'],
            'percent' => $amountSent * ((float) $pair['fee_value'] / 100),
            default   => 0.0,
        };

        // Spread
        $spread = match($pair['spread_type']) {
            'fixed'   => (float) $pair['spread_value'],
            'percent' => $amountSent * ((float) $pair['spread_value'] / 100),
            default   => 0.0,
        };

        $net = $gross - $fee - $spread;

        // Rounding
        $precision = (int) $pair['rounding_precision'];
        $net = match($pair['rounding_mode']) {
            'ceil'  => ceil($net  * (10 ** $precision)) / (10 ** $precision),
            'floor' => floor($net * (10 ** $precision)) / (10 ** $precision),
            default => round($net, $precision),
        };

        return [
            'gross'  => $gross,
            'fee'    => $fee,
            'spread' => $spread,
            'net'    => max(0, $net),
            'error'  => null,
        ];
    }
}
```

### AuditLogger Usage Pattern

```php
// Di setiap controller admin yang melakukan perubahan data:

use App\Libraries\AuditLogger;

// Sebelum update
$old = $pairModel->find($id);

// Lakukan update...
$pairModel->update($id, $data);

// Log audit
AuditLogger::log([
    'action'     => 'pair_edit',
    'model'      => 'pairs',
    'model_id'   => $id,
    'old_values' => $old,
    'new_values' => $data,
]);
```

### RBAC Permission Matrix (app/Config/Permissions.php)

```php
<?php
// app/Config/Permissions.php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Permissions extends BaseConfig
{
    /**
     * Minimum role yang dibutuhkan per resource.
     * Hirarki: admin > staff > viewer > user
     */
    public array $matrix = [
        // Resource          => minimum role
        'categories'         => 'admin',
        'assets'             => 'admin',
        'pairs'              => 'admin',
        'payment_methods'    => 'admin',
        'settings'           => 'admin',
        'notifications_blast'=> 'admin',
        'users_manage'       => 'admin',

        'rates_update'       => 'staff',
        'orders_manage'      => 'staff',
        'orders_export'      => 'staff',

        'rates_view'         => 'viewer',
        'orders_view'        => 'viewer',
        'audit_view'         => 'viewer',
        'dashboard'          => 'viewer',
    ];

    public array $roleHierarchy = [
        'admin'  => 4,
        'staff'  => 3,
        'viewer' => 2,
        'user'   => 1,
        'public' => 0,
    ];

    public function can(string $userRole, string $resource): bool
    {
        $minRole     = $this->matrix[$resource] ?? 'admin';
        $userLevel   = $this->roleHierarchy[$userRole]   ?? 0;
        $minLevel    = $this->roleHierarchy[$minRole]    ?? 99;
        return $userLevel >= $minLevel;
    }
}
```

---

## 9. Sistem Rate Manual

### Alur Update Rate (8 Langkah)

```
[1] Admin buka /admin/rates
    → Lihat tabel: Pair | Rate | Last Updated | Updated By | [Update Rate]

[2] Klik tombol "Update Rate" pada pair tertentu
    → Modal terbuka, tampilkan rate lama

[3] Admin isi form:
    - New Rate   : numeric, > 0, required
    - Reason     : string, min_length 10, required

[4] Submit form (POST /admin/rates/{id}/update)
    → CSRF validated
    → Server validasi input

[5] Update pairs:
    - last_rate             = new_rate
    - last_rate_updated_at  = now()
    - last_rate_updated_by  = session user_id

[6] Insert pair_rate_history:
    - pair_id, rate, changed_by, change_reason, created_at

[7] Insert audit_logs:
    - action     = 'rate_update'
    - model      = 'pairs'
    - model_id   = pair_id
    - old_values = {"last_rate": old_rate}
    - new_values = {"last_rate": new_rate, "reason": reason}

[8] NotificationService::send() ke semua admin + staff:
    - type  = 'rate_update'
    - title = "Rate diupdate: {FROM} → {TO}"
    - body  = "Rate baru: {new_rate} oleh {user_name}. Alasan: {reason}"

[9] Return JSON {success: true, new_rate: ..., updated_at: ...}
    → UI update row tanpa full page reload (Alpine.js)
```

### Chart Rate History

```javascript
// Endpoint: GET /admin/rates/{id}/chart-data?range=30
// Response format:
{
  "labels": ["2025-01-01", "2025-01-05", "2025-01-10"],
  "data": [16000, 16050, 16100],
  "pair": "USDT → IDR",
  "current_rate": 16100
}

// Chart.js config (di views/admin/rates/history.php)
const ctx = document.getElementById('rateChart').getContext('2d');
new Chart(ctx, {
  type: 'line',
  data: {
    labels: chartData.labels,
    datasets: [{
      label: chartData.pair,
      data: chartData.data,
      borderColor: '#3b82f6',
      backgroundColor: 'rgba(59,130,246,0.08)',
      tension: 0.3,
      pointRadius: 4,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      y: { ticks: { color: '#8a93a8' }, grid: { color: '#2a3147' } },
      x: { ticks: { color: '#8a93a8' }, grid: { color: '#2a3147' } }
    }
  }
});
```

---

## 10. Security Model

### CSRF

- **Global**: aktif di semua POST via CI4 `Security` filter
- **Semua form**: wajib `<?= csrf_field() ?>`
- **Exception AJAX** (`/quote/calculate`):
  - Tidak pakai CSRF token standard
  - Pakai **HMAC-SHA256 signed token** dengan format: `{timestamp}.{hmac}`
  - Secret: `settings.converter.quote_token_secret`
  - Expiry: 300 detik (dari `settings.converter.quote_token_expiry`)
  - Server verifikasi: `hash_equals(expected_hmac, submitted_hmac) && time() - timestamp < expiry`

### XSS Prevention

```php
// WAJIB — semua output view:
<?= esc($variable) ?>

// DILARANG — jangan pernah:
<?= $variable ?>
echo $variable;

// Konten admin (instructions, notes) yang perlu HTML:
// → Render di client dengan DOMPurify, bukan raw echo server
```

### Upload Security

```php
// app/Libraries/UploadSecurity.php — referensi rules

// Logos
$allowedExt  = ['png', 'jpg', 'jpeg', 'svg'];
$allowedMime = ['image/png', 'image/jpeg', 'image/svg+xml'];
$maxSize     = 500;  // KB

// Payment proofs
$allowedExt  = ['png', 'jpg', 'jpeg', 'pdf'];
$allowedMime = ['image/png', 'image/jpeg', 'application/pdf'];
$maxSize     = 5120; // KB = 5MB

// Filename generation
$filename = bin2hex(random_bytes(16)) . '.' . $ext;

// Storage paths
// Logos  : public/assets/img/uploads/logos/{filename}
// Proofs : public/assets/img/uploads/proofs/{user_id}/{filename}
```

### Upload Folder `.htaccess` (wajib ada di setiap folder upload)

```apache
# public/assets/img/uploads/logos/.htaccess
# public/assets/img/uploads/proofs/.htaccess

Options -Indexes -ExecCGI
RemoveHandler .php .phtml .php3 .php4 .php5 .phar .shtml
RemoveType .php .phtml
php_flag engine off
```

### Session Security

```php
// app/Config/Session.php
public string  $driver     = 'CodeIgniter\Session\Handlers\DatabaseHandler';
public string  $savePath   = 'ci_sessions';
public bool    $cookieSecure   = true;  // production only
public bool    $cookieHTTPOnly = true;
public string  $cookieSameSite = 'Lax';

// Di LoginController::process() setelah auth berhasil:
session()->regenerate(true); // destroy old, start new
```

### Auth & Rate Limiting

```php
// Lockout logic di LoginController::process()

$maxAttempts = (int) setting('security.max_login_attempts'); // default 5
$recentFails = $loginAttemptModel->countRecentFails($email, $ip, 15); // 15 menit terakhir

if ($recentFails >= $maxAttempts) {
    // Hitung lockout duration
    $lockoutMinutes = match(true) {
        $recentFails >= 15 => 60,
        $recentFails >= 10 => 30,
        default            => 15,
    };
    // Return error dengan sisa waktu lockout
}

// Password hashing
password_hash($password, PASSWORD_ARGON2ID)
password_verify($submitted, $storedHash)
```

### Security Headers (SecurityHeadersFilter)

```php
// Tambahkan ke SEMUA response

$response->setHeader('X-Frame-Options',           'DENY');
$response->setHeader('X-Content-Type-Options',     'nosniff');
$response->setHeader('Referrer-Policy',            'strict-origin-when-cross-origin');
$response->setHeader('X-XSS-Protection',           '1; mode=block');
$response->setHeader('Permissions-Policy',         'camera=(), microphone=(), geolocation=()');
$response->setHeader('Content-Security-Policy',
    "default-src 'self'; " .
    "script-src 'self' cdn.jsdelivr.net unpkg.com; " .
    "style-src 'self' 'unsafe-inline'; " .
    "img-src 'self' data:; " .
    "font-src 'self'; " .
    "connect-src 'self'; " .
    "frame-ancestors 'none';"
);
```

### Threat Model

| Threat                  | Mitigasi                                                          |
|-------------------------|-------------------------------------------------------------------|
| SQL Injection           | Query Builder ONLY — zero raw SQL                                |
| XSS Reflected/Stored    | `esc()` wajib semua view, CSP header                            |
| CSRF                    | CI4 global CSRF + HMAC signed token untuk AJAX                  |
| Brute Force Login       | Rate limit 5x/15mnt + exponential lockout + attempt logging     |
| Session Hijacking       | Secure/HTTPOnly cookie, regenerate on login, DB handler         |
| Session Fixation        | `session()->regenerate(true)` saat login                        |
| Malicious File Upload   | MIME check via `finfo_file()`, random filename, deny execution  |
| Privilege Escalation    | RBACFilter setiap admin route, permission matrix                |
| Data Tampering          | Audit log + FK constraints + unique constraints + snapshots     |
| Maintenance Bypass      | MaintenanceFilter allow hanya admin session                     |
| Clickjacking            | `X-Frame-Options: DENY`                                         |
| MIME Sniffing           | `X-Content-Type-Options: nosniff`                               |

---

## 11. Deployment Shared Hosting

### Prerequisites

```
PHP     : 8.3+ (extensions: intl, mbstring, json, mysqlnd, fileinfo, curl, openssl)
MySQL   : 5.7+ atau MariaDB 10.3+
Apache  : mod_rewrite aktif
SSH     : opsional tapi disarankan
```

### Step 1 — Struktur Domain

```
cPanel Document Root  →  /home/username/converthub/public/
Project root          →  /home/username/converthub/
```

Atur via cPanel: **Domains → Add Domain → Document Root = converthub/public**

### Step 2 — Upload Files

```bash
# Semua file project termasuk vendor/ di-upload
# .env TIDAK di-upload — buat langsung di server
# Upload via FTP atau cPanel File Manager
```

### Step 3 — Konfigurasi `.env` di Server

```ini
CI_ENVIRONMENT = production

app.baseURL    = 'https://yourdomain.com/'
app.forceGlobalSecureRequests = true

database.default.hostname = localhost
database.default.database = cpanelusername_dbname
database.default.username = cpanelusername_dbuser
database.default.password = your_strong_password
database.default.DBDriver = MySQLi
database.default.charset  = utf8mb4
database.default.DBCollat = utf8mb4_unicode_ci

app.sessionDriver          = CodeIgniter\Session\Handlers\DatabaseHandler
app.sessionSavePath        = ci_sessions
app.sessionCookieName      = ch_session
app.sessionExpiration      = 7200
app.sessionCookieSecure    = true
app.sessionCookieHTTPOnly  = true
app.sessionCookieSameSite  = Lax
```

### Step 4 — Database & Migrations

```bash
# Via SSH (direkomendasikan):
php spark migrate
php spark db:seed DatabaseSeeder

# Tanpa SSH:
# Buat route sementara di Routes.php:
# $routes->get('_install', function() {
#     $migrate = \Config\Services::migrations();
#     $migrate->latest();
#     $seeder = \Config\Database::seeder();
#     $seeder->call('DatabaseSeeder');
#     echo 'Done — hapus route ini sekarang!';
# });
# Akses sekali, lalu hapus route tersebut.
```

### Step 5 — Permissions

```bash
chmod 755 writable/
chmod 755 writable/cache/ writable/logs/ writable/session/
chmod 755 public/assets/img/uploads/
chmod 755 public/assets/img/uploads/logos/
chmod 755 public/assets/img/uploads/proofs/
```

### Step 6 — Verifikasi `.htaccess` Upload Folders

```apache
# Upload file ini ke KEDUA folder:
# public/assets/img/uploads/logos/.htaccess
# public/assets/img/uploads/proofs/.htaccess

Options -Indexes -ExecCGI
RemoveHandler .php .phtml .php3 .php4 .php5 .phar .shtml
RemoveType .php .phtml
php_flag engine off
```

### Step 7 — Post-Deploy Checklist

```
[ ] Login dengan admin@converthub.com / Admin123!
[ ] LANGSUNG ubah password admin — jangan tunda
[ ] Set Site URL yang benar di /admin/settings
[ ] Konfigurasi SMTP dan test kirim email
[ ] Cek security headers via https://securityheaders.com
[ ] Matikan maintenance mode jika aktif
[ ] Test AJAX quote calculator
[ ] Buat order test dari awal sampai selesai
[ ] Verifikasi upload bukti bayar berfungsi
[ ] Verifikasi .htaccess di folder upload (coba akses direct file PHP)
[ ] Set cron backup database harian via cPanel Cron Jobs
```

### Backup Strategy

```bash
# Database — jalankan harian via cPanel Cron Jobs
mysqldump -u dbuser -pdbpass dbname > /home/username/backups/db_$(date +%Y%m%d).sql
gzip /home/username/backups/db_$(date +%Y%m%d).sql

# Uploads — sync mingguan
# Salin public/assets/img/uploads/ ke storage eksternal

# Retention: 7 daily + 4 weekly + 3 monthly
```

---

## Phase 1 — Core Setup + Auth + RBAC

> **AGENT:** Mulai dari sini. Implementasi berurutan. Jangan lanjut ke Phase 2 sebelum semua deliverable Phase 1 selesai dan laporan ditulis.

### Deliverables

- [ ] CI4 project terkonfigurasi (.env development untuk Laragon)
- [ ] `app/Config/Database.php` — koneksi MySQL
- [ ] `app/Config/Session.php` — database handler
- [ ] `app/Config/Security.php` — CSRF aktif
- [ ] `app/Config/Filters.php` — daftarkan semua filter
- [ ] `app/Config/Permissions.php` — RBAC matrix
- [ ] Migrations: `ci_sessions`, `users`, `login_attempts`, `settings`, `audit_logs`
- [ ] Seeders: `AdminSeeder`, `SettingSeeder`, `DatabaseSeeder`
- [ ] `app/Filters/AuthFilter.php`
- [ ] `app/Filters/AdminFilter.php`
- [ ] `app/Filters/RBACFilter.php`
- [ ] `app/Filters/MaintenanceFilter.php`
- [ ] `app/Filters/SecurityHeadersFilter.php`
- [ ] `app/Libraries/RBACLibrary.php`
- [ ] `app/Libraries/AuditLogger.php`
- [ ] `app/Controllers/Auth/LoginController.php` — login, logout, rate limit, lockout, argon2id
- [ ] `app/Controllers/Auth/RegisterController.php`
- [ ] `app/Models/UserModel.php`
- [ ] `app/Models/LoginAttemptModel.php`
- [ ] `app/Models/SettingModel.php`
- [ ] `app/Models/AuditLogModel.php`
- [ ] `app/Views/layouts/main.php`, `auth.php`, `user.php`, `admin.php`
- [ ] `app/Views/partials/flash_messages.php`, `nav_public.php`, `nav_admin.php`
- [ ] `app/Views/auth/login.php`, `register.php`
- [ ] `app/Config/Routes.php` — public + auth routes saja dulu

### Laporan Phase 1

```
✅ PHASE 1 SELESAI

File dibuat  :
  app/Config/Database.php
  app/Config/Session.php
  app/Config/Security.php
  app/Config/Filters.php
  app/Config/Permissions.php
  app/Database/Migrations/001_create_ci_sessions.php
  app/Database/Migrations/002_create_users.php
  app/Database/Migrations/003_create_login_attempts.php
  app/Database/Migrations/004_create_settings.php
  app/Database/Migrations/005_create_audit_logs.php
  app/Database/Seeds/AdminSeeder.php
  app/Database/Seeds/SettingSeeder.php
  app/Database/Seeds/DatabaseSeeder.php
  app/Filters/AuthFilter.php
  app/Filters/AdminFilter.php
  app/Filters/RBACFilter.php
  app/Filters/MaintenanceFilter.php
  app/Filters/SecurityHeadersFilter.php
  app/Libraries/RBACLibrary.php
  app/Libraries/AuditLogger.php
  app/Controllers/Auth/LoginController.php
  app/Controllers/Auth/RegisterController.php
  app/Models/UserModel.php
  app/Models/LoginAttemptModel.php
  app/Models/SettingModel.php
  app/Models/AuditLogModel.php
  app/Views/layouts/main.php
  app/Views/layouts/auth.php
  app/Views/layouts/user.php
  app/Views/layouts/admin.php
  app/Views/partials/flash_messages.php
  app/Views/partials/nav_public.php
  app/Views/partials/nav_admin.php
  app/Views/auth/login.php
  app/Views/auth/register.php

File diubah  :
  app/Config/Routes.php
  .env

Catatan      :
  - Password hashing: PASSWORD_ARGON2ID
  - Rate limit login: 5 attempts / 15 menit per email+IP
  - Lockout progression: 5→15mnt, 10→30mnt, 15+→60mnt
  - session()->regenerate(true) dipanggil saat login berhasil
  - SecurityHeadersFilter aktif global di semua response
  - AuditLogger siap dipakai hook di controller

Next         : Phase 2 — Categories & Assets CRUD + Logo Upload
```

---

## Phase 2 — Categories & Assets

> **AGENT:** Mulai Phase 2 setelah laporan Phase 1 ditulis. Fokus CRUD + upload logo.

### Deliverables

- [ ] Migrations: `categories`, `assets`
- [ ] Seeders: `CategorySeeder`, `AssetSeeder`
- [ ] `app/Libraries/UploadSecurity.php` — MIME check, random filename, path
- [ ] `app/Controllers/Admin/CategoryController.php` — CRUD + slug auto-generate
- [ ] `app/Controllers/Admin/AssetController.php` — CRUD + logo upload
- [ ] `app/Models/CategoryModel.php`
- [ ] `app/Models/AssetModel.php`
- [ ] `app/Views/admin/categories/index.php`, `form.php`
- [ ] `app/Views/admin/assets/index.php`, `form.php`
- [ ] `public/assets/img/uploads/logos/.htaccess` — deny execution
- [ ] Update `app/Config/Routes.php` — tambah admin category + asset routes

### Laporan Phase 2

```
✅ PHASE 2 SELESAI

File dibuat  :
  app/Database/Migrations/006_create_categories.php
  app/Database/Migrations/007_create_assets.php
  app/Database/Seeds/CategorySeeder.php
  app/Database/Seeds/AssetSeeder.php
  app/Libraries/UploadSecurity.php
  app/Controllers/Admin/CategoryController.php
  app/Controllers/Admin/AssetController.php
  app/Models/CategoryModel.php
  app/Models/AssetModel.php
  app/Views/admin/categories/index.php
  app/Views/admin/categories/form.php
  app/Views/admin/assets/index.php
  app/Views/admin/assets/form.php
  public/assets/img/uploads/logos/.htaccess

File diubah  :
  app/Config/Routes.php
  app/Database/Seeds/DatabaseSeeder.php

Catatan      :
  - Slug auto-generate dari name (lowercase, replace spasi dengan -, strip non-alnum)
  - Asset code di-force uppercase saat disimpan
  - MIME validation via PHP finfo_file() — bukan hanya ekstensi
  - Filename: bin2hex(random_bytes(16)) + '.' + ext
  - Logo preview ditampilkan di halaman edit
  - AuditLogger di-hook di store/update/delete

Next         : Phase 3 — Pairs CRUD + Converter UI + AJAX Quote
```

---

## Phase 3 — Pairs + Converter UI + AJAX Quote

> **AGENT:** Phase ini paling kompleks dari sisi UI. Buat converter UI dulu, baru AJAX endpoint.

### Deliverables

- [ ] Migration: `pairs`
- [ ] Seeder: `PairSeeder`
- [ ] `app/Controllers/Admin/PairController.php` — CRUD semua field
- [ ] `app/Controllers/Quote.php` — AJAX calculate, validasi signed token
- [ ] `app/Models/PairModel.php`
- [ ] `app/Libraries/RateCalculator.php` — formula lengkap (lihat Section 8)
- [ ] `app/Views/admin/pairs/index.php`, `form.php`
- [ ] `app/Views/home/index.php` — converter 3-panel, Alpine.js, tabs dinamis
- [ ] `public/assets/css/app.css` — design system lengkap (lihat Section 3)
- [ ] `public/assets/js/app.js` — Alpine.js data, debounce, AJAX logic
- [ ] Update `app/Config/Routes.php`

### AJAX Quote Flow

```
[User input amount] → debounce 400ms
  → Alpine.js fetch POST /quote/calculate
  → Header: X-Quote-Token: {timestamp}.{hmac}
  → Body: {pair_id, amount_sent}
  → Server: verifikasi token HMAC, kalkulasi, return JSON
  → Alpine.js update panel kanan: rate, fee, spread, net, warning
```

### Quote Token Generation (frontend)

```javascript
// Token dibuat saat halaman dimuat dari meta tag yang di-render server
// Server embed token di HTML:
// <meta name="qt" content="<?= esc($quoteToken) ?>">

// Token format: {timestamp}.{hmac_sha256(secret, timestamp)}
// Frontend hanya membaca dan mengirim — tidak generate sendiri
// Server regenerate token baru setiap request quote (stateless, cukup verifikasi HMAC)
```

### Laporan Phase 3

```
✅ PHASE 3 SELESAI

File dibuat  :
  app/Database/Migrations/008_create_pairs.php
  app/Database/Seeds/PairSeeder.php
  app/Controllers/Admin/PairController.php
  app/Controllers/Quote.php
  app/Models/PairModel.php
  app/Libraries/RateCalculator.php
  app/Views/admin/pairs/index.php
  app/Views/admin/pairs/form.php
  app/Views/home/index.php
  public/assets/css/app.css
  public/assets/js/app.js

File diubah  :
  app/Config/Routes.php
  app/Database/Seeds/DatabaseSeeder.php

Catatan      :
  - HMAC token diverifikasi server: hash_equals() + expiry check
  - Kalkulasi 100% server-side — frontend tidak bisa manipulasi
  - Debounce 400ms via Alpine.js x-data
  - Tabs kategori diambil dari DB, disimpan di state Alpine
  - Panel kanan: tampilkan rate, fee, spread, estimasi, min/max warning, notes pair
  - Semua angka pakai class .mono

Next         : Phase 4 — Rate Manager + History Chart + Audit Log
```

---

## Phase 4 — Rate Manager + History + Audit

### Deliverables

- [ ] Migrations: `pair_rate_history`, `audit_logs`
- [ ] `app/Controllers/Admin/RateController.php` — index, update, history, chartData
- [ ] `app/Controllers/Admin/AuditController.php` — index dengan filter
- [ ] `app/Models/PairRateHistoryModel.php`
- [ ] `app/Views/admin/rates/index.php` — table + modal update
- [ ] `app/Views/admin/rates/history.php` — table riwayat + Chart.js
- [ ] `app/Views/admin/audit/index.php`
- [ ] Hook `AuditLogger` di semua admin controller yang sudah ada
- [ ] `NotificationService::send()` triggered saat rate update
- [ ] Update `app/Config/Routes.php`

### Laporan Phase 4

```
✅ PHASE 4 SELESAI

File dibuat  :
  app/Database/Migrations/009_create_pair_rate_history.php
  app/Database/Migrations/010_create_audit_logs.php (jika belum)
  app/Controllers/Admin/RateController.php
  app/Controllers/Admin/AuditController.php
  app/Models/PairRateHistoryModel.php
  app/Views/admin/rates/index.php
  app/Views/admin/rates/history.php
  app/Views/admin/audit/index.php

File diubah  :
  app/Controllers/Admin/CategoryController.php (audit hook)
  app/Controllers/Admin/AssetController.php (audit hook)
  app/Controllers/Admin/PairController.php (audit hook)
  app/Config/Routes.php

Catatan      :
  - Rate update: atomic — update pair + insert history + audit + notif dalam satu transaksi DB
  - Chart.js endpoint return JSON {labels[], data[], pair, current_rate}
  - Filter chart: 7/30/90/0 (all) hari via query param ?range=N
  - Audit log old_values + new_values disimpan sebagai JSON
  - AuditLogger::log() dipanggil di semua controller admin yang ubah data

Next         : Phase 5 — Orders + Payment + Notifikasi
```

---

## Phase 5 — Orders + Payment + Notifikasi

### Deliverables

- [ ] Migrations: `payment_methods`, `pair_payment_methods`, `orders`, `notifications`
- [ ] Seeder: `PaymentMethodSeeder`
- [ ] `app/Controllers/Admin/PaymentMethodController.php`
- [ ] `app/Controllers/Admin/OrderController.php` — list, detail, updateStatus, exportCsv
- [ ] `app/Controllers/User/OrderController.php` — index, create, detail, uploadProof, cancel
- [ ] `app/Controllers/User/NotificationController.php`
- [ ] `app/Controllers/Admin/NotificationController.php` — blast
- [ ] `app/Libraries/NotificationService.php`
- [ ] `app/Models/PaymentMethodModel.php`, `PairPaymentMethodModel.php`
- [ ] `app/Models/OrderModel.php`
- [ ] `app/Models/NotificationModel.php`
- [ ] `app/Views/admin/payment_methods/index.php`, `form.php`
- [ ] `app/Views/admin/orders/index.php`, `detail.php`
- [ ] `app/Views/admin/notifications/blast.php`
- [ ] `app/Views/user/orders/index.php`, `detail.php`
- [ ] `app/Views/user/notifications.php`
- [ ] `app/Views/partials/notifications_dropdown.php`
- [ ] `public/assets/img/uploads/proofs/.htaccess` — deny execution
- [ ] Update `app/Config/Routes.php`

### Order Code Generation

```php
// Format: CH-YYYYMMDD-XXXXXX (6 char hex uppercase)
$code = 'CH-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
// Pastikan unique: cek DB, generate ulang jika collision (sangat jarang)
```

### Status Flow

```
pending → paid → processing → completed
                            → failed
       → cancelled (hanya dari pending, oleh user atau admin)
```

### CSV Export

```php
// app/Controllers/Admin/OrderController.php::exportCsv()
// Gunakan fputcsv() native PHP — tidak perlu library
// Header: Content-Type: text/csv, Content-Disposition: attachment; filename="orders-{date}.csv"
// Apply filter yang sama dengan halaman index
```

### Laporan Phase 5

```
✅ PHASE 5 SELESAI

File dibuat  :
  app/Database/Migrations/010_create_payment_methods.php
  app/Database/Migrations/011_create_pair_payment_methods.php
  app/Database/Migrations/012_create_orders.php
  app/Database/Migrations/013_create_notifications.php
  app/Database/Seeds/PaymentMethodSeeder.php
  app/Controllers/Admin/PaymentMethodController.php
  app/Controllers/Admin/OrderController.php
  app/Controllers/Admin/NotificationController.php
  app/Controllers/User/OrderController.php
  app/Controllers/User/NotificationController.php
  app/Libraries/NotificationService.php
  app/Models/PaymentMethodModel.php
  app/Models/PairPaymentMethodModel.php
  app/Models/OrderModel.php
  app/Models/NotificationModel.php
  app/Views/admin/payment_methods/index.php
  app/Views/admin/payment_methods/form.php
  app/Views/admin/orders/index.php
  app/Views/admin/orders/detail.php
  app/Views/admin/notifications/blast.php
  app/Views/user/orders/index.php
  app/Views/user/orders/detail.php
  app/Views/user/notifications.php
  app/Views/partials/notifications_dropdown.php
  public/assets/img/uploads/proofs/.htaccess

File diubah  :
  app/Config/Routes.php
  app/Database/Seeds/DatabaseSeeder.php
  app/Views/layouts/admin.php (notif dropdown)
  app/Views/layouts/user.php (notif dropdown)

Catatan      :
  - Order code: CH-YYYYMMDD-XXXXXX (collision check)
  - Snapshot rate/fee/spread disalin dari pair saat order dibuat — immutable
  - Proof path: uploads/proofs/{user_id}/{random16hex}.{ext}
  - CSV export pakai fputcsv() native, filter mengikuti query aktif
  - Blast notif: insert satu row per user target
  - Admin note wajib diisi saat update status ke cancelled/failed

Next         : Phase 6 — Settings + Maintenance + Security Hardening
```

---

## Phase 6 — Settings + Maintenance + Security Hardening

### Deliverables

- [ ] `app/Controllers/Admin/SettingController.php` — grouped form, toggle maintenance
- [ ] `app/Filters/RateLimitFilter.php` — throttle login & quote
- [ ] `app/Filters/SecurityHeadersFilter.php` (sudah ada di Phase 1, pastikan lengkap)
- [ ] `app/Views/admin/settings/index.php` — tab per group
- [ ] Tambah lockout check lengkap di `LoginController`
- [ ] Tambah 2FA TOTP skeleton (aktif jika `security.two_factor_admin = 1`)
- [ ] Verifikasi semua `.htaccess` upload folder terpasang
- [ ] Cache layer di `SettingModel` — jangan query DB setiap request
- [ ] Update `app/Config/Filters.php` — pastikan semua filter terdaftar

### SettingModel Cache Pattern

```php
// app/Models/SettingModel.php
public function get(string $group, string $key): mixed
{
    $cacheKey = "setting_{$group}_{$key}";
    $cached   = cache($cacheKey);
    if ($cached !== null) return $cached;

    $row = $this->where('group', $group)->where('key', $key)->first();
    $val = $row['value'] ?? null;

    cache()->save($cacheKey, $val, 300); // 5 menit
    return $val;
}

public function set(string $group, string $key, mixed $value): void
{
    $this->where('group', $group)->where('key', $key)
         ->set(['value' => $value, 'updated_at' => date('Y-m-d H:i:s')])
         ->update();

    cache()->delete("setting_{$group}_{$key}"); // invalidate cache
}
```

### Laporan Phase 6

```
✅ PHASE 6 SELESAI

File dibuat  :
  app/Controllers/Admin/SettingController.php
  app/Views/admin/settings/index.php
  (app/Filters/RateLimitFilter.php — jika belum ada dari Phase 1)

File diubah  :
  app/Filters/AuthFilter.php (lockout check lengkap)
  app/Filters/SecurityHeadersFilter.php (verifikasi semua header)
  app/Models/SettingModel.php (cache layer)
  app/Config/Filters.php (verifikasi semua filter terdaftar)
  app/Controllers/Auth/LoginController.php (2FA hook)

Catatan      :
  - SettingModel cache TTL 300 detik, invalidate saat update
  - Maintenance mode: cek settings.system.maintenance_mode di filter
  - SecurityHeadersFilter: semua 6 header aktif di setiap response
  - 2FA: skeleton siap, aktif jika setting two_factor_admin = 1
  - Lockout: exponential — 5→15mnt, 10→30mnt, 15+→60mnt
  - Settings security group: setiap update → AuditLogger

Next         : Phase 7 — Testing + Optimasi + Deployment
```

---

## Phase 7 — Testing + Optimasi + Deployment

### Deliverables

- [ ] Verifikasi CSV export berfungsi dengan semua kombinasi filter
- [ ] Cek dan fix semua N+1 query (gunakan `with()` atau join)
- [ ] Tambah index yang hilang berdasarkan EXPLAIN pada query berat
- [ ] Buat `TEST_CHECKLIST.md` (lihat di bawah)
- [ ] Buat `.env.example` lengkap
- [ ] Hapus semua route debug/dev
- [ ] Verifikasi semua `esc()` di setiap view
- [ ] Verifikasi semua form punya `csrf_field()`
- [ ] Test deployment di shared hosting sesuai Section 11

### Test Checklist (`docs/TEST_CHECKLIST.md`)

```markdown
# ConvertHub — Test Checklist

## Auth
- [ ] Login dengan kredensial benar → redirect ke dashboard
- [ ] Login dengan password salah 5x → lockout aktif
- [ ] Lockout expired → bisa login kembali
- [ ] Logout → session destroyed, redirect ke /login
- [ ] Akses /admin tanpa login → redirect ke /login
- [ ] User biasa akses /admin → 403 forbidden

## Converter Public
- [ ] Halaman / load tanpa error
- [ ] Tabs kategori tampil dari DB
- [ ] Input amount → AJAX terpanggil setelah debounce 400ms
- [ ] Panel kanan update: rate, fee, estimasi, min/max warning
- [ ] Klik "Buat Order" tanpa login → redirect ke /login
- [ ] Quote token invalid / expired → error response

## Orders (User)
- [ ] Buat order dari quote yang valid → order tersimpan dengan snapshot
- [ ] Upload bukti bayar → file tersimpan, status tidak berubah otomatis
- [ ] Cancel order (status pending) → berhasil
- [ ] Cancel order (status paid) → ditolak

## Rate Manager (Admin)
- [ ] Update rate tanpa alasan → validasi error
- [ ] Update rate dengan alasan < 10 char → validasi error
- [ ] Update rate valid → pair.last_rate berubah, history tercatat, audit tercatat
- [ ] Chart history tampil dengan data yang benar

## Security
- [ ] CSRF: submit form tanpa token → 403
- [ ] Upload PHP file sebagai logo → ditolak (MIME check)
- [ ] Akses langsung file di /uploads/logos/file.php → denied (htaccess)
- [ ] Response header: X-Frame-Options: DENY ada di semua halaman
- [ ] Response header: CSP ada di semua halaman
- [ ] SQL injection attempt di search field → tidak error, query Builder aman

## Admin CRUD
- [ ] Create category → tersimpan, slug auto-generate benar
- [ ] Create asset dengan code duplikat → validasi error
- [ ] Create pair dengan from=to → validasi error
- [ ] Update pair → AuditLogger mencatat old + new values
```

### Laporan Phase 7 (Final)

```
✅ PHASE 7 SELESAI — PROJECT COMPLETE 🚀

File dibuat  :
  docs/TEST_CHECKLIST.md
  .env.example

File diubah  :
  app/Controllers/Admin/OrderController.php (CSV export finalisasi)
  app/Models/PairModel.php (eager load assets)
  app/Models/OrderModel.php (eager load user + pair)
  app/Config/Routes.php (hapus dev routes)

Catatan      :
  - Semua N+1 query diselesaikan dengan join / with()
  - CSV export: fputcsv() native, filter mengikuti state query index
  - Semua esc() diverifikasi manual di setiap view file
  - Semua form csrf_field() diverifikasi
  - Security headers ditest via securityheaders.com → Grade A
  - Deployment divalidasi di shared hosting cPanel
  - Test checklist: 100% passed

Next         : 🚀 LAUNCH — Ganti password admin, konfigurasi SMTP, set Site URL, GO LIVE.
```

---

<!-- ============================================================
END OF BLUEPRINT
ConvertHub CI4 v1.0
Stack: CodeIgniter 4 | PHP 8.3 | MySQL | Alpine.js | Chart.js | Custom CSS
Security: High | Rate Mode: Manual Admin Only | Deploy: Shared Hosting
============================================================ -->
