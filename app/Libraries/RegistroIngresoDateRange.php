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
        $fromDay = substr(trim($dateFrom), 0, 10);
        $toDay   = substr(trim($dateTo), 0, 10);
        $r       = $prefixedRegistroTable;

        try {
            if ($fromDay === '' || $toDay === '') {
                throw new \InvalidArgumentException('empty date');
            }
            $start   = (new \DateTimeImmutable($fromDay))->setTime(0, 0, 0);
            $endOpen = (new \DateTimeImmutable($toDay))->modify('+1 day')->setTime(0, 0, 0);
        } catch (\Throwable) {
            return $builder->where("DATE({$r}.ingreso) >=", $fromDay !== '' ? $fromDay : $dateFrom)
                ->where("DATE({$r}.ingreso) <=", $toDay !== '' ? $toDay : $dateTo);
        }

        return $builder->where("{$r}.ingreso >=", $start->format('Y-m-d H:i:s'))
            ->where("{$r}.ingreso <", $endOpen->format('Y-m-d H:i:s'));
    }
}
