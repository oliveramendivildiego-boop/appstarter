<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Inserta valores de referencia del hemograma (prueba compuesta) por grupo poblacional
 * según tablas clínicas estándar (Neonato, Lactante, Niño, Adulto M/F, Adulto mayor).
 *
 * - Solo INSERT (no borra ni actualiza filas existentes; no toca regvalues).
 * - Requiere dom_poblacion 6–12 (migration_poblacion_edad / insert_poblaciones_edad).
 * - Ajuste HEM_PRIA si su hemograma no usa prianacategoria_id = 2.
 *
 * @see database/insert_poblaciones_edad.sql
 * @see database/migrate_hemograma_refs_poblacion.sql (mismo lote vía SQL / phpMyAdmin)
 */
class InsertHemogramaReferenciasPorPoblacion extends Migration
{
    private const HEM_PRIA = 2;

    /** dom_poblacion: 6 RN, 7 Lactante, 8–9 Niño, 10 Adolescente, 11 Adulto, 12 Adulto mayor */
    private const P_RN = 6;

    private const P_LACT = 7;

    private const P_NINO_PEQ = 8;

    private const P_NINO_ESC = 9;

    private const P_ADOL = 10;

    private const P_ADULTO = 11;

    private const P_MAYOR = 12;

    public function up()
    {
        $table = $this->db->prefixTable('secanacategoria');
        if (! $this->db->tableExists($table)) {
            return;
        }

        $hasSexo = $this->db->fieldExists('sexo', $table);
        $hasOrden = $this->db->fieldExists('orden', $table);

        if (! $hasOrden) {
            return;
        }

        // Evitar duplicar el lote completo si ya se aplicó (p. ej. re-ejecutar migrate tras borrar solo la fila en migrations).
        $n = (int) $this->db->table('secanacategoria')
            ->where('prianacategoria_id', self::HEM_PRIA)
            ->where('orden >=', 2000)
            ->where('deleted', 0)
            ->countAllResults();
        if ($n >= 75) {
            return;
        }

        $base = [
            'prianacategoria_id' => self::HEM_PRIA,
            'formulas_id'        => 1,
            'opcion_id'          => 3,
            'deleted'            => 0,
        ];
        if ($hasSexo) {
            $base['sexo'] = 'ambos';
        }
        $base['orden'] = 0;

        $rows = [];
        $ord = 2000;

        $push = function (
            int $pob,
            string $nombre,
            string $min,
            string $max,
            string $u,
            string $sexo = 'ambos'
        ) use (&$rows, $base, $hasSexo, &$ord): void {
            $r = $base;
            $r['paciente_id'] = $pob;
            $r['nombre'] = $nombre;
            $r['valor_min'] = $min;
            $r['valor_max'] = $max;
            $r['umedida'] = $u;
            if ($hasSexo) {
                $r['sexo'] = $sexo;
            }
            $r['orden'] = $ord++;
            $rows[] = $r;
        };

        // —— Neonato (0–28 días) ——
        $push(self::P_RN, 'Eritrocitos', '4.0', '6.0', 'mill/µL');
        $push(self::P_RN, 'Hemoglobina', '16', '22', 'g/dL');
        $push(self::P_RN, 'Hematocrito', '50', '65', '%');
        $push(self::P_RN, 'VCM', '95', '120', 'fL');
        $push(self::P_RN, 'Leucocitos', '9000', '30000', '/µL');
        $push(self::P_RN, 'Neutrófilos', '50', '70', '%');
        $push(self::P_RN, 'Linfocitos', '20', '40', '%');
        $push(self::P_RN, 'Plaquetas', '150000', '400000', '/µL');

        // —— Lactante (1 mes – 2 años) ——
        $push(self::P_LACT, 'Eritrocitos', '3.1', '4.5', 'mill/µL');
        $push(self::P_LACT, 'Hemoglobina', '10', '13', 'g/dL');
        $push(self::P_LACT, 'Hematocrito', '30', '40', '%');
        $push(self::P_LACT, 'VCM', '70', '90', 'fL');
        $push(self::P_LACT, 'Leucocitos', '6000', '17000', '/µL');
        $push(self::P_LACT, 'Linfocitos', '50', '70', '%');
        $push(self::P_LACT, 'Neutrófilos', '20', '40', '%');
        $push(self::P_LACT, 'Plaquetas', '150000', '400000', '/µL');

        // —— Niño (2–12 años): mismo rango en 8 y 9 ——
        foreach ([self::P_NINO_PEQ, self::P_NINO_ESC, self::P_ADOL] as $pNi) {
            $push($pNi, 'Eritrocitos', '4.0', '5.2', 'mill/µL');
            $push($pNi, 'Hemoglobina', '11.5', '15', 'g/dL');
            $push($pNi, 'Hematocrito', '35', '45', '%');
            $push($pNi, 'VCM', '75', '90', 'fL');
            $push($pNi, 'Leucocitos', '5000', '14500', '/µL');
            $push($pNi, 'Neutrófilos', '40', '60', '%');
            $push($pNi, 'Linfocitos', '30', '50', '%');
            $push($pNi, 'Plaquetas', '150000', '400000', '/µL');
        }

        // —— Adulto masculino ——
        $push(self::P_ADULTO, 'Eritrocitos', '4.5', '5.9', 'mill/µL', 'masculino');
        $push(self::P_ADULTO, 'Hemoglobina', '13.5', '17.5', 'g/dL', 'masculino');
        $push(self::P_ADULTO, 'Hematocrito', '41', '53', '%', 'masculino');
        $push(self::P_ADULTO, 'VCM', '80', '100', 'fL', 'masculino');
        $push(self::P_ADULTO, 'HCM', '27', '33', 'pg', 'masculino');
        $push(self::P_ADULTO, 'CHCM', '32', '36', 'g/dL', 'masculino');
        $push(self::P_ADULTO, 'RDW', '11.5', '14.5', '%', 'masculino');
        $push(self::P_ADULTO, 'Leucocitos', '4000', '11000', '/µL', 'masculino');
        $push(self::P_ADULTO, 'Neutrófilos', '40', '70', '%', 'masculino');
        $push(self::P_ADULTO, 'Linfocitos', '20', '40', '%', 'masculino');
        $push(self::P_ADULTO, 'Monocitos', '2', '8', '%', 'masculino');
        $push(self::P_ADULTO, 'Eosinófilos', '1', '4', '%', 'masculino');
        $push(self::P_ADULTO, 'Basófilos', '0', '1', '%', 'masculino');
        $push(self::P_ADULTO, 'Plaquetas', '150000', '400000', '/µL', 'masculino');
        $push(self::P_ADULTO, 'VPM', '7.5', '11.5', 'fL', 'masculino');

        // —— Adulto femenino ——
        $push(self::P_ADULTO, 'Eritrocitos', '4.0', '5.2', 'mill/µL', 'femenino');
        $push(self::P_ADULTO, 'Hemoglobina', '12.0', '16.0', 'g/dL', 'femenino');
        $push(self::P_ADULTO, 'Hematocrito', '36', '46', '%', 'femenino');
        $push(self::P_ADULTO, 'VCM', '80', '100', 'fL', 'femenino');
        $push(self::P_ADULTO, 'HCM', '27', '33', 'pg', 'femenino');
        $push(self::P_ADULTO, 'CHCM', '32', '36', 'g/dL', 'femenino');
        $push(self::P_ADULTO, 'RDW', '11.5', '14.5', '%', 'femenino');
        $push(self::P_ADULTO, 'Leucocitos', '4000', '11000', '/µL', 'femenino');
        $push(self::P_ADULTO, 'Neutrófilos', '40', '70', '%', 'femenino');
        $push(self::P_ADULTO, 'Linfocitos', '20', '40', '%', 'femenino');
        $push(self::P_ADULTO, 'Monocitos', '2', '8', '%', 'femenino');
        $push(self::P_ADULTO, 'Eosinófilos', '1', '4', '%', 'femenino');
        $push(self::P_ADULTO, 'Basófilos', '0', '1', '%', 'femenino');
        $push(self::P_ADULTO, 'Plaquetas', '150000', '400000', '/µL', 'femenino');
        $push(self::P_ADULTO, 'VPM', '7.5', '11.5', 'fL', 'femenino');

        // —— Adulto mayor (>60 años), sexo ambos ——
        $push(self::P_MAYOR, 'Eritrocitos', '4.0', '5.5', 'mill/µL');
        $push(self::P_MAYOR, 'Hemoglobina', '11.5', '16.5', 'g/dL');
        $push(self::P_MAYOR, 'Hematocrito', '35', '50', '%');
        $push(self::P_MAYOR, 'Leucocitos', '4000', '10000', '/µL');
        $push(self::P_MAYOR, 'Plaquetas', '150000', '400000', '/µL');

        $this->db->table('secanacategoria')->insertBatch($rows);
    }

    /**
     * Elimina solo las filas insertadas por esta migración (orden 2000+).
     */
    public function down()
    {
        $table = $this->db->prefixTable('secanacategoria');
        if (! $this->db->tableExists($table)) {
            return;
        }
        if (! $this->db->fieldExists('orden', $table)) {
            return;
        }
        $this->db->table('secanacategoria')
            ->where('prianacategoria_id', self::HEM_PRIA)
            ->where('orden >=', 2000)
            ->delete();
    }
}
