<?php

declare(strict_types=1);

namespace App\Services\ReportLayout;

/**
 * Clases de paginación por objetivo DOM (misma superficie que el sistema anterior).
 */
final class LayoutDomMarkers
{
    public function __construct(
        public readonly string $grupoClass = '',
        public readonly string $subgrupoClass = '',
        public readonly string $cabeceraClass = '',
        /** @var list<string> */
        public readonly array $segmentClasses = [],
        public readonly bool $interPageBreakBeforeArea = false,
        public readonly bool $pageBreakBeforeBlock = false,
        public readonly bool $signatureTailGroup = false,
    ) {
    }
}
