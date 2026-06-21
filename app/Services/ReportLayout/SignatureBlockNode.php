<?php

declare(strict_types=1);

namespace App\Services\ReportLayout;

/**
 * SIGNATURE_BLOCK: firma de validación/aprobación; unidad atómica.
 */
final class SignatureBlockNode
{
    public const SCOPE_PER_GROUP = 'per_group';

    public const SCOPE_BLOCK_END = 'block_end';

    public function __construct(
        public readonly string $id,
        public readonly int $areaIndex,
        public readonly string $scope,
        public readonly string $areaName,
    ) {
    }
}
