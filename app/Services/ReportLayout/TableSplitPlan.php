<?php

declare(strict_types=1);

namespace App\Services\ReportLayout;

/**
 * Parte de tabla dentro de un bloque cuando el tbody continúa en otra página.
 */
final class TableSplitPlan
{
    public function __construct(
        public readonly int $sectionIndex,
        public readonly int $startRow,
        public readonly int $rowCount,
        public readonly bool $repeatThead,
        public readonly int $pageIndex,
    ) {
    }
}
