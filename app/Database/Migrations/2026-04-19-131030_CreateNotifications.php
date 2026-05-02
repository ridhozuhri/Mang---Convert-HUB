<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNotifications extends Migration
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
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'body' => [
                'type' => 'TEXT',
            ],
            'data_json' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'is_read' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'read_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('user_id', false, false, 'idx_notif_user');
        $this->forge->addKey('is_read', false, false, 'idx_notif_is_read');
        $this->forge->addKey('created_at', false, false, 'idx_notif_created');
        $this->forge->createTable('notifications', true);

        $this->db->query('ALTER TABLE `notifications`
            ADD CONSTRAINT `fk_notif_user`
            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)');
    }

    public function down(): void
    {
        $this->forge->dropTable('notifications', true);
    }
}
