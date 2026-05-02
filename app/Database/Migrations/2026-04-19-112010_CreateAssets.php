<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAssets extends Migration
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
            'category_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'symbol' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'decimals' => [
                'type'       => 'TINYINT',
                'default'    => 2,
            ],
            'logo_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'active',
            ],
            'sort_order' => [
                'type'    => 'INT',
                'default' => 0,
            ],
            'metadata_json' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code', 'uq_assets_code');
        $this->forge->addKey('category_id', false, false, 'idx_assets_category');
        $this->forge->addKey('status', false, false, 'idx_assets_status');
        $this->forge->createTable('assets', true);

        $this->db->query('ALTER TABLE `assets`
            ADD CONSTRAINT `fk_assets_category`
            FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)');
    }

    public function down(): void
    {
        $this->forge->dropTable('assets', true);
    }
}
