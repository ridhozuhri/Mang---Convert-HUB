<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Semua', 'slug' => 'semua', 'sort_order' => 0, 'is_active' => 1],
            ['name' => 'USD', 'slug' => 'usd', 'sort_order' => 1, 'is_active' => 1],
            ['name' => 'Coin', 'slug' => 'coin', 'sort_order' => 2, 'is_active' => 1],
            ['name' => 'Bank', 'slug' => 'bank', 'sort_order' => 3, 'is_active' => 1],
            ['name' => 'Pulsa', 'slug' => 'pulsa', 'sort_order' => 4, 'is_active' => 1],
            ['name' => 'Stable', 'slug' => 'stable', 'sort_order' => 5, 'is_active' => 1],
        ];

        $table = $this->db->table('categories');
        $now   = date('Y-m-d H:i:s');

        foreach ($items as $item) {
            $exists = $table->where('slug', $item['slug'])->countAllResults();
            if ($exists > 0) {
                continue;
            }

            $item['created_at'] = $now;
            $item['updated_at'] = $now;
            $table->insert($item);
        }
    }
}
