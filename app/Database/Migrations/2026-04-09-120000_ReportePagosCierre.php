<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ReportePagosCierre extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('reporte_pagos_cierre')) {
            return;
        }

        $this->forge->addField([
            'cierre_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'fecha_desde' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'fecha_hasta' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'snapshot_json' => [
                'type' => 'LONGTEXT',
                'null' => false,
            ],
            'person_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'elaborado_nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);
        $this->forge->addKey('cierre_id', true);
        $this->forge->addKey('created_at');
        $this->forge->createTable('reporte_pagos_cierre', true);
    }

    public function down()
    {
        if ($this->db->tableExists('reporte_pagos_cierre')) {
            $this->forge->dropTable('reporte_pagos_cierre', true);
        }
    }
}
