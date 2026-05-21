<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAddress1ToPeople extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('address_1', 'people')) {
            $this->forge->addColumn('people', [
                'address_1' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'gender',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('address_1', 'people')) {
            $this->forge->dropColumn('people', 'address_1');
        }
    }
}
