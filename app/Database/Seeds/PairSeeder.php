<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PairSeeder extends Seeder
{
    public function run(): void
    {
        $assets = $this->db->table('assets')->select('id, code')->get()->getResultArray();
        $assetMap = [];
        foreach ($assets as $asset) {
            $assetMap[$asset['code']] = (int) $asset['id'];
        }

        $pairs = [
            [
                'from_code' => 'USDT',
                'to_code'   => 'IDR',
                'status'    => 'active',
                'min_amount' => 10,
                'max_amount' => 50000,
                'fee_type'   => 'percent',
                'fee_value'  => 0.5,
                'spread_type' => 'none',
                'spread_value' => 0,
                'rounding_mode' => 'floor',
                'rounding_precision' => 0,
                'rate_mode' => 'manual',
                'last_rate' => 16100,
                'notes' => 'Transfer ke rekening BCA/Mandiri. Proses 1-3 jam kerja.',
            ],
            [
                'from_code' => 'USD',
                'to_code'   => 'IDR',
                'status'    => 'active',
                'min_amount' => 1,
                'max_amount' => 10000,
                'fee_type'   => 'fixed',
                'fee_value'  => 5000,
                'spread_type' => 'none',
                'spread_value' => 0,
                'rounding_mode' => 'floor',
                'rounding_precision' => 0,
                'rate_mode' => 'manual',
                'last_rate' => 16050,
                'notes' => 'USD via PayPal ke IDR. Proses 1-6 jam kerja.',
            ],
            [
                'from_code' => 'BTC',
                'to_code'   => 'IDR',
                'status'    => 'active',
                'min_amount' => 0.0001,
                'max_amount' => 1,
                'fee_type'   => 'percent',
                'fee_value'  => 1,
                'spread_type' => 'none',
                'spread_value' => 0,
                'rounding_mode' => 'floor',
                'rounding_precision' => 0,
                'rate_mode' => 'manual',
                'last_rate' => 980000000,
                'notes' => 'BTC ke IDR. Rate update setiap 30 menit. Proses 1-4 jam.',
            ],
            [
                'from_code' => 'IDR',
                'to_code'   => 'PAYPAL_USD',
                'status'    => 'active',
                'min_amount' => 100000,
                'max_amount' => 100000000,
                'fee_type'   => 'fixed',
                'fee_value'  => 0,
                'spread_type' => 'none',
                'spread_value' => 0,
                'rounding_mode' => 'floor',
                'rounding_precision' => 2,
                'rate_mode' => 'inverse',
                'last_rate' => 18000,
                'notes' => 'IDR ke PayPal USD. Proses 5-30 menit pada jam operasional.',
            ],
            [
                'from_code' => 'IDR',
                'to_code'   => 'USDT',
                'status'    => 'active',
                'min_amount' => 100000,
                'max_amount' => 150000000,
                'fee_type'   => 'fixed',
                'fee_value'  => 0,
                'spread_type' => 'none',
                'spread_value' => 0,
                'rounding_mode' => 'floor',
                'rounding_precision' => 2,
                'rate_mode' => 'inverse',
                'last_rate' => 17500,
                'notes' => 'IDR ke USDT. Jaringan pengiriman disesuaikan service yang dipilih.',
            ],
            [
                'from_code' => 'IDR',
                'to_code'   => 'BTC',
                'status'    => 'active',
                'min_amount' => 500000,
                'max_amount' => 200000000,
                'fee_type'   => 'fixed',
                'fee_value'  => 0,
                'spread_type' => 'none',
                'spread_value' => 0,
                'rounding_mode' => 'floor',
                'rounding_precision' => 8,
                'rate_mode' => 'inverse',
                'last_rate' => 1350000000,
                'notes' => 'IDR ke BTC. Rate mengikuti update market internal.',
            ],
        ];

        $table = $this->db->table('pairs');
        $now   = date('Y-m-d H:i:s');

        foreach ($pairs as $pair) {
            $fromId = $assetMap[$pair['from_code']] ?? null;
            $toId   = $assetMap[$pair['to_code']] ?? null;
            if ($fromId === null || $toId === null) {
                continue;
            }

            $payload = [
                'from_asset_id' => $fromId,
                'to_asset_id' => $toId,
                'status' => $pair['status'],
                'sort_order' => 0,
                'min_amount' => $pair['min_amount'],
                'max_amount' => $pair['max_amount'],
                'fee_type' => $pair['fee_type'],
                'fee_value' => $pair['fee_value'],
                'spread_type' => $pair['spread_type'],
                'spread_value' => $pair['spread_value'],
                'rounding_mode' => $pair['rounding_mode'],
                'rounding_precision' => $pair['rounding_precision'],
                'rate_mode' => (string) ($pair['rate_mode'] ?? 'manual'),
                'last_rate' => $pair['last_rate'],
                'last_rate_updated_at' => $now,
                'notes' => $pair['notes'],
                'updated_at' => $now,
            ];

            $existing = $table->where('from_asset_id', $fromId)->where('to_asset_id', $toId)->get()->getRowArray();
            if (is_array($existing)) {
                $table->where('id', (int) $existing['id'])->update($payload);
                continue;
            }

            $payload['created_at'] = $now;
            $table->insert($payload);
        }
    }
}
