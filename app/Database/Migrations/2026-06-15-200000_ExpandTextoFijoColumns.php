<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Asegura capacidad amplia para texto fijo en sub-clases y referencias simples.
 * En instalaciones donde la columna existía como VARCHAR/TEXT corto, la amplía a MEDIUMTEXT.
 */
class ExpandTextoFijoColumns extends Migration
{
    public function up(): void
    {
        foreach (['secanacategoria', 'priresultados'] as $tableName) {
            $table = $this->db->prefixTable($tableName);
            if (! $this->db->fieldExists('texto_fijo', $tableName)) {
                continue;
            }
            $this->db->query("ALTER TABLE `{$table}` MODIFY `texto_fijo` MEDIUMTEXT NULL");
        }
    }

    public function down(): void
    {
        foreach (['secanacategoria', 'priresultados'] as $tableName) {
            $table = $this->db->prefixTable($tableName);
            if (! $this->db->fieldExists('texto_fijo', $tableName)) {
                continue;
            }
            $this->db->query("ALTER TABLE `{$table}` MODIFY `texto_fijo` VARCHAR(255) NULL");
        }
    }
}
