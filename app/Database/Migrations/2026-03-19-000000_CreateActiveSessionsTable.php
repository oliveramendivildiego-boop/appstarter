<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateActiveSessionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'session_id' => [
                'type'           => 'VARCHAR',
                'constraint'     => 255,
                'primary_key'    => true,
            ],
            'person_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => false,
            ],
            'username' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'login_time' => [
                'type'       => 'DATETIME',
                'null'       => false,
            ],
            'last_activity' => [
                'type'       => 'DATETIME',
                'null'       => false,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
            ],
            'user_agent' => [
                'type'       => 'TEXT',
                'null'       => true,
            ],
        ]);

        $this->forge->addKey('person_id');
        $this->forge->addKey('login_time');
        $this->forge->createTable('active_sessions');
    }

    public function down()
    {
        $this->forge->dropTable('active_sessions');
    }
}
