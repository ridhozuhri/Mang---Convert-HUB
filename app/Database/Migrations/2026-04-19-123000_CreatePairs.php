<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePairs extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'from_asset_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'to_asset_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'sort_order' => ['type' => 'INT', 'default' => 0],
            'min_amount' => ['type' => 'DECIMAL', 'constraint' => '30,8', 'default' => '0.00000000'],
            'max_amount' => ['type' => 'DECIMAL', 'constraint' => '30,8', 'default' => '0.00000000'],
            'fee_type' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'fixed'],
            'fee_value' => ['type' => 'DECIMAL', 'constraint' => '20,8', 'default' => '0.00000000'],
            'spread_type' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'none'],
            'spread_value' => ['type' => 'DECIMAL', 'constraint' => '20,8', 'default' => '0.00000000'],
            'rounding_mode' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'floor'],
            'rounding_precision' => ['type' => 'TINYINT', 'default' => 2],
            'rate_mode' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'manual'],
            'last_rate' => ['type' => 'DECIMAL', 'constraint' => '30,12', 'default' => '0.000000000000'],
            'last_rate_updated_at' => ['type' => 'DATETIME', 'null' => true],
            'last_rate_updated_by' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['from_asset_id', 'to_asset_id'], 'uq_pairs_from_to');
        $this->forge->addKey('status', false, false, 'idx_pairs_status');
        $this->forge->createTable('pairs', true);

        $this->db->query('ALTER TABLE `pairs`
            ADD CONSTRAINT `fk_pairs_from`
            FOREIGN KEY (`from_asset_id`) REFERENCES `assets` (`id`)');
        $this->db->query('ALTER TABLE `pairs`
            ADD CONSTRAINT `fk_pairs_to`
            FOREIGN KEY (`to_asset_id`) REFERENCES `assets` (`id`)');
        $this->db->query('ALTER TABLE `pairs`
            ADD CONSTRAINT `fk_pairs_updater`
            FOREIGN KEY (`last_rate_updated_by`) REFERENCES `users` (`id`)');
    }

    public function down(): void
    {
        $this->forge->dropTable('pairs', true);
    }
}
