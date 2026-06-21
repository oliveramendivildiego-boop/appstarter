<?php

declare(strict_types=1);

namespace App\Services\ReportLayout;

/**
 * Área de reporte (grupo prueba / padre).
 */
final class AreaNode
{
    public function __construct(
        public readonly int $index,
        public readonly string $name,
        /** @var list<AnalysisBlockNode> */
        public readonly array $analysisBlocks,
        public readonly ?SignatureBlockNode $signature,
    ) {
    }
}
