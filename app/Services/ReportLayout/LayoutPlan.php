<?php

declare(strict_types=1);

namespace App\Services\ReportLayout;

/**
 * Plan determinista consumido por PRINT y PDF.
 */
final class LayoutPlan
{
    public function __construct(
        public readonly string $mode,
        public readonly ReportLayoutMetrics $metrics,
        /** @var list<LayoutPlacement> */
        public readonly array $placements,
        public readonly int $totalPages,
    ) {
    }

    /** @return array<string, LayoutPlacement> */
    public function placementsByNodeId(): array
    {
        $map = [];
        foreach ($this->placements as $placement) {
            $map[$placement->nodeId] = $placement;
        }

        return $map;
    }

    public function findPlacement(string $nodeId): ?LayoutPlacement
    {
        foreach ($this->placements as $placement) {
            if ($placement->nodeId === $nodeId) {
                return $placement;
            }
        }

        return null;
    }
}
