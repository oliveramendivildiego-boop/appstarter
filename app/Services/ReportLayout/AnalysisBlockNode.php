<?php

declare(strict_types=1);

namespace App\Services\ReportLayout;

/**
 * ANALYSIS_BLOCK: cabecera (title + tipo + método) + tablas; unidad atómica de paginación.
 */
final class AnalysisBlockNode
{
    public function __construct(
        public readonly string $id,
        public readonly int $areaIndex,
        public readonly int $blockIndex,
        public readonly string $groupTitle,
        public readonly string $tipoMuestra,
        public readonly string $metodo,
        public readonly bool $isSubPrueba,
        public readonly bool $isCultivoMatrix,
        /** @var list<TableSectionNode> */
        public readonly array $tables,
    ) {
    }
}
