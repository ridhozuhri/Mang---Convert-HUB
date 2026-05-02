<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PairPaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $pairs = $this->db->table('pairs p')
            ->select('p.id, fa.code AS from_code, ta.code AS to_code')
            ->join('assets fa', 'fa.id = p.from_asset_id', 'left')
            ->join('assets ta', 'ta.id = p.to_asset_id', 'left')
            ->get()
            ->getResultArray();

        $pairMap = [];
        foreach ($pairs as $pair) {
            $key = strtoupper((string) $pair['from_code']) . '->' . strtoupper((string) $pair['to_code']);
            $pairMap[$key] = (int) $pair['id'];
        }

        $methods = $this->db->table('payment_methods')->select('id, slug')->get()->getResultArray();
        $methodMap = [];
        foreach ($methods as $method) {
            $methodMap[(string) $method['slug']] = (int) $method['id'];
        }

        $map = [
            'USDT->IDR' => [
                'usdt-trc20',
                'usdt-bep20',
                'bank-transfer-auto-idr',
                'bank-bca-idr',
                'bi-fast-bank-lain-idr',
                'dana-idr',
            ],
            'USD->IDR' => [
                'paypal-global-usd',
                'paypal-id-usd',
                'fasapay-usd',
                'epay-usd',
                'skrill-usd',
                'bank-transfer-auto-idr',
            ],
            'BTC->IDR' => [
                'bitcoin-network-btc',
                'bank-transfer-auto-idr',
                'bank-bca-idr',
                'bi-fast-bank-lain-idr',
            ],
            'IDR->PAYPAL_USD' => [
                'bank-transfer-auto-idr',
                'bank-bca-idr',
                'bank-bri-idr',
                'bank-mandiri-idr',
                'bank-bni-idr',
                'dana-idr',
                'ovo-idr',
                'gopay-idr',
            ],
            'IDR->USDT' => [
                'bank-transfer-auto-idr',
                'bank-bca-idr',
                'bank-bri-idr',
                'bank-mandiri-idr',
                'dana-idr',
            ],
            'IDR->BTC' => [
                'bank-transfer-auto-idr',
                'bank-bca-idr',
                'bi-fast-bank-lain-idr',
                'bank-jago-idr',
            ],
        ];

        $table = $this->db->table('pair_payment_methods');

        foreach ($map as $pairKey => $methodSlugs) {
            $pairId = $pairMap[$pairKey] ?? null;
            if (! is_int($pairId)) {
                continue;
            }

            foreach ($methodSlugs as $slug) {
                $methodId = $methodMap[$slug] ?? null;
                if (! is_int($methodId)) {
                    continue;
                }

                $existing = $table
                    ->where('pair_id', $pairId)
                    ->where('payment_method_id', $methodId)
                    ->get()
                    ->getRowArray();

                if (is_array($existing)) {
                    $table->where('id', (int) $existing['id'])->update(['is_active' => 1]);
                    continue;
                }

                $table->insert([
                    'pair_id'           => $pairId,
                    'payment_method_id' => $methodId,
                    'rules_json'        => null,
                    'is_active'         => 1,
                ]);
            }
        }
    }
}
