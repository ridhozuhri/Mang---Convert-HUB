<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddServiceControlToOrders extends Migration
{
    public function up(): void
    {
        $fields = [];
        if (! $this->db->fieldExists('service_rules_snapshot', 'orders')) {
            $fields['service_rules_snapshot'] = [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'payment_method_id',
            ];
        }
        if (! $this->db->fieldExists('customer_sender_account', 'orders')) {
            $fields['customer_sender_account'] = [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'after' => 'service_rules_snapshot',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('orders', $fields);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('customer_sender_account', 'orders')) {
            $this->forge->dropColumn('orders', 'customer_sender_account');
        }
        if ($this->db->fieldExists('service_rules_snapshot', 'orders')) {
            $this->forge->dropColumn('orders', 'service_rules_snapshot');
        }
    }
}
