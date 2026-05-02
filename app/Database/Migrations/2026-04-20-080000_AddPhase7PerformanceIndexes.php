<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPhase7PerformanceIndexes extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('orders', 'idx_orders_status_created_id', '(`status`, `created_at`, `id`)');
        $this->addIndexIfMissing('audit_logs', 'idx_al_action_created_id', '(`action`, `created_at`, `id`)');
        $this->addIndexIfMissing('pair_rate_history', 'idx_prh_pair_created', '(`pair_id`, `created_at`)');
        $this->addIndexIfMissing('notifications', 'idx_notif_user_read_created', '(`user_id`, `is_read`, `created_at`)');
        $this->addIndexIfMissing('login_attempts', 'idx_la_email_ip_success_created', '(`email`, `ip_address`, `is_success`, `created_at`)');
    }

    public function down(): void
    {
        $this->dropIndexIfExists('orders', 'idx_orders_status_created_id');
        $this->dropIndexIfExists('audit_logs', 'idx_al_action_created_id');
        $this->dropIndexIfExists('pair_rate_history', 'idx_prh_pair_created');
        $this->dropIndexIfExists('notifications', 'idx_notif_user_read_created');
        $this->dropIndexIfExists('login_attempts', 'idx_la_email_ip_success_created');
    }

    private function addIndexIfMissing(string $table, string $indexName, string $columnsSql): void
    {
        $row = $this->db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$indexName}'")->getRowArray();
        if (is_array($row)) {
            return;
        }

        $this->db->query("ALTER TABLE `{$table}` ADD INDEX `{$indexName}` {$columnsSql}");
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $row = $this->db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$indexName}'")->getRowArray();
        if (! is_array($row)) {
            return;
        }

        $this->db->query("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
    }
}
