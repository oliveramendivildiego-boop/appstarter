<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Database\BaseBuilder;

/**
 * Filtro por rango de días (inclusive) sobre registro.ingreso sin DATE(columna) en el WHERE,
 * para que el optimizador pueda usar índices en ingreso.
 */
final class RegistroIngresoDateRange
{
    /**
     * @param string $prefixedRegistroTable Tabla registro ya prefijada (p. ej. dom_registro)
     */
    public static function apply(BaseBuilder $builder, string $prefixedRegistroTable, string $dateFrom, string $dateTo): BaseBuilder
    {
        return LabNaiveDateRange::apply($builder, $prefixedRegistroTable, 'ingreso', $dateFrom, $dateTo);
    }
}
