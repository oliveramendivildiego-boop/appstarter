<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Elimina comisiones duplicadas por orden y evita que se vuelvan a crear.
 * Corrige listados con cientos de filas repetidas del mismo registro_id.
 */
class CleanDuplicateDoctorCommissions extends Migration
{
    public function up(): void
    {
        $table = $this->db->prefixTable('doctor_commissions');
        if (! $this->db->tableExists('doctor_commissions')) {
            return;
        }

        // 1) Pendientes duplicados: conservar la comisión con mayor commission_id.
        $this->db->query("
            DELETE c1 FROM {$table} c1
            INNER JOIN {$table} c2
                ON c1.registro_id = c2.registro_id
                AND c1.status = 0
                AND c2.status = 0
                AND c1.commission_id < c2.commission_id
        ");

        // 2) Cualquier otro duplicado por orden (distinto estado): una fila por registro_id.
        $this->db->query("
            DELETE c1 FROM {$table} c1
            INNER JOIN {$table} c2
                ON c1.registro_id = c2.registro_id
                AND c1.commission_id < c2.commission_id
        ");

        // 3) Índice único para que la base rechace duplicados futuros.
        if (! $this->hasUniqueIndexOnRegistroId($table)) {
            $this->db->query("ALTER TABLE {$table} ADD UNIQUE KEY uk_dc_registro_id (registro_id)");
        }
    }

    public function down(): void
    {
        $table = $this->db->prefixTable('doctor_commissions');
        if (! $this->db->tableExists('doctor_commissions')) {
            return;
        }

        if ($this->hasUniqueIndexOnRegistroId($table)) {
            $this->db->query("ALTER TABLE {$table} DROP INDEX uk_dc_registro_id");
        }
    }

    private function hasUniqueIndexOnRegistroId(string $table): bool
    {
        $rows = $this->db->query("SHOW INDEX FROM {$table} WHERE Column_name = 'registro_id' AND Non_unique = 0")
            ->getResultArray();

        return $rows !== [];
    }
}
