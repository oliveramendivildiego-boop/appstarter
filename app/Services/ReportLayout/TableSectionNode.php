<?php

declare(strict_types=1);

namespace App\Services\ReportLayout;

/**
 * Sección de tabla dentro de un ANALYSIS_BLOCK (segmento o matriz de referencias).
 */
final class TableSectionNode
{
    public function __construct(
        public readonly int $sectionIndex,
        public readonly bool $isMatrix,
        public readonly bool $hasSegmentTitle,
        public readonly int $rowCount,
        public readonly bool $includeThead,
    ) {
    }
}
