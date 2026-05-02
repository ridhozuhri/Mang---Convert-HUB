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
        $this->call('PairPaymentMethodSeeder');
        $this->call('SettingSeeder');
    }
}
