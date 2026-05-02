<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePairPaymentMethods extends Migration
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
            'payment_method_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'rules_json' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['pair_id', 'payment_method_id'], 'uq_ppm_pair_pm');
        $this->forge->createTable('pair_payment_methods', true);

        $this->db->query('ALTER TABLE `pair_payment_methods`
            ADD CONSTRAINT `fk_ppm_pair`
            FOREIGN KEY (`pair_id`) REFERENCES `pairs` (`id`)');
        $this->db->query('ALTER TABLE `pair_payment_methods`
            ADD CONSTRAINT `fk_ppm_pm`
            FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`)');
    }

    public function down(): void
    {
        $this->forge->dropTable('pair_payment_methods', true);
    }
}
