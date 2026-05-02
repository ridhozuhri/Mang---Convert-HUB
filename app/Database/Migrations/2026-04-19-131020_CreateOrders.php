<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrders extends Migration
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
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
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
                'null'       => true,
            ],
            'snap_rate' => ['type' => 'DECIMAL', 'constraint' => '30,12'],
            'snap_fee_type' => ['type' => 'VARCHAR', 'constraint' => 10],
            'snap_fee_value' => ['type' => 'DECIMAL', 'constraint' => '20,8'],
            'snap_spread_type' => ['type' => 'VARCHAR', 'constraint' => 10],
            'snap_spread_value' => ['type' => 'DECIMAL', 'constraint' => '20,8'],
            'snap_min_amount' => ['type' => 'DECIMAL', 'constraint' => '30,8'],
            'snap_max_amount' => ['type' => 'DECIMAL', 'constraint' => '30,8'],
            'amount_sent' => ['type' => 'DECIMAL', 'constraint' => '30,8'],
            'amount_received' => ['type' => 'DECIMAL', 'constraint' => '30,8'],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'pending',
            ],
            'proof_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'proof_uploaded_at' => ['type' => 'DATETIME', 'null' => true],
            'admin_note' => ['type' => 'TEXT', 'null' => true],
            'cancelled_reason' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'expires_at' => ['type' => 'DATETIME', 'null' => true],
            'completed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code', 'uq_orders_code');
        $this->forge->addKey('user_id', false, false, 'idx_orders_user');
        $this->forge->addKey('pair_id', false, false, 'idx_orders_pair');
        $this->forge->addKey('status', false, false, 'idx_orders_status');
        $this->forge->addKey('created_at', false, false, 'idx_orders_created');
        $this->forge->createTable('orders', true);

        $this->db->query('ALTER TABLE `orders`
            ADD CONSTRAINT `fk_orders_user`
            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)');
        $this->db->query('ALTER TABLE `orders`
            ADD CONSTRAINT `fk_orders_pair`
            FOREIGN KEY (`pair_id`) REFERENCES `pairs` (`id`)');
        $this->db->query('ALTER TABLE `orders`
            ADD CONSTRAINT `fk_orders_pm`
            FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`)');
    }

    public function down(): void
    {
        $this->forge->dropTable('orders', true);
    }
}
