<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOrderFlowEnhancements extends Migration
{
    public function up(): void
    {
        $fields = [];
        if (! $this->db->fieldExists('idempotency_key', 'orders')) {
            $fields['idempotency_key'] = [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
                'after' => 'payment_method_id',
            ];
        }
        if (! $this->db->fieldExists('proof_status', 'orders')) {
            $fields['proof_status'] = [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'none',
                'after' => 'proof_uploaded_at',
            ];
        }
        if (! $this->db->fieldExists('proof_reviewed_at', 'orders')) {
            $fields['proof_reviewed_at'] = [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'proof_status',
            ];
        }
        if (! $this->db->fieldExists('proof_reviewed_by', 'orders')) {
            $fields['proof_reviewed_by'] = [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'null' => true,
                'after' => 'proof_reviewed_at',
            ];
        }
        if (! $this->db->fieldExists('sla_escalated_at', 'orders')) {
            $fields['sla_escalated_at'] = [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'sla_due_at',
            ];
        }
        if ($fields !== []) {
            $this->forge->addColumn('orders', $fields);
        }
        if (! $this->db->tableExists('asset_ledger_entries')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
                'asset_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
                'order_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
                'entry_type' => ['type' => 'VARCHAR', 'constraint' => 20],
                'amount' => ['type' => 'DECIMAL', 'constraint' => '30,8'],
                'notes' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'created_by' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addKey('asset_id', false, false, 'idx_ledger_asset');
            $this->forge->addKey('order_id', false, false, 'idx_ledger_order');
            $this->forge->createTable('asset_ledger_entries', true);
        }
    }

    public function down(): void
    {
        foreach (['sla_escalated_at', 'proof_reviewed_by', 'proof_reviewed_at', 'proof_status', 'idempotency_key'] as $column) {
            if ($this->db->fieldExists($column, 'orders')) {
                $this->forge->dropColumn('orders', $column);
            }
        }
        if ($this->db->tableExists('asset_ledger_entries')) {
            $this->forge->dropTable('asset_ledger_entries', true);
        }
    }
}
