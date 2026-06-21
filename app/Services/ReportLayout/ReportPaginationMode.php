<?php

declare(strict_types=1);

namespace App\Services\ReportLayout;

/**
 * Modos de paginación configurables en plantilla PDF (/config/pdf-templates/edit).
 */
final class ReportPaginationMode
{
    public const FLOW_CONTINUOUS_SIGNATURE_LAST_PAGE = 'flow_continuous_signature_last_page';

    public const FLOW_NO_LONE_SIGNATURE = 'flow_no_lone_signature';
    /** MODE 2: igual que MODE 1 salvo la última firma del reporte (no puede quedar sola en la hoja). */

    public const AREA_HARD_PAGE_BREAK = 'area_hard_page_break';

    public const AREA_SOFT_FIT_SIGNATURE = 'area_soft_fit_signature';

    /** @var list<string> */
    public const ALL = [
        self::FLOW_CONTINUOUS_SIGNATURE_LAST_PAGE,
        self::FLOW_NO_LONE_SIGNATURE,
        self::AREA_HARD_PAGE_BREAK,
        self::AREA_SOFT_FIT_SIGNATURE,
    ];

    public static function isValid(string $mode): bool
    {
        return in_array($mode, self::ALL, true);
    }

    public static function default(): string
    {
        return self::FLOW_CONTINUOUS_SIGNATURE_LAST_PAGE;
    }

    /**
     * Migra modos legacy (grupo_prueba_page_break.mode) al contrato actual.
     */
    public static function migrateFromLegacy(string $legacyMode): string
    {
        $legacy = strtolower(trim($legacyMode));

        return match ($legacy) {
            'keep_together' => self::AREA_HARD_PAGE_BREAK,
            'keep_together_compact' => self::AREA_SOFT_FIT_SIGNATURE,
            'keep_together_if_fits' => self::FLOW_NO_LONE_SIGNATURE,
            'keep_segment' => self::FLOW_CONTINUOUS_SIGNATURE_LAST_PAGE,
            'keep_together_if_fits_auto_order' => self::FLOW_CONTINUOUS_SIGNATURE_LAST_PAGE,
            'flow' => self::FLOW_CONTINUOUS_SIGNATURE_LAST_PAGE,
            default => self::isValid($legacy) ? $legacy : self::default(),
        };
    }

    public static function usesAreaHardPageBreak(string $mode): bool
    {
        return $mode === self::AREA_HARD_PAGE_BREAK || $mode === self::AREA_SOFT_FIT_SIGNATURE;
    }

    public static function avoidsLoneSignatureEverywhere(string $mode): bool
    {
        return $mode === self::FLOW_NO_LONE_SIGNATURE;
    }

    /** MODE 2: regla anti-firma-sola solo en la última firma del reporte. */
    public static function avoidsLoneSignatureOnLastReportSignature(string $mode): bool
    {
        return $mode === self::FLOW_NO_LONE_SIGNATURE;
    }

    public static function optimizesLoneSignatureOnLastPage(string $mode): bool
    {
        return $mode === self::FLOW_CONTINUOUS_SIGNATURE_LAST_PAGE;
    }

    public static function softFitSignatureInArea(string $mode): bool
    {
        return $mode === self::AREA_SOFT_FIT_SIGNATURE;
    }

    public static function usesFlowContinuousPagination(string $mode): bool
    {
        return $mode === self::FLOW_CONTINUOUS_SIGNATURE_LAST_PAGE
            || $mode === self::FLOW_NO_LONE_SIGNATURE;
    }

    public static function usesSubgrupoKeepIntactInHtml(string $mode): bool
    {
        return self::usesAreaHardPageBreak($mode);
    }

    /**
     * @deprecated Use usesSubgrupoKeepIntactInHtml() — flujo no usa keep-intact.
     */
    public static function flowPaginationDisablesSubgrupoKeepIntact(string $mode): bool
    {
        return self::usesFlowContinuousPagination($mode);
    }
}
