<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class GuideController extends BaseController
{
    public function index(): string
    {
        return view('admin/guide/index', [
            'pageTitle' => 'Guide Admin',
            'menus' => [
                [
                    'name' => 'Guide',
                    'path' => '/admin/guide',
                    'function' => 'Halaman panduan admin berisi fungsi menu dan contoh pengisian form.',
                ],
                [
                    'name' => 'Overview',
                    'path' => '/admin',
                    'function' => 'Landing dashboard admin dan navigasi cepat ke modul utama.',
                ],
                [
                    'name' => 'Order Transaksi',
                    'path' => '/admin/orders',
                    'function' => 'Monitoring order user, detail order, ubah status, dan export CSV.',
                ],
                [
                    'name' => 'Kelola Rate',
                    'path' => '/admin/rates',
                    'function' => 'Update rate manual per service/pair, simpan reason, lalu cek history/chart.',
                ],
                [
                    'name' => 'Rate History',
                    'path' => '/admin/rates/{id}/history',
                    'function' => 'Riwayat perubahan rate per service/pair lengkap dengan grafik.',
                ],
                [
                    'name' => 'Audit Aktivitas',
                    'path' => '/admin/audit',
                    'function' => 'Lihat jejak audit login, perubahan rate, perubahan order, dan aksi admin.',
                ],
                [
                    'name' => 'Kategori Aset',
                    'path' => '/admin/categories',
                    'function' => 'Kelola kategori converter (contoh: ewallet, bank, crypto).',
                ],
                [
                    'name' => 'Daftar Aset',
                    'path' => '/admin/assets',
                    'function' => 'Kelola asset/code/logo yang dipakai di pair converter.',
                ],
                [
                    'name' => 'Service (Pair Konversi)',
                    'path' => '/admin/pairs',
                    'function' => 'Atur pasangan from-to, fee, spread, min-max, dan rule pembulatan.',
                ],
                [
                    'name' => 'Service',
                    'path' => '/admin/payment-methods',
                    'function' => 'Kelola metode bayar dan keterkaitan metode dengan pair.',
                ],
                [
                    'name' => 'Broadcast Notifikasi',
                    'path' => '/admin/notifications/blast',
                    'function' => 'Kirim notifikasi massal ke user secara manual.',
                ],
                [
                    'name' => 'Management User',
                    'path' => '/admin/users',
                    'function' => 'Kelola akun user internal: tambah user, edit role, aktif/nonaktifkan akun.',
                ],
                [
                    'name' => 'Pengaturan Platform',
                    'path' => '/admin/settings',
                    'function' => 'Atur konfigurasi sistem (converter, security, smtp, maintenance, dll).',
                ],
            ],
            'examples' => [
                [
                    'title' => 'Contoh pembuatan Service (Pair Konversi)',
                    'steps' => [
                        'Masuk ke menu `Kategori Aset`, buat kategori yang dibutuhkan.',
                        'Masuk ke menu `Daftar Aset`, buat asset FROM dan asset TO.',
                        'Masuk ke menu `Pair Konversi`, klik create pair.',
                        'Set `from_asset`, `to_asset`, `min/max`, `fee`, `spread`, lalu simpan.',
                        'Masuk ke `Kelola Rate` untuk set rate awal pair baru.',
                    ],
                ],
                [
                    'title' => 'Contoh pembuatan Asset',
                    'steps' => [
                        'Masuk ke `Daftar Aset` lalu klik create.',
                        'Isi nama asset, code unik, kategori, urutan tampil, dan status aktif.',
                        'Upload logo (png/jpg/webp) sesuai validasi sistem.',
                        'Simpan, lalu verifikasi asset muncul pada pilihan saat create pair.',
                    ],
                ],
                [
                    'title' => 'Contoh update Rate',
                    'steps' => [
                        'Masuk ke `Kelola Rate`.',
                        'Pilih pair lalu klik `Update Rate`.',
                        'Isi `new rate` dan `reason` minimal 10 karakter.',
                        'Submit, lalu cek perubahan di history chart dan audit log.',
                    ],
                ],
            ],
            'fillSamples' => [
                [
                    'title' => 'Contoh isi: Kategori Aset',
                    'menu'  => '/admin/categories',
                    'fields'=> [
                        'name'      => 'E-Wallet',
                        'slug'      => 'ewallet',
                        'sort_order'=> '10',
                        'is_active' => 'Active',
                    ],
                ],
                [
                    'title' => 'Contoh isi: Asset',
                    'menu'  => '/admin/assets',
                    'fields'=> [
                        'category'  => 'E-Wallet',
                        'name'      => 'DANA',
                        'code'      => 'DANA',
                        'status'    => 'active',
                        'sort_order'=> '20',
                        'logo'      => 'dana.png',
                    ],
                ],
                [
                    'title' => 'Contoh isi: Service (Pair Konversi)',
                    'menu'  => '/admin/pairs',
                    'fields'=> [
                        'from_asset'       => 'USDT',
                        'to_asset'         => 'DANA',
                        'status'           => 'active',
                        'min_amount'       => '10',
                        'max_amount'       => '5000',
                        'fee_type'         => 'percent',
                        'fee_value'        => '1.5',
                        'spread_type'      => 'percent',
                        'spread_value'     => '0.5',
                        'rounding_mode'    => 'round',
                        'rounding_precision'=> '2',
                    ],
                ],
                [
                    'title' => 'Contoh isi: Update Rate',
                    'menu'  => '/admin/rates',
                    'fields'=> [
                        'pair'     => 'USDT → DANA',
                        'new_rate' => '15650',
                        'reason'   => 'Penyesuaian kurs market jam 10:00 WIB',
                    ],
                ],
                [
                    'title' => 'Contoh isi: Service',
                    'menu'  => '/admin/payment-methods',
                    'fields'=> [
                        'name'      => 'Transfer BCA',
                        'type'      => 'manual',
                        'status'    => 'active',
                        'sort_order'=> '10',
                        'linked_pair'=> 'USDT → DANA',
                    ],
                ],
                [
                    'title' => 'Contoh isi: SMTP (Settings)',
                    'menu'  => '/admin/settings',
                    'fields'=> [
                        'smtp_host'       => 'smtp.gmail.com',
                        'smtp_port'       => '587',
                        'smtp_user'       => 'noreply@domain.com',
                        'smtp_pass'       => 'app-password',
                        'smtp_encryption' => 'tls',
                        'from_name'       => 'ConvertHub',
                    ],
                ],
            ],
            'pairFieldGuide' => [
                [
                    'field' => 'Spread Type',
                    'description' => 'Jenis spread yang diterapkan ke perhitungan output. Pilihan: none, fixed, percent.',
                    'example' => 'percent untuk spread 0.5% per transaksi.',
                ],
                [
                    'field' => 'Spread Value',
                    'description' => 'Nilai spread sesuai Spread Type. Jika fixed = nominal, jika percent = persen.',
                    'example' => 'Spread Type=percent, Spread Value=0.5 berarti 0.5%.',
                ],
                [
                    'field' => 'Rounding Mode',
                    'description' => 'Metode pembulatan hasil akhir konversi.',
                    'example' => 'floor (turun), ceil (naik), round (pembulatan normal).',
                ],
                [
                    'field' => 'Rounding Precision',
                    'description' => 'Jumlah digit desimal setelah koma saat pembulatan dilakukan.',
                    'example' => 'USD biasanya 2, IDR biasanya 0.',
                ],
                [
                    'field' => 'Rate Mode',
                    'description' => 'Mode sumber rate. Saat ini sistem memakai mode manual oleh admin.',
                    'example' => 'manual: rate diupdate dari menu Kelola Rate.',
                ],
                [
                    'field' => 'Sort Order',
                    'description' => 'Urutan tampil data di list/front-end. Angka lebih kecil tampil lebih dulu.',
                    'example' => '10 tampil di atas 20.',
                ],
            ],
        ]);
    }
}
