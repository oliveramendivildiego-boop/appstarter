<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Fecha de reporte fijada al imprimir o exportar PDF desde registers (no cambia después).
 */
class RegistroFechaHoraReporteFijada extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('registro')) {
            return;
        }

        $prefixed = $this->db->prefixTable('registro');
        $fields   = $this->db->getFieldNames($prefixed);
        if (in_array('fecha_hora_reporte_fijada', $fields, true)) {
            return;
        }

        $this->forge->addColumn('registro', [
            'fecha_hora_reporte_fijada' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'comment' => 'Momento en que se imprimió o generó PDF del reporte por primera vez (registers)',
            ],
        ]);
    }

    public function down()
    {
        if (! $this->db->tableExists('registro')) {
            return;
        }

        if ($this->db->fieldExists('fecha_hora_reporte_fijada', 'registro')) {
            $this->forge->dropColumn('registro', 'fecha_hora_reporte_fijada');
        }
    }
}
