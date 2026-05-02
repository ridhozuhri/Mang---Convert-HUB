<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'name' => 'Bank BCA IDR',
                'slug' => 'bank-bca-idr',
                'type' => 'manual',
                'instructions' => 'Transfer ke rekening BCA resmi lalu upload bukti transfer.',
                'status' => 'active',
                'sort_order' => 1,
            ],
            [
                'name' => 'Bank Transfer (Auto) IDR',
                'slug' => 'bank-transfer-auto-idr',
                'type' => 'manual',
                'instructions' => 'Transfer bank otomatis via VA. Verifikasi umumnya 1-10 menit.',
                'status' => 'active',
                'sort_order' => 2,
            ],
            [
                'name' => 'Bank BRI IDR',
                'slug' => 'bank-bri-idr',
                'type' => 'manual',
                'instructions' => 'Transfer ke rekening BRI resmi dan lampirkan bukti transfer.',
                'status' => 'active',
                'sort_order' => 3,
            ],
            [
                'name' => 'Bank Mandiri IDR',
                'slug' => 'bank-mandiri-idr',
                'type' => 'manual',
                'instructions' => 'Transfer Mandiri ke rekening resmi sesuai nominal order.',
                'status' => 'active',
                'sort_order' => 4,
            ],
            [
                'name' => 'Bank BNI IDR',
                'slug' => 'bank-bni-idr',
                'type' => 'manual',
                'instructions' => 'Transfer BNI sesuai nominal unik order agar validasi cepat.',
                'status' => 'active',
                'sort_order' => 5,
            ],
            [
                'name' => 'Bank Jago IDR',
                'slug' => 'bank-jago-idr',
                'type' => 'manual',
                'instructions' => 'Transfer dari Bank Jago. Wajib gunakan rekening nama sendiri.',
                'status' => 'active',
                'sort_order' => 6,
            ],
            [
                'name' => 'BI-FAST / Bank Lain IDR',
                'slug' => 'bi-fast-bank-lain-idr',
                'type' => 'manual',
                'instructions' => 'Transfer dari bank lain via BI-FAST ke rekening resmi.',
                'status' => 'active',
                'sort_order' => 7,
            ],
            [
                'name' => 'DANA IDR',
                'slug' => 'dana-idr',
                'type' => 'gateway',
                'instructions' => 'Kirim via DANA dengan akun terverifikasi atas nama sendiri.',
                'status' => 'active',
                'sort_order' => 8,
            ],
            [
                'name' => 'OVO IDR',
                'slug' => 'ovo-idr',
                'type' => 'gateway',
                'instructions' => 'Kirim via OVO ke akun resmi sesuai nominal order.',
                'status' => 'active',
                'sort_order' => 9,
            ],
            [
                'name' => 'GoPay IDR',
                'slug' => 'gopay-idr',
                'type' => 'gateway',
                'instructions' => 'Kirim via GoPay. Pastikan akun sudah premium untuk transfer.',
                'status' => 'active',
                'sort_order' => 10,
            ],
            [
                'name' => 'PayPal (Global) USD',
                'slug' => 'paypal-global-usd',
                'type' => 'gateway',
                'instructions' => 'Kirim USD via PayPal Global ke akun resmi.',
                'status' => 'active',
                'sort_order' => 11,
            ],
            [
                'name' => 'PayPal (ID) USD',
                'slug' => 'paypal-id-usd',
                'type' => 'gateway',
                'instructions' => 'Kirim USD via PayPal Indonesia. Biaya pihak ketiga ditanggung user.',
                'status' => 'active',
                'sort_order' => 12,
            ],
            [
                'name' => 'FasaPay USD',
                'slug' => 'fasapay-usd',
                'type' => 'gateway',
                'instructions' => 'Kirim USD dari akun FasaPay terverifikasi.',
                'status' => 'active',
                'sort_order' => 13,
            ],
            [
                'name' => 'ePay USD',
                'slug' => 'epay-usd',
                'type' => 'gateway',
                'instructions' => 'Kirim USD dari ePay ke akun resmi.',
                'status' => 'active',
                'sort_order' => 14,
            ],
            [
                'name' => 'Skrill USD',
                'slug' => 'skrill-usd',
                'type' => 'gateway',
                'instructions' => 'Kirim USD via Skrill. Akun wajib KYC dan nama sama.',
                'status' => 'active',
                'sort_order' => 15,
            ],
            [
                'name' => 'USDT TRC20',
                'slug' => 'usdt-trc20',
                'type' => 'gateway',
                'instructions' => 'Kirim USDT jaringan TRC20 ke alamat wallet resmi.',
                'status' => 'active',
                'sort_order' => 16,
            ],
            [
                'name' => 'USDT BEP20',
                'slug' => 'usdt-bep20',
                'type' => 'gateway',
                'instructions' => 'Kirim USDT jaringan BEP20. Salah network dianggap hangus.',
                'status' => 'active',
                'sort_order' => 17,
            ],
            [
                'name' => 'Bitcoin Network (BTC)',
                'slug' => 'bitcoin-network-btc',
                'type' => 'gateway',
                'instructions' => 'Kirim BTC ke alamat wallet resmi. Tunggu minimal 1 konfirmasi.',
                'status' => 'active',
                'sort_order' => 18,
            ],
        ];

        $table = $this->db->table('payment_methods');
        $now = date('Y-m-d H:i:s');

        foreach ($items as $item) {
            $existing = $table->where('slug', $item['slug'])->get()->getRowArray();
            if (is_array($existing)) {
                $table->where('id', (int) $existing['id'])->update([
                    'name' => $item['name'],
                    'type' => $item['type'],
                    'instructions' => $item['instructions'],
                    'status' => $item['status'],
                    'sort_order' => $item['sort_order'],
                    'updated_at' => $now,
                ]);
            } else {
                $item['created_at'] = $now;
                $item['updated_at'] = $now;
                $table->insert($item);
            }
        }

        $table->whereIn('slug', ['bank-transfer', 'e-wallet'])->update([
            'status' => 'inactive',
            'updated_at' => $now,
        ]);
    }
}
