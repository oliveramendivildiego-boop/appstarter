<?php

declare(strict_types=1);

namespace App\Services\ReportLayout;

/**
 * Árbol previo al render: áreas → bloques de análisis → firmas.
 */
final class ReportTree
{
    public function __construct(
        /** @var list<AreaNode> */
        public readonly array $areas,
        public readonly ?SignatureBlockNode $globalSignature,
    ) {
    }

    /** @return list<AnalysisBlockNode> */
    public function allAnalysisBlocks(): array
    {
        $blocks = [];
        foreach ($this->areas as $area) {
            foreach ($area->analysisBlocks as $block) {
                $blocks[] = $block;
            }
        }

        return $blocks;
    }

    public function findAnalysisBlock(string $id): ?AnalysisBlockNode
    {
        foreach ($this->allAnalysisBlocks() as $block) {
            if ($block->id === $id) {
                return $block;
            }
        }

        return null;
    }
}
