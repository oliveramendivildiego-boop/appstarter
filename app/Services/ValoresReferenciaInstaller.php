<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use Config\ValoresReferenciaCatalog;

/**
 * Aplica catálogo de valores de referencia (priresultados por población 6–12),
 * normaliza id_poblacion legacy y corrige subclases del hemograma compuesto.
 * Usado por la migración y por el comando spark lab:apply-valores-referencia.
 */
class ValoresReferenciaInstaller
{
    public static function apply(BaseConnection $db): void
    {
        self::normalizarPoblacionLegacy($db);
        self::corregirHemogramaCompuesto($db);
        self::aplicarCatalogoPriresultados($db);
    }

    private static function normalizarPoblacionLegacy(BaseConnection $db): void
    {
        $valid = [6, 7, 8, 9, 10, 11, 12, 15];
        $tbl   = $db->prefixTable('priresultados');

        $db->query("UPDATE {$tbl} SET id_poblacion = 15, sexo = 'masculino' WHERE id_poblacion = 1 AND (sexo IS NULL OR sexo = '' OR sexo = 'ambos')");
        $db->query("UPDATE {$tbl} SET id_poblacion = 15, sexo = 'femenino' WHERE id_poblacion = 2 AND (sexo IS NULL OR sexo = '' OR sexo = 'ambos')");
        $db->query("UPDATE {$tbl} SET id_poblacion = 15, sexo = 'ambos' WHERE id_poblacion IN (0,3,4,5)");

        $in = implode(',', $valid);
        $db->query("UPDATE {$tbl} SET id_poblacion = 15 WHERE id_poblacion > 0 AND id_poblacion NOT IN ({$in})");
    }

    private static function corregirHemogramaCompuesto(BaseConnection $db): void
    {
        $sec = $db->prefixTable('secanacategoria');
        $db->query("UPDATE {$sec} SET paciente_id = 15 WHERE prianacategoria_id = 2 AND paciente_id NOT IN (6,7,8,9,10,11,12,15)");
        $db->query("UPDATE {$sec} SET valor_min = '36', valor_max = '46' WHERE secanacategoria_id = 9 AND nombre LIKE 'Hematocrito%' AND sexo = 'femenino'");
        $db->query("UPDATE {$sec} SET valor_min = '80', valor_max = '100' WHERE secanacategoria_id = 18 AND nombre LIKE 'VCM%' AND sexo = 'femenino'");
    }

    private static function aplicarCatalogoPriresultados(BaseConnection $db): void
    {
        $tbl   = $db->prefixTable('priresultados');
        $cols  = $db->getFieldNames($tbl);
        $hasSx = in_array('sexo', $cols, true);

        $cat = ValoresReferenciaCatalog::catalogoCompleto();

        foreach ($cat as $priaId => $rows) {
            $priaId = (int) $priaId;
            if ($priaId < 1) {
                continue;
            }
            $db->table('priresultados')->where('prianacategoria_id', $priaId)->update(['deleted' => 1]);

            foreach ($rows as $r) {
                $p = (int) ($r['p'] ?? 15);
                $s = (string) ($r['s'] ?? 'ambos');
                if (! in_array($s, ['ambos', 'masculino', 'femenino'], true)) {
                    $s = 'ambos';
                }
                $insert = [
                    'prianacategoria_id' => $priaId,
                    'id_poblacion'      => $p,
                    'valor_min'         => (string) ($r['min'] ?? ''),
                    'valor_max'         => (string) ($r['max'] ?? ''),
                    'umedida'           => (string) ($r['u'] ?? ''),
                    'formulas_id'       => (int) ($r['f'] ?? 1),
                    'opcion_id'         => (int) ($r['o'] ?? 3),
                    'deleted'           => 0,
                ];
                if (in_array('critico_min', $cols, true)) {
                    $insert['critico_min'] = '';
                    $insert['critico_max'] = '';
                }
                if ($hasSx) {
                    $insert['sexo'] = $s;
                }
                $db->table('priresultados')->insert($insert);
            }
        }
    }
}
