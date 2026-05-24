<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Services\RegisterService;
use CodeIgniter\Database\BaseBuilder;

/**
 * Filtro por rango de días (inclusive) sobre columnas DATETIME guardadas en la zona del laboratorio,
 * sin DATE(columna) en el WHERE para permitir uso de índices.
 */
final class LabNaiveDateRange
{
    public static function apply(BaseBuilder $builder, string $prefixedTable, string $column, string $dateFrom, string $dateTo): BaseBuilder
    {
        $fromDay = substr(trim($dateFrom), 0, 10);
        $toDay   = substr(trim($dateTo), 0, 10);
        $col     = "{$prefixedTable}.{$column}";

        try {
            if ($fromDay === '' || $toDay === '') {
                throw new \InvalidArgumentException('empty date');
            }
            $tz      = new \DateTimeZone(RegisterService::reportDisplayTimezone());
            $start   = (new \DateTimeImmutable($fromDay, $tz))->setTime(0, 0, 0);
            $endOpen = (new \DateTimeImmutable($toDay, $tz))->modify('+1 day')->setTime(0, 0, 0);
        } catch (\Throwable) {
            return $builder->where("DATE({$col}) >=", $fromDay !== '' ? $fromDay : $dateFrom)
                ->where("DATE({$col}) <=", $toDay !== '' ? $toDay : $dateTo);
        }

        return $builder->where("{$col} >=", $start->format('Y-m-d H:i:s'))
            ->where("{$col} <", $endOpen->format('Y-m-d H:i:s'));
    }

    /**
     * Igual que apply(), pero admite solo fecha inicial o solo final.
     */
    public static function applyPartial(BaseBuilder $builder, string $prefixedTable, string $column, ?string $dateFrom, ?string $dateTo): BaseBuilder
    {
        $fromDay = ($dateFrom !== null && trim($dateFrom) !== '') ? substr(trim($dateFrom), 0, 10) : '';
        $toDay   = ($dateTo !== null && trim($dateTo) !== '') ? substr(trim($dateTo), 0, 10) : '';

        if ($fromDay !== '' && $toDay !== '') {
            return self::apply($builder, $prefixedTable, $column, $fromDay, $toDay);
        }

        $col = "{$prefixedTable}.{$column}";

        try {
            $tz = new \DateTimeZone(RegisterService::reportDisplayTimezone());
            if ($fromDay !== '') {
                $start = (new \DateTimeImmutable($fromDay, $tz))->setTime(0, 0, 0);
                $builder->where("{$col} >=", $start->format('Y-m-d H:i:s'));
            }
            if ($toDay !== '') {
                $endOpen = (new \DateTimeImmutable($toDay, $tz))->modify('+1 day')->setTime(0, 0, 0);
                $builder->where("{$col} <", $endOpen->format('Y-m-d H:i:s'));
            }
        } catch (\Throwable) {
            if ($fromDay !== '') {
                $builder->where("DATE({$col}) >=", $fromDay);
            }
            if ($toDay !== '') {
                $builder->where("DATE({$col}) <=", $toDay);
            }
        }

        return $builder;
    }
}
