<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTipoMovimientoToEgresos extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('egresos')) {
            return;
        }

        $fields = $this->db->getFieldNames($this->db->prefixTable('egresos'));
        if (!in_array('tipo_movimiento', $fields, true)) {
            $this->forge->addColumn('egresos', [
                'tipo_movimiento' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => false,
                    'default' => 'egreso',
                    'after' => 'tipopago',
                ],
            ]);
        }

        // Registros existentes quedan como egreso.
        $this->db->table('egresos')
            ->where('tipo_movimiento IS NULL', null, false)
            ->orWhere('TRIM(tipo_movimiento) =', '')
            ->set(['tipo_movimiento' => 'egreso'])
            ->update();
    }

    public function down()
    {
        if (!$this->db->tableExists('egresos')) {
            return;
        }
        $fields = $this->db->getFieldNames($this->db->prefixTable('egresos'));
        if (in_array('tipo_movimiento', $fields, true)) {
            $this->forge->dropColumn('egresos', 'tipo_movimiento');
        }
    }
}

