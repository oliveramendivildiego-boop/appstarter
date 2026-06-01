<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Services\RegisterService;
use CodeIgniter\Database\BaseBuilder;

/**
 * Filtro por rango de días (inclusive) sobre columnas DATETIME (UTC o local según /config),
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
            [$startStr, $endOpenStr] = RegisterService::labDateRangeToStorageBounds($fromDay, $toDay);
        } catch (\Throwable) {
            return $builder->where("DATE({$col}) >=", $fromDay !== '' ? $fromDay : $dateFrom)
                ->where("DATE({$col}) <=", $toDay !== '' ? $toDay : $dateTo);
        }

        return $builder->where("{$col} >=", $startStr)
            ->where("{$col} <", $endOpenStr);
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
            if ($fromDay !== '') {
                [$startStr] = RegisterService::labDateRangeToStorageBounds($fromDay, $fromDay);
                $builder->where("{$col} >=", $startStr);
            }
            if ($toDay !== '') {
                [, $endOpenStr] = RegisterService::labDateRangeToStorageBounds($toDay, $toDay);
                $builder->where("{$col} <", $endOpenStr);
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
