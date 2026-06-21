<?php

declare(strict_types=1);

namespace App\Services\ReportLayout;

use App\Services\ReportPdfLayoutService;

/**
 * Métricas de página y tipografía derivadas de plantilla + pageSize global.
 */
final class ReportLayoutMetrics
{
    /** Compensa diferencia Dompdf vs medición teórica en modos de flujo (MODE 1/2). */
    public const FLOW_DOMPDF_BLOCK_HEIGHT_FACTOR = 1.26;

    public const FLOW_DOMPDF_SIGNATURE_HEIGHT_FACTOR = 1.08;

    /** Margen de seguridad al decidir si cabe un bloque en flujo (evita huecos por subestimación). */
    public const FLOW_FIT_SAFETY_MM = 5.0;

    public function __construct(
        public readonly float $pageHeightMm,
        public readonly float $pageWidthMm,
        public readonly float $marginTopMm,
        public readonly float $marginBottomMm,
        public readonly float $footerReserveMm,
        public readonly float $contentStartMm,
        public readonly float $maxContentHeightMm,
        public readonly float $rowHeightMm,
        public readonly float $theadHeightMm,
        public readonly float $segmentTitleHeightMm,
        public readonly float $blockHeaderHeightMm,
        public readonly float $areaSeparatorHeightMm,
        public readonly float $subgrupoGapMm,
        public readonly float $signatureHeightMm,
        public readonly string $paperKey,
        public readonly bool $cabeceraShowTipoMuestra = false,
        public readonly bool $cabeceraShowMetodo = false,
        public readonly bool $cabeceraAreaSeparatorEnabled = false,
        public readonly float $groupTitleLineHeightMm = 4.64,
        public readonly float $groupTitleMarginBottomMm = 1.59,
        public readonly float $cabeceraMetaLineHeightMm = 3.87,
        public readonly float $cabeceraMetaMarginBottomMm = 2.65,
        public readonly float $firstReportGroupTitleMarginTopMm = 2.65,
        public readonly float $grupoPruebaGapMm = 2.65,
        public readonly float $segmentWrapMarginBottomMm = 2.12,
        public readonly float $tableRowBorderMm = 0.35,
        public readonly float $tableMarginTopMm = 0.53,
        public readonly float $tableMarginBottomMm = 0.53,
        public readonly float $analysisBlockHeightFactor = 1.0,
        public readonly float $signatureHeightFactor = 1.0,
        public readonly bool $flowCompactSpacing = false,
        public readonly float $flowFitSafetyMm = 0.0,
    ) {
    }

    /**
     * @param array<string, mixed> $layout
     * @param array<string, mixed> $labConfig
     */
    public static function fromLayoutAndConfig(
        array $layout,
        array $labConfig,
        float $contentStartMm = 0.0,
        ?string $paginationMode = null,
    ): self {
        $page = ReportPdfLayoutService::resolveGlobalPageSizeMm($labConfig);
        $mm = is_array($layout['margins_mm'] ?? null)
            ? $layout['margins_mm']
            : ReportPdfLayoutService::defaultMarginsMmStatic();

        $marginTopMm = (float) ($mm['top'] ?? 15.0);
        $marginBottomMm = (float) ($mm['bottom'] ?? 15.0);

        $footerEnabled = false;
        foreach (is_array($layout['blocks'] ?? null) ? $layout['blocks'] : [] as $block) {
            if (! empty($block['enabled']) && (string) ($block['id'] ?? '') === 'footer') {
                $footerEnabled = true;
                break;
            }
        }
        $footerReserveMm = $footerEnabled
            ? max(10.0, ReportPdfLayoutService::estimatePdfFooterReserveMm($layout))
            : 0.0;

        $maxContentHeightMm = max(
            40.0,
            $page['height_mm'] - $marginTopMm - $marginBottomMm - $footerReserveMm,
        );

        $ps = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : [];
        $rs = ReportPdfLayoutService::normalizeResultsTableStyle($ps['results_table'] ?? []);
        $lf = ReportPdfLayoutService::normalizeLabFirmasStyle($ps['lab_firmas'] ?? []);

        $fontPt = max(6.0, (float) ($rs['font_size_pt'] ?? 9.0));
        $lineHeight = max(1.0, (float) ($rs['line_height'] ?? 1.35));
        $cellPadPx = max(0, (int) ($rs['cell_padding_v_px'] ?? 6));
        $cellPadMm = $cellPadPx * 25.4 / 96;

        $rowHeightMm = ($fontPt * $lineHeight * 25.4 / 72) + ($cellPadMm * 2) + 0.53;
        $theadHeightMm = $rowHeightMm + 1.5;
        $segmentTitleHeightMm = $fontPt * 1.2 * 25.4 / 72 + 3.0;
        $blockHeaderHeightMm = 14.0;

        $cfg = ReportPdfLayoutService::grupoCabeceraDisplayFromLayout($layout);
        $pxToMm = static fn (int $px): float => $px * 25.4 / 96;
        $groupTitleLineHeightMm = 11.0 * 1.2 * 25.4 / 72;
        $groupTitleMarginBottomMm = $pxToMm(max(0, min(20, (int) ($rs['cell_padding_v_px'] ?? 6))));
        $cabeceraMetaLineHeightMm = 9.0 * 1.3 * 25.4 / 72;
        $cabeceraMetaMarginBottomMm = $pxToMm(10);
        $firstReportGroupTitleMarginTopMm = $pxToMm(
            max(0, min(80, (int) ($rs['grupo_prueba_gap_px'] ?? 10))),
        );

        $sepMarginTopMm = $pxToMm(max(0, min(80, (int) ($rs['grupo_area_separator_margin_top_px'] ?? 10))));
        $sepMarginBottomMm = $pxToMm(max(0, min(80, (int) ($rs['grupo_area_separator_margin_bottom_px'] ?? 10))));
        $sepFontPt = max(6.0, (float) ($rs['grupo_area_separator_font_size_pt'] ?? 11.0));
        $sepTitleLineMm = $sepFontPt * 1.2 * 25.4 / 72 + 3.0;
        $areaSeparatorHeightMm = $sepMarginTopMm + $sepMarginBottomMm + $sepTitleLineMm;

        $subgrupoGapMm = max(0.0, ReportPdfLayoutService::subgrupoPruebaGapPx($layout) * 25.4 / 96);
        $grupoPruebaGapMm = $pxToMm(max(0, min(80, (int) ($rs['grupo_prueba_gap_px'] ?? 10))));
        $segmentWrapMarginBottomMm = $pxToMm(max(0, min(20, (int) ($rs['cell_padding_v_px'] ?? 6) + 2)));
        $tableMarginTopMm = $pxToMm(max(0, min(40, (int) ($rs['table_margin_top_px'] ?? 15))));
        $tableMarginBottomMm = $pxToMm(max(0, min(40, (int) ($rs['table_margin_bottom_px'] ?? 15))));

        $sealMm = (int) ($lf['seal_max_height_px'] ?? 110) * 25.4 / 96;
        $sigMm = (int) ($lf['signature_max_height_px'] ?? 72) * 25.4 / 96;
        $marginFirmaMm = ((float) ($lf['inline_margin_top_pt'] ?? 8) + (float) ($lf['inline_margin_bottom_pt'] ?? 6)) * 25.4 / 72;
        $signatureHeightMm = $marginFirmaMm + max($sealMm, $sigMm) + 18.0;

        $blockFactor = 1.0;
        $signatureFactor = 1.0;
        $flowCompact = false;
        $flowSafety = 0.0;
        if ($paginationMode !== null && ReportPaginationMode::usesFlowContinuousPagination($paginationMode)) {
            $blockFactor = self::FLOW_DOMPDF_BLOCK_HEIGHT_FACTOR;
            $signatureFactor = self::FLOW_DOMPDF_SIGNATURE_HEIGHT_FACTOR;
            $flowCompact = true;
            $flowSafety = self::FLOW_FIT_SAFETY_MM;
        }

        return new self(
            pageHeightMm: $page['height_mm'],
            pageWidthMm: $page['width_mm'],
            marginTopMm: $marginTopMm,
            marginBottomMm: $marginBottomMm,
            footerReserveMm: $footerReserveMm,
            contentStartMm: max(0.0, $contentStartMm),
            maxContentHeightMm: $maxContentHeightMm,
            rowHeightMm: $rowHeightMm,
            theadHeightMm: $theadHeightMm,
            segmentTitleHeightMm: $segmentTitleHeightMm,
            blockHeaderHeightMm: $blockHeaderHeightMm,
            areaSeparatorHeightMm: $areaSeparatorHeightMm,
            subgrupoGapMm: $subgrupoGapMm,
            signatureHeightMm: $signatureHeightMm,
            paperKey: $page['key'],
            cabeceraShowTipoMuestra: ! empty($cfg['show_tipo_muestra']),
            cabeceraShowMetodo: ! empty($cfg['show_metodo']),
            cabeceraAreaSeparatorEnabled: ! empty($cfg['area_separator_enabled']),
            groupTitleLineHeightMm: $groupTitleLineHeightMm,
            groupTitleMarginBottomMm: $groupTitleMarginBottomMm,
            cabeceraMetaLineHeightMm: $cabeceraMetaLineHeightMm,
            cabeceraMetaMarginBottomMm: $cabeceraMetaMarginBottomMm,
            firstReportGroupTitleMarginTopMm: $firstReportGroupTitleMarginTopMm,
            grupoPruebaGapMm: $grupoPruebaGapMm,
            segmentWrapMarginBottomMm: $segmentWrapMarginBottomMm,
            tableRowBorderMm: 0.35,
            tableMarginTopMm: $tableMarginTopMm,
            tableMarginBottomMm: $tableMarginBottomMm,
            analysisBlockHeightFactor: $blockFactor,
            signatureHeightFactor: $signatureFactor,
            flowCompactSpacing: $flowCompact,
            flowFitSafetyMm: $flowSafety,
        );
    }
}
