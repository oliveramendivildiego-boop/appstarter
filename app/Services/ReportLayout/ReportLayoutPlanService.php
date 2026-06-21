<?php

declare(strict_types=1);

namespace App\Services\ReportLayout;

/**
 * Punto de entrada: datos de reporte → LayoutPlan + Applier.
 */
final class ReportLayoutPlanService
{
    /**
     * @param array<string, list<mixed>>             $grupos
     * @param array<int, string>                     $reportPriaTipoMuestra
     * @param array<int, string>                     $reportPriaMetodo
     * @param array<int, list<array<string, mixed>>> $reportPriaRefsConsolidada
     * @param list<array<string, mixed>>             $reportLabFirmas
     * @param array<string, mixed>                   $pdfLayout
     * @param array<string, mixed>                   $labConfig
     *
     * @return array{
     *   plan: LayoutPlan,
     *   tree: ReportTree,
     *   applier: LayoutPlanApplier
     * }
     */
    public function buildContext(
        array $grupos,
        array $reportPriaTipoMuestra,
        array $reportPriaMetodo,
        array $reportPriaRefsConsolidada,
        array $reportLabFirmas,
        array $pdfLayout,
        array $labConfig,
        ?float $contentStartMm = null,
    ): array {
        $contentStart = $contentStartMm ?? \App\Services\ReportPdfLayoutService::estimatePdfHeaderBeforeResultsMm($pdfLayout);

        $tree = ReportTreeBuilder::build(
            $grupos,
            $reportPriaTipoMuestra,
            $reportPriaMetodo,
            $reportPriaRefsConsolidada,
            $reportLabFirmas,
            $pdfLayout,
        );

        $mode = \App\Services\ReportPdfLayoutService::resolvePaginationModeFromLayout($pdfLayout);

        $metrics = ReportLayoutMetrics::fromLayoutAndConfig(
            $pdfLayout,
            $labConfig,
            $contentStart,
            $mode,
        );

        $plan = (new ReportLayoutEngine($metrics))->buildLayoutPlan($tree, $mode);

        return [
            'plan'    => $plan,
            'tree'    => $tree,
            'applier' => new LayoutPlanApplier($plan, $tree),
        ];
    }

    /**
     * @param array<string, list<mixed>>             $grupos
     * @param array<int, string>                     $reportPriaTipoMuestra
     * @param array<int, string>                     $reportPriaMetodo
     * @param array<int, list<array<string, mixed>>> $reportPriaRefsConsolidada
     * @param list<array<string, mixed>>             $reportLabFirmas
     * @param array<string, mixed>                   $pdfLayout
     * @param array<string, mixed>                   $labConfig
     */
    public function buildPlan(
        array $grupos,
        array $reportPriaTipoMuestra,
        array $reportPriaMetodo,
        array $reportPriaRefsConsolidada,
        array $reportLabFirmas,
        array $pdfLayout,
        array $labConfig,
        ?float $contentStartMm = null,
    ): LayoutPlan {
        return $this->buildContext(
            $grupos,
            $reportPriaTipoMuestra,
            $reportPriaMetodo,
            $reportPriaRefsConsolidada,
            $reportLabFirmas,
            $pdfLayout,
            $labConfig,
            $contentStartMm,
        )['plan'];
    }
}
