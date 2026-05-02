<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        $categories = $this->db->table('categories')->select('id, slug')->get()->getResultArray();
        $categoryMap = [];
        foreach ($categories as $category) {
            $categoryMap[$category['slug']] = (int) $category['id'];
        }

        $items = [
            ['category_slug' => 'bank', 'code' => 'IDR', 'name' => 'Indonesian Rupiah', 'symbol' => 'Rp', 'decimals' => 0],
            ['category_slug' => 'usd', 'code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimals' => 2],
            ['category_slug' => 'stable', 'code' => 'USDT', 'name' => 'Tether USD', 'symbol' => 'USDT', 'decimals' => 2],
            ['category_slug' => 'coin', 'code' => 'BTC', 'name' => 'Bitcoin', 'symbol' => 'BTC', 'decimals' => 8],
            ['category_slug' => 'bank', 'code' => 'BCA_IDR', 'name' => 'Bank BCA', 'symbol' => 'BCA', 'decimals' => 0],
            ['category_slug' => 'usd', 'code' => 'PAYPAL_USD', 'name' => 'PayPal USD', 'symbol' => 'PP', 'decimals' => 2],
            ['category_slug' => 'pulsa', 'code' => 'TELKOMSEL_PULSA', 'name' => 'Pulsa Telkomsel', 'symbol' => 'Tsel', 'decimals' => 0],
        ];

        $table = $this->db->table('assets');
        $now   = date('Y-m-d H:i:s');

        foreach ($items as $item) {
            $categoryId = $categoryMap[$item['category_slug']] ?? null;
            if ($categoryId === null) {
                continue;
            }

            $exists = $table->where('code', $item['code'])->countAllResults();
            if ($exists > 0) {
                continue;
            }

            $table->insert([
                'category_id' => $categoryId,
                'code'        => strtoupper($item['code']),
                'name'        => $item['name'],
                'symbol'      => $item['symbol'],
                'decimals'    => $item['decimals'],
                'status'      => 'active',
                'sort_order'  => 0,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }
    }
}
