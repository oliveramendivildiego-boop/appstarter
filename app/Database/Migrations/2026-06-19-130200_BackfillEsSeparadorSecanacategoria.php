<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Marca filas título históricas como separadores (es_separador=1).
 * Requiere columna es_separador (migración 2026-04-04-130000).
 */
class BackfillEsSeparadorSecanacategoria extends Migration
{
    public function up(): void
    {
        $table = $this->db->prefixTable('secanacategoria');
        if (! $this->db->tableExists($table) || ! $this->db->fieldExists('es_separador', $table)) {
            return;
        }

        $this->db->query("
            UPDATE {$table}
            SET es_separador = 1
            WHERE (deleted = 0 OR deleted IS NULL)
              AND es_separador = 0
              AND opcion_id = 3
              AND formulas_id = 1
              AND (valor_min IS NULL OR TRIM(valor_min) = '')
              AND (valor_max IS NULL OR TRIM(valor_max) = '')
              AND (
                    UPPER(nombre) LIKE '%MUESTRA%'
                 OR UPPER(nombre) LIKE '%MUETRA%'
                 OR UPPER(nombre) LIKE 'EXAMEN %'
              )
        ");
    }

    public function down(): void
    {
        $table = $this->db->prefixTable('secanacategoria');
        if (! $this->db->tableExists($table) || ! $this->db->fieldExists('es_separador', $table)) {
            return;
        }

        $this->db->query("
            UPDATE {$table}
            SET es_separador = 0
            WHERE es_separador = 1
              AND opcion_id = 3
              AND formulas_id = 1
              AND (valor_min IS NULL OR TRIM(valor_min) = '')
              AND (valor_max IS NULL OR TRIM(valor_max) = '')
              AND (
                    UPPER(nombre) LIKE '%MUESTRA%'
                 OR UPPER(nombre) LIKE '%MUETRA%'
                 OR UPPER(nombre) LIKE 'EXAMEN %'
              )
        ");
    }
}
