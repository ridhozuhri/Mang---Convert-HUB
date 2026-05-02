<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['group' => 'general', 'key' => 'site_name', 'value' => 'ConvertHub', 'type' => 'string', 'label' => 'Nama Situs'],
            ['group' => 'general', 'key' => 'site_url', 'value' => '', 'type' => 'string', 'label' => 'URL Situs'],
            ['group' => 'general', 'key' => 'timezone', 'value' => 'Asia/Jakarta', 'type' => 'string', 'label' => 'Timezone'],
            ['group' => 'smtp', 'key' => 'host', 'value' => 'smtp.mailtrap.io', 'type' => 'string', 'label' => 'SMTP Host'],
            ['group' => 'smtp', 'key' => 'port', 'value' => '587', 'type' => 'int', 'label' => 'SMTP Port'],
            ['group' => 'smtp', 'key' => 'username', 'value' => '', 'type' => 'string', 'label' => 'Username'],
            ['group' => 'smtp', 'key' => 'password', 'value' => '', 'type' => 'string', 'label' => 'Password'],
            ['group' => 'smtp', 'key' => 'from_email', 'value' => '', 'type' => 'string', 'label' => 'From Email'],
            ['group' => 'smtp', 'key' => 'encryption', 'value' => 'tls', 'type' => 'string', 'label' => 'Enkripsi'],
            ['group' => 'security', 'key' => 'max_login_attempts', 'value' => '5', 'type' => 'int', 'label' => 'Maks Percobaan Login'],
            ['group' => 'security', 'key' => 'lockout_minutes', 'value' => '15', 'type' => 'int', 'label' => 'Durasi Lockout (menit)'],
            ['group' => 'security', 'key' => 'session_timeout', 'value' => '120', 'type' => 'int', 'label' => 'Timeout Sesi (menit)'],
            ['group' => 'security', 'key' => 'two_factor_admin', 'value' => '0', 'type' => 'bool', 'label' => 'Wajib 2FA untuk Admin'],
            ['group' => 'converter', 'key' => 'quote_token_secret', 'value' => bin2hex(random_bytes(32)), 'type' => 'string', 'label' => 'Quote Token Secret'],
            ['group' => 'converter', 'key' => 'quote_token_expiry', 'value' => '300', 'type' => 'int', 'label' => 'Expiry Quote Token (detik)'],
            ['group' => 'converter', 'key' => 'show_rate_indicator', 'value' => '1', 'type' => 'bool', 'label' => 'Tampilkan Indikator Perubahan Rate'],
            ['group' => 'system', 'key' => 'maintenance_mode', 'value' => '0', 'type' => 'bool', 'label' => 'Mode Maintenance'],
            ['group' => 'system', 'key' => 'maintenance_message', 'value' => 'Sistem sedang dalam pemeliharaan. Coba lagi nanti.', 'type' => 'string', 'label' => 'Pesan Maintenance'],
        ];

        $table = $this->db->table('settings');
        $now   = date('Y-m-d H:i:s');

        foreach ($settings as $row) {
            $exists = $table->where('group', $row['group'])->where('key', $row['key'])->countAllResults();
            if ($exists > 0) {
                continue;
            }

            $row['created_at'] = $now;
            $row['updated_at'] = $now;
            $table->insert($row);
        }
    }
}
