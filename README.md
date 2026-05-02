# MANG-CONVERT (ConvertHub)

Platform konversi aset berbasis CodeIgniter 4 dengan flow transaksi manual oleh admin.

## Fitur Utama

- Converter publik model **Jual -> Beli** dengan quote realtime.
- Manajemen master data: kategori, aset, pair, service.
- Rule service per pair (limit, tujuan transfer, sender requirement, catatan customer).
- Manajemen order admin:
  - quick action (`approve/process/complete/reject`)
  - bulk action
  - lock/unlock order antar admin
  - SLA timer + eskalasi overdue
  - verifikasi bukti transfer (`pending_review/verified/rejected`)
- Audit log aktivitas admin.
- Notifikasi transaksi.
- Asset ledger + halaman rekonsiliasi.

## Requirement

- PHP `^8.2`
- MySQL/MariaDB
- Composer

## Setup Lokal

1. Install dependency:
   ```bash
   composer install
   ```
2. Siapkan file environment:
   - copy `env` menjadi `.env`
   - set database di `.env`
3. Jalankan migrasi:
   ```bash
   php spark migrate
   ```
4. Seed data awal:
   ```bash
   php spark db:seed DatabaseSeeder
   ```
5. Jalankan server:
   ```bash
   php spark serve
   ```
6. Buka:
   - `http://localhost:8080/` (public)
   - `http://localhost:8080/admin` (dashboard admin)

## Akun Default (Seeder)

- Email: `admin@converthub.com`
- Password: `Admin123!`

> Ganti password setelah login pertama.

## Struktur Penting

- `app/Controllers/Admin` : modul admin
- `app/Controllers/User` : modul user
- `app/Models` : model database
- `app/Database/Migrations` : skema database
- `app/Database/Seeds` : data awal
- `app/Views` : template halaman
- `public/assets` : css/js/image

## Catatan

- Base URL default project: `http://localhost:8080/`
- URL sudah tanpa `index.php`.
- Pastikan web server mengarah ke folder `public/`.
