<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $table = $this->db->table('users');
        $email = 'admin@converthub.com';

        $exists = $table->where('email', $email)->countAllResults();
        if ($exists > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $table->insert([
            'name'       => 'Super Admin',
            'email'      => $email,
            'password'   => password_hash('Admin123!', PASSWORD_ARGON2ID),
            'role'       => 'admin',
            'is_active'  => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
