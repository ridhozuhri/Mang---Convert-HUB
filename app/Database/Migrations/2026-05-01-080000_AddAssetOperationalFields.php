<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAssetOperationalFields extends Migration
{
    public function up(): void
    {
        $fields = [];

        if (! $this->db->fieldExists('capacity_limit', 'assets')) {
            $fields['capacity_limit'] = [
                'type'       => 'DECIMAL',
                'constraint' => '24,8',
                'null'       => true,
                'after'      => 'sort_order',
            ];
        }

        if (! $this->db->fieldExists('hide_threshold_percent', 'assets')) {
            $fields['hide_threshold_percent'] = [
                'type'       => 'TINYINT',
                'constraint' => 3,
                'default'    => 80,
                'after'      => 'capacity_limit',
            ];
        }

        if (! $this->db->fieldExists('is_auto_hide_enabled', 'assets')) {
            $fields['is_auto_hide_enabled'] = [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'after'      => 'hide_threshold_percent',
            ];
        }

        if (! $this->db->fieldExists('destination_account_type', 'assets')) {
            $fields['destination_account_type'] = [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'after'      => 'is_auto_hide_enabled',
            ];
        }

        if (! $this->db->fieldExists('destination_account_number', 'assets')) {
            $fields['destination_account_number'] = [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
                'after'      => 'destination_account_type',
            ];
        }

        if (! $this->db->fieldExists('destination_account_name', 'assets')) {
            $fields['destination_account_name'] = [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'after'      => 'destination_account_number',
            ];
        }

        if (! $this->db->fieldExists('checkout_instruction', 'assets')) {
            $fields['checkout_instruction'] = [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'destination_account_name',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('assets', $fields);
        }
    }

    public function down(): void
    {
        $columns = [
            'checkout_instruction',
            'destination_account_name',
            'destination_account_number',
            'destination_account_type',
            'is_auto_hide_enabled',
            'hide_threshold_percent',
            'capacity_limit',
        ];

        foreach ($columns as $column) {
            if ($this->db->fieldExists($column, 'assets')) {
                $this->forge->dropColumn('assets', $column);
            }
        }
    }
}
