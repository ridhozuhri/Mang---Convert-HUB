# ConvertHub - Test Checklist

Last validated (smoke + CSV regression): 2026-04-20

## Auth
- [ ] Login dengan kredensial benar -> redirect ke dashboard
- [ ] Login dengan password salah 5x -> lockout aktif
- [ ] Lockout expired -> bisa login kembali
- [ ] Logout -> session destroyed, redirect ke /login
- [ ] Akses /admin tanpa login -> redirect ke /login
- [ ] User biasa akses /admin -> 403 forbidden

## Converter Public
- [ ] Halaman / load tanpa error
- [ ] Tabs kategori tampil dari DB
- [ ] Input amount -> AJAX terpanggil setelah debounce 400ms
- [ ] Panel kanan update: rate, fee, estimasi, min/max warning
- [ ] Klik "Buat Order" tanpa login -> redirect ke /login
- [ ] Quote token invalid/expired -> error response

## Orders (User)
- [ ] Buat order dari quote valid -> order tersimpan dengan snapshot
- [ ] Upload bukti bayar -> file tersimpan, status tidak berubah otomatis
- [ ] Cancel order (status pending) -> berhasil
- [ ] Cancel order (status paid) -> ditolak

## Rate Manager (Admin)
- [ ] Update rate tanpa alasan -> validasi error
- [ ] Update rate dengan alasan < 10 char -> validasi error
- [ ] Update rate valid -> pair.last_rate berubah, history tercatat, audit tercatat
- [ ] Chart history tampil dengan data yang benar

## Security
- [ ] CSRF: submit form tanpa token -> 403
- [ ] Upload PHP file sebagai logo -> ditolak (MIME check)
- [ ] Akses langsung file di /uploads/logos/file.php -> denied
- [ ] Response header: X-Frame-Options: DENY ada di semua halaman
- [ ] Response header: CSP ada di semua halaman
- [ ] SQL injection attempt di search field -> tidak error

## Admin CRUD
- [ ] Create category -> tersimpan, slug auto-generate benar
- [ ] Create asset dengan code duplikat -> validasi error
- [ ] Create pair dengan from=to -> validasi error
- [ ] Update pair -> AuditLogger mencatat old + new values
