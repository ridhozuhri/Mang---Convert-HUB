<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePairRateHistory extends Migration
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
            'pair_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'rate' => [
                'type'       => 'DECIMAL',
                'constraint' => '30,12',
            ],
            'changed_by' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'change_reason' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('pair_id', false, false, 'idx_prh_pair');
        $this->forge->addKey('changed_by', false, false, 'idx_prh_changer');
        $this->forge->addKey('created_at', false, false, 'idx_prh_created');
        $this->forge->createTable('pair_rate_history', true);

        $this->db->query('ALTER TABLE `pair_rate_history`
            ADD CONSTRAINT `fk_prh_pair`
            FOREIGN KEY (`pair_id`) REFERENCES `pairs` (`id`)');
        $this->db->query('ALTER TABLE `pair_rate_history`
            ADD CONSTRAINT `fk_prh_user`
            FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`)');
    }

    public function down(): void
    {
        $this->forge->dropTable('pair_rate_history', true);
    }
}
