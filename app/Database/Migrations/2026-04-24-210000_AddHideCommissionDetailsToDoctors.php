<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHideCommissionDetailsToDoctors extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('hide_commission_details', 'doctors')) {
            $this->forge->addColumn('doctors', [
                'hide_commission_details' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'null'       => false,
                    'comment'    => '1=oculta el detalle de comisiones en el portal del doctor',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('hide_commission_details', 'doctors')) {
            $this->forge->dropColumn('doctors', 'hide_commission_details');
        }
    }
}
