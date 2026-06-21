<?php

declare(strict_types=1);

namespace App\Services\ReportLayout;

/**
 * Colocación de un nodo (área, bloque o firma) en el plan de layout.
 */
final class LayoutPlacement
{
    public const TYPE_AREA_BREAK = 'area_break';

    public const TYPE_ANALYSIS = 'analysis';

    public const TYPE_SIGNATURE = 'signature';

    public function __construct(
        public readonly string $nodeId,
        public readonly string $nodeType,
        public readonly int $pageIndex,
        public readonly float $yStartMm,
        public readonly float $heightMm,
        public readonly LayoutDomMarkers $markers,
        /** @var list<TableSplitPlan> */
        public readonly array $tableSplits = [],
    ) {
    }
}
