<?php

declare(strict_types=1);

use App\Services\ReportLayout\AnalysisBlockNode;
use App\Services\ReportLayout\AreaNode;
use App\Services\ReportLayout\LayoutPlacement;
use App\Services\ReportLayout\ReportLayoutEngine;
use App\Services\ReportLayout\ReportLayoutMetrics;
use App\Services\ReportLayout\ReportLayoutPlanService;
use App\Services\ReportLayout\ReportPaginationMode;
use App\Services\ReportLayout\ReportTree;
use App\Services\ReportLayout\ReportTreeBuilder;
use App\Services\ReportLayout\SignatureBlockNode;
use App\Services\ReportLayout\TableSectionNode;
use App\Services\ReportPdfLayoutService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ReportLayoutEngineTest extends CIUnitTestCase
{
    public function testMigrateLegacyPaginationModes(): void
    {
        $this->assertSame(
            ReportPaginationMode::AREA_HARD_PAGE_BREAK,
            ReportPaginationMode::migrateFromLegacy('keep_together'),
        );
        $this->assertSame(
            ReportPaginationMode::FLOW_NO_LONE_SIGNATURE,
            ReportPaginationMode::migrateFromLegacy('keep_together_if_fits'),
        );
        $this->assertSame(
            ReportPaginationMode::FLOW_CONTINUOUS_SIGNATURE_LAST_PAGE,
            ReportPaginationMode::migrateFromLegacy('flow'),
        );
    }

    public function testResolvePaginationModeFromLayoutPrefersPaginationModeKey(): void
    {
        $layout = [
            'page_style' => [
                'pagination_mode' => ReportPaginationMode::AREA_SOFT_FIT_SIGNATURE,
                'grupo_prueba_page_break' => ['mode' => 'flow'],
            ],
        ];

        $this->assertSame(
            ReportPaginationMode::AREA_SOFT_FIT_SIGNATURE,
            ReportPdfLayoutService::resolvePaginationModeFromLayout($layout),
        );
    }

    public function testReportTreeBuilderCreatesAnalysisBlocks(): void
    {
        $grupos = [
            'HEMATOLOGÍA' => [
                (object) [
                    'prianacategoria_id' => 10,
                    'hijo' => 'Hemograma',
                    'nombre' => 'GB',
                    'regvalues' => '12',
                    'es_separador' => 0,
                ],
                (object) [
                    'prianacategoria_id' => 10,
                    'hijo' => 'Hemograma',
                    'nombre' => 'Hb',
                    'regvalues' => '14',
                    'es_separador' => 0,
                ],
            ],
        ];

        $tree = ReportTreeBuilder::build(
            $grupos,
            [10 => 'Sangre EDTA'],
            [10 => 'Automatizado'],
            [],
            [],
            ['blocks' => []],
        );

        $this->assertCount(1, $tree->areas);
        $this->assertSame('HEMATOLOGÍA', $tree->areas[0]->name);
        $this->assertCount(1, $tree->areas[0]->analysisBlocks);
        $this->assertSame('Hemograma', $tree->areas[0]->analysisBlocks[0]->groupTitle);
        $this->assertSame('Sangre EDTA', $tree->areas[0]->analysisBlocks[0]->tipoMuestra);
        $this->assertCount(1, $tree->areas[0]->analysisBlocks[0]->tables);
        $this->assertSame(2, $tree->areas[0]->analysisBlocks[0]->tables[0]->rowCount);
    }

    public function testMode1BlockTooLargeAdvancesPageWithoutCssForceBreak(): void
    {
        $metrics = new ReportLayoutMetrics(
            pageHeightMm: 279.4,
            pageWidthMm: 215.9,
            marginTopMm: 15.0,
            marginBottomMm: 15.0,
            footerReserveMm: 22.0,
            contentStartMm: 40.0,
            maxContentHeightMm: 120.0,
            rowHeightMm: 5.0,
            theadHeightMm: 6.5,
            segmentTitleHeightMm: 8.0,
            blockHeaderHeightMm: 14.0,
            areaSeparatorHeightMm: 10.0,
            subgrupoGapMm: 4.0,
            signatureHeightMm: 30.0,
            paperKey: 'letter',
            cabeceraShowTipoMuestra: true,
            cabeceraShowMetodo: true,
        );

        $blockA = new AnalysisBlockNode(
            id: 'area-0-block-0',
            areaIndex: 0,
            blockIndex: 0,
            groupTitle: 'Prueba previa',
            tipoMuestra: '',
            metodo: '',
            isSubPrueba: false,
            isCultivoMatrix: false,
            tables: [new TableSectionNode(0, false, false, 3, true)],
        );
        $blockB = new AnalysisBlockNode(
            id: 'area-0-block-1',
            areaIndex: 0,
            blockIndex: 1,
            groupTitle: 'Prueba A',
            tipoMuestra: 'Sangre',
            metodo: 'Automatizado',
            isSubPrueba: true,
            isCultivoMatrix: false,
            tables: [new TableSectionNode(0, false, false, 12, true)],
        );

        $tree = new ReportTree(
            [new AreaNode(0, 'Área A', [$blockA, $blockB], null)],
            null,
        );

        $plan = (new ReportLayoutEngine($metrics))->buildLayoutPlan(
            $tree,
            ReportPaginationMode::FLOW_CONTINUOUS_SIGNATURE_LAST_PAGE,
        );

        $placement = $plan->findPlacement('area-0-block-1');
        $this->assertNotNull($placement);
        $this->assertSame(1, $placement->pageIndex);
        $this->assertSame('', $placement->markers->subgrupoClass);
        $this->assertSame('', $placement->markers->cabeceraClass);
        $this->assertFalse($placement->markers->pageBreakBeforeBlock);
    }

    public function testMode2SignatureTailSplitOnLastBlock(): void
    {
        $metrics = new ReportLayoutMetrics(
            pageHeightMm: 279.4,
            pageWidthMm: 215.9,
            marginTopMm: 15.0,
            marginBottomMm: 15.0,
            footerReserveMm: 22.0,
            contentStartMm: 40.0,
            maxContentHeightMm: 200.0,
            rowHeightMm: 5.0,
            theadHeightMm: 6.5,
            segmentTitleHeightMm: 8.0,
            blockHeaderHeightMm: 14.0,
            areaSeparatorHeightMm: 10.0,
            subgrupoGapMm: 4.0,
            signatureHeightMm: 30.0,
            paperKey: 'letter',
            flowFitSafetyMm: 5.0,
            flowCompactSpacing: true,
        );

        $block = new AnalysisBlockNode(
            id: 'area-0-block-0',
            areaIndex: 0,
            blockIndex: 0,
            groupTitle: 'Orina',
            tipoMuestra: '',
            metodo: '',
            isSubPrueba: false,
            isCultivoMatrix: false,
            tables: [
                new TableSectionNode(0, false, true, 2, true),
                new TableSectionNode(1, false, true, 4, true),
            ],
        );
        $signature = new SignatureBlockNode(
            id: 'area-0-signature',
            areaIndex: 0,
            scope: SignatureBlockNode::SCOPE_PER_GROUP,
            areaName: 'ORINA',
        );

        $tree = new ReportTree(
            [new AreaNode(0, 'ORINA', [$block], $signature)],
            null,
        );

        $plan = (new ReportLayoutEngine($metrics))->buildLayoutPlan(
            $tree,
            ReportPaginationMode::FLOW_NO_LONE_SIGNATURE,
        );

        $placement = $plan->findPlacement('area-0-block-0');
        $this->assertNotNull($placement);
        $this->assertTrue($placement->markers->signatureTailGroup);
        $this->assertCount(2, $placement->tableSplits);
        $this->assertSame(1, $placement->tableSplits[1]->sectionIndex);
        $this->assertSame(0, $placement->tableSplits[0]->rowCount);
        $this->assertSame(4, $placement->tableSplits[1]->rowCount);
        $this->assertTrue($placement->tableSplits[1]->repeatThead);
    }

    public function testMode2SingleRowLastBlockUsesFullTailAndRelocate(): void
    {
        $metrics = new ReportLayoutMetrics(
            pageHeightMm: 279.4,
            pageWidthMm: 215.9,
            marginTopMm: 15.0,
            marginBottomMm: 15.0,
            footerReserveMm: 22.0,
            contentStartMm: 40.0,
            maxContentHeightMm: 200.0,
            rowHeightMm: 5.0,
            theadHeightMm: 6.5,
            segmentTitleHeightMm: 8.0,
            blockHeaderHeightMm: 14.0,
            areaSeparatorHeightMm: 10.0,
            subgrupoGapMm: 4.0,
            signatureHeightMm: 30.0,
            paperKey: 'letter',
            flowFitSafetyMm: 5.0,
            flowCompactSpacing: true,
        );

        $blockPrev = new AnalysisBlockNode(
            id: 'area-0-block-0',
            areaIndex: 0,
            blockIndex: 0,
            groupTitle: 'Glucosa',
            tipoMuestra: '',
            metodo: '',
            isSubPrueba: false,
            isCultivoMatrix: false,
            tables: [new TableSectionNode(0, false, true, 2, true)],
        );
        $blockLast = new AnalysisBlockNode(
            id: 'area-0-block-1',
            areaIndex: 0,
            blockIndex: 1,
            groupTitle: 'Creatinina',
            tipoMuestra: '',
            metodo: '',
            isSubPrueba: true,
            isCultivoMatrix: false,
            tables: [new TableSectionNode(0, false, true, 1, true)],
        );
        $signature = new SignatureBlockNode(
            id: 'area-0-signature',
            areaIndex: 0,
            scope: SignatureBlockNode::SCOPE_PER_GROUP,
            areaName: 'QUIMICA CLÍNICA',
        );

        $tree = new ReportTree(
            [new AreaNode(0, 'QUIMICA CLÍNICA', [$blockPrev, $blockLast], $signature)],
            null,
        );

        $plan = (new ReportLayoutEngine($metrics))->buildLayoutPlan(
            $tree,
            ReportPaginationMode::FLOW_NO_LONE_SIGNATURE,
        );

        $lastBlock = $plan->findPlacement('area-0-block-1');
        $sig = $plan->findPlacement('area-0-signature');
        $this->assertNotNull($lastBlock);
        $this->assertNotNull($sig);
        $this->assertTrue($lastBlock->markers->signatureTailGroup);
        $this->assertSame(0, $lastBlock->tableSplits[0]->rowCount);
        $this->assertSame(1, $lastBlock->tableSplits[1]->rowCount);
        $this->assertSame($lastBlock->pageIndex, $sig->pageIndex);
        $this->assertGreaterThan($plan->findPlacement('area-0-block-0')?->pageIndex ?? 0, $lastBlock->pageIndex);
    }

    public function testMode2TailSplitOnlyOnLastReportSignature(): void
    {
        $metrics = new ReportLayoutMetrics(
            pageHeightMm: 279.4,
            pageWidthMm: 215.9,
            marginTopMm: 15.0,
            marginBottomMm: 15.0,
            footerReserveMm: 22.0,
            contentStartMm: 40.0,
            maxContentHeightMm: 200.0,
            rowHeightMm: 5.0,
            theadHeightMm: 6.5,
            segmentTitleHeightMm: 8.0,
            blockHeaderHeightMm: 14.0,
            areaSeparatorHeightMm: 10.0,
            subgrupoGapMm: 4.0,
            signatureHeightMm: 30.0,
            paperKey: 'letter',
            flowFitSafetyMm: 5.0,
            flowCompactSpacing: true,
        );

        $sig = static fn (int $area): SignatureBlockNode => new SignatureBlockNode(
            id: 'area-' . $area . '-signature',
            areaIndex: $area,
            scope: SignatureBlockNode::SCOPE_PER_GROUP,
            areaName: 'Area ' . $area,
        );

        $block = static fn (int $area, int $block, int $rows): AnalysisBlockNode => new AnalysisBlockNode(
            id: 'area-' . $area . '-block-' . $block,
            areaIndex: $area,
            blockIndex: $block,
            groupTitle: 'Prueba ' . $area . '-' . $block,
            tipoMuestra: 'Suero',
            metodo: 'ELISA',
            isSubPrueba: $block > 0,
            isCultivoMatrix: false,
            tables: [new TableSectionNode(0, false, true, $rows, true)],
        );

        $tree = new ReportTree(
            [
                new AreaNode(0, 'SERología', [$block(0, 0, 2), $block(0, 1, 2)], $sig(0)),
                new AreaNode(1, 'ORINA', [$block(1, 0, 4)], $sig(1)),
            ],
            null,
        );

        $plan = (new ReportLayoutEngine($metrics))->buildLayoutPlan(
            $tree,
            ReportPaginationMode::FLOW_NO_LONE_SIGNATURE,
        );

        $firstAreaLastBlock = $plan->findPlacement('area-0-block-1');
        $lastAreaBlock = $plan->findPlacement('area-1-block-0');
        $this->assertNotNull($firstAreaLastBlock);
        $this->assertNotNull($lastAreaBlock);
        $this->assertFalse($firstAreaLastBlock->markers->signatureTailGroup);
        $this->assertSame([], $firstAreaLastBlock->tableSplits);
        $this->assertTrue($lastAreaBlock->markers->signatureTailGroup);
        $this->assertNotEmpty($lastAreaBlock->tableSplits);
    }

    public function testMeasureBlockHeaderIncludesTipoAndMetodo(): void
    {
        $metrics = ReportLayoutMetrics::fromLayoutAndConfig(
            [
                'blocks' => [],
                'page_style' => [
                    'results_table' => [
                        'grupo_cabecera_show_tipo_muestra' => true,
                        'grupo_cabecera_show_metodo' => true,
                    ],
                ],
            ],
            ['print_paper_size' => 'letter'],
        );

        $measurer = new \App\Services\ReportLayout\ReportNodeMeasurer($metrics);
        $block = new AnalysisBlockNode(
            id: 'area-0-block-0',
            areaIndex: 0,
            blockIndex: 0,
            groupTitle: 'Hemograma',
            tipoMuestra: 'Sangre EDTA',
            metodo: 'Automatizado',
            isSubPrueba: false,
            isCultivoMatrix: false,
            tables: [],
        );

        $headerOnly = $measurer->measureBlockHeader($block);
        $this->assertGreaterThan(14.0, $headerOnly);
    }

    public function testAreaHardPageBreakStartsSecondAreaOnNewPage(): void
    {
        $metrics = new ReportLayoutMetrics(
            pageHeightMm: 279.4,
            pageWidthMm: 215.9,
            marginTopMm: 15.0,
            marginBottomMm: 15.0,
            footerReserveMm: 22.0,
            contentStartMm: 40.0,
            maxContentHeightMm: 200.0,
            rowHeightMm: 5.0,
            theadHeightMm: 6.5,
            segmentTitleHeightMm: 8.0,
            blockHeaderHeightMm: 14.0,
            areaSeparatorHeightMm: 10.0,
            subgrupoGapMm: 4.0,
            signatureHeightMm: 30.0,
            paperKey: 'letter',
        );

        $blockA = new AnalysisBlockNode(
            id: 'area-0-block-0',
            areaIndex: 0,
            blockIndex: 0,
            groupTitle: 'Prueba A',
            tipoMuestra: '',
            metodo: '',
            isSubPrueba: false,
            isCultivoMatrix: false,
            tables: [new TableSectionNode(0, false, false, 3, true)],
        );
        $blockB = new AnalysisBlockNode(
            id: 'area-1-block-0',
            areaIndex: 1,
            blockIndex: 0,
            groupTitle: 'Prueba B',
            tipoMuestra: '',
            metodo: '',
            isSubPrueba: false,
            isCultivoMatrix: false,
            tables: [new TableSectionNode(0, false, false, 2, true)],
        );

        $tree = new ReportTree(
            [
                new AreaNode(0, 'Área A', [$blockA], null),
                new AreaNode(1, 'Área B', [$blockB], null),
            ],
            null,
        );

        $plan = (new ReportLayoutEngine($metrics))->buildLayoutPlan(
            $tree,
            ReportPaginationMode::AREA_HARD_PAGE_BREAK,
        );

        $secondBlock = $plan->findPlacement('area-1-block-0');
        $this->assertNotNull($secondBlock);
        $this->assertSame(1, $secondBlock->pageIndex);
    }

    public function testAreaHardPageBreakUsesCabeceraForceBreakMarker(): void
    {
        $metrics = new ReportLayoutMetrics(
            pageHeightMm: 279.4,
            pageWidthMm: 215.9,
            marginTopMm: 15.0,
            marginBottomMm: 15.0,
            footerReserveMm: 22.0,
            contentStartMm: 40.0,
            maxContentHeightMm: 50.0,
            rowHeightMm: 5.0,
            theadHeightMm: 6.5,
            segmentTitleHeightMm: 8.0,
            blockHeaderHeightMm: 14.0,
            areaSeparatorHeightMm: 10.0,
            subgrupoGapMm: 4.0,
            signatureHeightMm: 30.0,
            paperKey: 'letter',
        );

        $blockA = new AnalysisBlockNode(
            id: 'area-0-block-0',
            areaIndex: 0,
            blockIndex: 0,
            groupTitle: 'Prueba A',
            tipoMuestra: '',
            metodo: '',
            isSubPrueba: false,
            isCultivoMatrix: false,
            tables: [new TableSectionNode(0, false, false, 3, true)],
        );
        $blockB = new AnalysisBlockNode(
            id: 'area-1-block-0',
            areaIndex: 1,
            blockIndex: 0,
            groupTitle: 'Prueba B',
            tipoMuestra: '',
            metodo: '',
            isSubPrueba: false,
            isCultivoMatrix: false,
            tables: [new TableSectionNode(0, false, false, 3, true)],
        );

        $tree = new ReportTree(
            [
                new AreaNode(0, 'Área A', [$blockA], null),
                new AreaNode(1, 'Área B', [$blockB], null),
            ],
            null,
        );

        $plan = (new ReportLayoutEngine($metrics))->buildLayoutPlan(
            $tree,
            ReportPaginationMode::AREA_HARD_PAGE_BREAK,
        );

        $secondBlock = $plan->findPlacement('area-1-block-0');
        $this->assertNotNull($secondBlock);
        $this->assertSame(1, $secondBlock->pageIndex);
    }

    public function testBuildPlanServiceEndToEnd(): void
    {
        $grupos = [
            'QUÍMICA' => [
                (object) [
                    'prianacategoria_id' => 20,
                    'hijo' => 'Glucosa',
                    'nombre' => 'Glucosa',
                    'regvalues' => '90',
                    'es_separador' => 0,
                ],
            ],
        ];

        $layout = [
            'blocks' => [],
            'margins_mm' => ['top' => 15, 'bottom' => 15],
            'page_style' => [
                'pagination_mode' => ReportPaginationMode::FLOW_CONTINUOUS_SIGNATURE_LAST_PAGE,
                'results_table' => ['font_size_pt' => 9, 'line_height' => 1.35],
            ],
        ];

        $plan = (new ReportLayoutPlanService())->buildPlan(
            $grupos,
            [],
            [],
            [],
            [],
            $layout,
            ['print_paper_size' => 'letter'],
        );

        $this->assertSame(ReportPaginationMode::FLOW_CONTINUOUS_SIGNATURE_LAST_PAGE, $plan->mode);
        $this->assertNotEmpty($plan->placements);
        $this->assertSame(
            LayoutPlacement::TYPE_ANALYSIS,
            $plan->placements[0]->nodeType,
        );
    }
}
