# Phase 7 Report — Testing + Optimasi + Deployment

Tanggal verifikasi: 2026-04-20

## Ringkasan
- CSV export diverifikasi untuk beberapa kombinasi filter dan semua menghasilkan `200` + `text/csv`.
- Query berat dianalisis dengan `EXPLAIN`, lalu ditambahkan index komposit untuk mengurangi filesort/full scan.
- Audit `esc()` dan `csrf_field()` di seluruh view aplikasi selesai.
- Route debug/dev diverifikasi tidak ada.
- Deployment shared hosting divalidasi via preflight checklist sesuai Section 11 blueprint.

## Detail Validasi

### 1) CSV Export
URL diuji:
- `/admin/orders/export-csv`
- `/admin/orders/export-csv?status=pending`
- `/admin/orders/export-csv?code=ORD`
- `/admin/orders/export-csv?user_email=@`
- `/admin/orders/export-csv?date_from=2026-01-01&date_to=2026-12-31`
- `/admin/orders/export-csv?status=completed&date_from=2026-01-01&date_to=2026-12-31`

Hasil: semua endpoint return sukses dan header CSV valid.

### 2) Optimasi Query (EXPLAIN + Index)
Index tambahan:
- `orders.idx_orders_status_created_id (status, created_at, id)`
- `audit_logs.idx_al_action_created_id (action, created_at, id)`
- `pair_rate_history.idx_prh_pair_created (pair_id, created_at)`
- `notifications.idx_notif_user_read_created (user_id, is_read, created_at)`
- `login_attempts.idx_la_email_ip_success_created (email, ip_address, is_success, created_at)`

### 3) Audit View Security
- Verifikasi semua output variabel user-facing memakai `esc(...)`.
- Verifikasi semua form `POST` mengandung `csrf_field()`.

### 4) Route Hygiene
- Verifikasi `php spark routes` tidak memuat route debug/dev seperti `_install`, `_debug`, dll.

### 5) Deployment Shared Hosting (Section 11)
Preflight valid:
- `public/.htaccess` tersedia.
- Upload hardening tersedia:
  - `public/assets/img/uploads/logos/.htaccess`
  - `public/assets/img/uploads/proofs/.htaccess`
- `.env.example` siap untuk dijadikan template environment production.

Catatan:
- Simulasi dilakukan di environment lokal. Eksekusi final di cPanel/hosting target tetap perlu dilakukan saat go-live.
