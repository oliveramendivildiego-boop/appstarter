<?php

namespace App\Database\Migrations;

use App\Services\ValoresReferenciaInstaller;
use CodeIgniter\Database\Migration;

/**
 * Datos: valores de referencia por grupo poblacional (dom_poblacion 6–12),
 * normalización de id_poblacion legacy en dom_priresultados y ajustes en
 * dom_secanacategoria del hemograma (prianacategoria_id = 2).
 *
 * La lógica coincide con Config\ValoresReferenciaCatalog + ValoresReferenciaBandas
 * y con php spark lab:apply-valores-referencia.
 */
class ApplyValoresReferenciaPorPoblacion extends Migration
{
    public function up()
    {
        $pri = $this->db->prefixTable('priresultados');
        if (! $this->db->tableExists($pri)) {
            return;
        }

        $this->db->transStart();
        ValoresReferenciaInstaller::apply($this->db);
        $this->db->transComplete();
    }

    /**
     * No revierte inserciones ni el soft-delete de filas previas en priresultados.
     */
    public function down()
    {
    }
}
