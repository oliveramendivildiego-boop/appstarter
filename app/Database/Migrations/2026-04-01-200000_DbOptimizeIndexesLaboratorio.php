<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Optimización de índices alineada con RegisterModel, ReportModel, AuditoriaModel, etc.
 *
 * - Elimina índices secundarios que solo repiten la columna de la PK (no aportan y duplican el árbol B+).
 * - Añade índices simples y compuestos para JOINs, filtros por fecha y FK lógicas.
 * - FULLTEXT opcional en people (nombres) para búsquedas MATCH...AGAINST si el motor lo permite.
 *
 * Verificar con: database/explain_verification.sql
 */
class DbOptimizeIndexesLaboratorio extends Migration
{
    public function up(): void
    {
        $schema = $this->db->getDatabase();

        foreach ($this->redundantPkDuplicateIndexes() as [$logicalTable, $indexName]) {
            if (! $this->db->tableExists($logicalTable)) {
                continue;
            }
            $physical = $this->db->prefixTable($logicalTable);
            $this->dropIndexIfExists($schema, $physical, $indexName);
        }

        $toAdd = [
            ['registro', 'idx_registro_person_ingreso', '(person_id, ingreso)'],
            ['registro', 'idx_registro_doctor_ingreso', '(doctor_id, ingreso)'],
            ['registro', 'idx_registro_ingreso', '(ingreso)'],
            ['regvalues', 'idx_regvalues_registro_ord', '(registro_id, regvalues_id)'],
            ['pago', 'idx_pago_registro_id', '(registro_id)'],
            ['resulanalisis', 'idx_resulanalisis_registro_id', '(registro_id)'],
            ['prianacategoria', 'idx_prianacategoria_anacat_deleted', '(anacategoria_id, deleted)'],
            ['secanacategoria', 'idx_secanacategoria_pria_deleted', '(prianacategoria_id, deleted)'],
            ['priresultados', 'idx_priresultados_pria_pob_del', '(prianacategoria_id, id_poblacion, deleted)'],
            ['people', 'idx_people_last_name_fa', '(last_name_fa(100))'],
            ['doctors', 'idx_doctors_deleted', '(deleted)'],
        ];

        foreach ($toAdd as [$logicalTable, $indexName, $cols]) {
            if (! $this->db->tableExists($logicalTable)) {
                continue;
            }
            $physical = $this->db->prefixTable($logicalTable);
            $this->addIndexIfNotExists($schema, $physical, $indexName, $cols);
        }

        if ($this->db->tableExists('registro')) {
            $physical = $this->db->prefixTable('registro');
            $fields   = $this->db->getFieldNames($physical);
            if (in_array('anulado', $fields, true)) {
                $this->addIndexIfNotExists($schema, $physical, 'idx_registro_anulado_ingreso', '(anulado, ingreso)');
            }
        }

        if ($this->db->tableExists('muestra')) {
            $physical = $this->db->prefixTable('muestra');
            $fields   = $this->db->getFieldNames($physical);
            if (in_array('registro_id', $fields, true)) {
                $this->addIndexIfNotExists($schema, $physical, 'idx_muestra_registro_id', '(registro_id)');
            }
        }

        if ($this->db->tableExists('doctor_commissions')) {
            $physical = $this->db->prefixTable('doctor_commissions');
            $this->addIndexIfNotExists($schema, $physical, 'idx_dc_doctor_status', '(doctor_id, status)');
            $this->addIndexIfNotExists($schema, $physical, 'idx_dc_registro_id', '(registro_id)');
        }

        if ($this->db->tableExists('auditoria')) {
            $physical = $this->db->prefixTable('auditoria');
            $fields   = $this->db->getFieldNames($physical);
            if (in_array('fecha', $fields, true)) {
                $this->addIndexIfNotExists($schema, $physical, 'idx_auditoria_fecha', '(fecha)');
                $this->addIndexIfNotExists($schema, $physical, 'idx_auditoria_modulo_fecha', '(modulo(64), fecha)');
            }
            if (in_array('person_id', $fields, true)) {
                $this->addIndexIfNotExists($schema, $physical, 'idx_auditoria_person_id', '(person_id)');
            }
        }

        if ($this->db->tableExists('leyendas')) {
            $physical = $this->db->prefixTable('leyendas');
            $fields   = $this->db->getFieldNames($physical);
            if (in_array('deleted', $fields, true)) {
                $this->addIndexIfNotExists($schema, $physical, 'idx_leyendas_deleted', '(deleted)');
            }
        }

        if ($this->db->tableExists('control_valor')) {
            $physical = $this->db->prefixTable('control_valor');
            $fields   = $this->db->getFieldNames($physical);
            if (in_array('control_id', $fields, true) && in_array('fecha', $fields, true)) {
                $this->addIndexIfNotExists($schema, $physical, 'idx_control_valor_ctrl_fecha', '(control_id, fecha)');
            }
        }

        if ($this->db->tableExists('toquotelogs')) {
            $physical = $this->db->prefixTable('toquotelogs');
            $fields   = $this->db->getFieldNames($physical);
            foreach (['created_at', 'fecha', 'log_date', 'timestamp'] as $cand) {
                if (in_array($cand, $fields, true)) {
                    $this->addIndexIfNotExists($schema, $physical, 'idx_toquotelogs_time', '(' . $cand . ')');
                    break;
                }
            }
        }

        $this->tryAddFulltextPeople($schema);
    }

    public function down(): void
    {
        $schema = $this->db->getDatabase();

        $added = [
            ['registro', 'idx_registro_person_ingreso'],
            ['registro', 'idx_registro_doctor_ingreso'],
            ['registro', 'idx_registro_ingreso'],
            ['registro', 'idx_registro_anulado_ingreso'],
            ['regvalues', 'idx_regvalues_registro_ord'],
            ['pago', 'idx_pago_registro_id'],
            ['resulanalisis', 'idx_resulanalisis_registro_id'],
            ['prianacategoria', 'idx_prianacategoria_anacat_deleted'],
            ['secanacategoria', 'idx_secanacategoria_pria_deleted'],
            ['priresultados', 'idx_priresultados_pria_pob_del'],
            ['people', 'idx_people_last_name_fa'],
            ['doctors', 'idx_doctors_deleted'],
            ['muestra', 'idx_muestra_registro_id'],
            ['doctor_commissions', 'idx_dc_doctor_status'],
            ['doctor_commissions', 'idx_dc_registro_id'],
            ['auditoria', 'idx_auditoria_fecha'],
            ['auditoria', 'idx_auditoria_modulo_fecha'],
            ['auditoria', 'idx_auditoria_person_id'],
            ['leyendas', 'idx_leyendas_deleted'],
            ['control_valor', 'idx_control_valor_ctrl_fecha'],
            ['toquotelogs', 'idx_toquotelogs_time'],
        ];

        foreach ($added as [$logicalTable, $indexName]) {
            if (! $this->db->tableExists($logicalTable)) {
                continue;
            }
            $this->dropIndexIfExists($schema, $this->db->prefixTable($logicalTable), $indexName);
        }

        if ($this->db->tableExists('people')) {
            $this->dropIndexIfExists($schema, $this->db->prefixTable('people'), 'ft_people_nombres');
        }
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function redundantPkDuplicateIndexes(): array
    {
        return [
            ['registro', 'registro_id'],
            ['regvalues', 'regvalues_id'],
            ['pago', 'pago_id'],
            ['resulanalisis', 'resulanalisis_id'],
        ];
    }

    private function indexExists(string $schema, string $prefixedTable, string $indexName): bool
    {
        $sql = 'SELECT 1 FROM information_schema.statistics
            WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1';
        $row = $this->db->query($sql, [$schema, $prefixedTable, $indexName])->getRow();

        return $row !== null;
    }

    private function dropIndexIfExists(string $schema, string $prefixedTable, string $indexName): void
    {
        if ($indexName === 'PRIMARY' || ! $this->indexExists($schema, $prefixedTable, $indexName)) {
            return;
        }
        try {
            $this->db->query("ALTER TABLE `{$prefixedTable}` DROP INDEX `{$indexName}`");
        } catch (\Throwable $e) {
            log_message('debug', 'DbOptimizeIndexes: no se pudo eliminar índice ' . $indexName . ': ' . $e->getMessage());
        }
    }

    private function addIndexIfNotExists(string $schema, string $prefixedTable, string $indexName, string $columnsWithParens): void
    {
        if ($this->indexExists($schema, $prefixedTable, $indexName)) {
            return;
        }
        try {
            $this->db->query("ALTER TABLE `{$prefixedTable}` ADD INDEX `{$indexName}` {$columnsWithParens}");
        } catch (\Throwable $e) {
            log_message('warning', 'DbOptimizeIndexes: no se pudo crear índice ' . $indexName . ': ' . $e->getMessage());
        }
    }

    private function tryAddFulltextPeople(string $schema): void
    {
        if (! $this->db->tableExists('people')) {
            return;
        }
        $physical = $this->db->prefixTable('people');
        if ($this->indexExists($schema, $physical, 'ft_people_nombres')) {
            return;
        }
        try {
            $this->db->query("ALTER TABLE `{$physical}` ADD FULLTEXT KEY `ft_people_nombres` (first_name, last_name_fa, last_name_mom)");
        } catch (\Throwable $e) {
            log_message('notice', 'DbOptimizeIndexes: FULLTEXT en people omitido (motor, versión o tipo de columna): ' . $e->getMessage());
        }
    }
}
