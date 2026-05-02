<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOrderOpsControlFields extends Migration
{
    public function up(): void
    {
        $fields = [];
        if (! $this->db->fieldExists('locked_by', 'orders')) {
            $fields['locked_by'] = [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'null' => true,
                'after' => 'completed_at',
            ];
        }
        if (! $this->db->fieldExists('locked_at', 'orders')) {
            $fields['locked_at'] = [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'locked_by',
            ];
        }
        if (! $this->db->fieldExists('sla_due_at', 'orders')) {
            $fields['sla_due_at'] = [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'locked_at',
            ];
        }
        if ($fields !== []) {
            $this->forge->addColumn('orders', $fields);
        }
    }

    public function down(): void
    {
        foreach (['sla_due_at', 'locked_at', 'locked_by'] as $field) {
            if ($this->db->fieldExists($field, 'orders')) {
                $this->forge->dropColumn('orders', $field);
            }
        }
    }
}
