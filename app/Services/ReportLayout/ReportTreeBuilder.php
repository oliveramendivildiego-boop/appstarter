<?php

declare(strict_types=1);

namespace App\Services\ReportLayout;

use App\Services\ReportPdfLayoutService;

/**
 * Construye ReportTree desde datos de reporte (sin HTML).
 */
final class ReportTreeBuilder
{
    /**
     * @param array<string, list<mixed>>                          $grupos
     * @param array<int, string>                                  $reportPriaTipoMuestra
     * @param array<int, string>                                  $reportPriaMetodo
     * @param array<int, list<array<string, mixed>>>              $reportPriaRefsConsolidada
     * @param list<array<string, mixed>>                          $reportLabFirmas
     * @param array<string, mixed>                                $pdfLayout
     */
    public static function build(
        array $grupos,
        array $reportPriaTipoMuestra,
        array $reportPriaMetodo,
        array $reportPriaRefsConsolidada,
        array $reportLabFirmas,
        array $pdfLayout,
    ): ReportTree {
        $lfStyle = ReportPdfLayoutService::normalizeLabFirmasStyle(
            is_array($pdfLayout['page_style']['lab_firmas'] ?? null)
                ? $pdfLayout['page_style']['lab_firmas']
                : [],
        );
        $labFirmasEnabled = ReportPdfLayoutService::isLabFirmasBlockEnabled($pdfLayout);
        $showFirmaPerGroup = $labFirmasEnabled
            && ReportPdfLayoutService::labFirmasPlacementShowsPerGroup($lfStyle);
        $showFirmaBlockEnd = $labFirmasEnabled
            && ReportPdfLayoutService::labFirmasPlacementShowsBlockEnd($lfStyle);

        $firmasPorPadre = [];
        foreach ($reportLabFirmas as $firmaRow) {
            if (! is_array($firmaRow)) {
                continue;
            }
            $areaNombre = trim((string) ($firmaRow['prueba_nombre'] ?? ''));
            if ($areaNombre !== '') {
                $firmasPorPadre[$areaNombre] = $firmaRow;
            }
        }

        $ocultarThead = ReportPdfLayoutService::grupoCabeceraOcultarTheadResultsTabla($pdfLayout);

        $areas = [];
        $areaIndex = 0;
        foreach ($grupos as $padre => $items) {
            $padreKey = trim((string) $padre);
            $itemsList = is_array($items) ? $items : [];
            if ($padreKey === '' || $itemsList === []) {
                continue;
            }

            $analysisBlocks = self::buildAnalysisBlocksForArea(
                $areaIndex,
                $padreKey,
                $itemsList,
                $reportPriaTipoMuestra,
                $reportPriaMetodo,
                $reportPriaRefsConsolidada,
                ! $ocultarThead,
            );

            if ($analysisBlocks === []) {
                continue;
            }

            $signature = null;
            if ($showFirmaPerGroup && isset($firmasPorPadre[$padreKey])) {
                $signature = new SignatureBlockNode(
                    id: self::signatureId($areaIndex, SignatureBlockNode::SCOPE_PER_GROUP),
                    areaIndex: $areaIndex,
                    scope: SignatureBlockNode::SCOPE_PER_GROUP,
                    areaName: $padreKey,
                );
            }

            $areas[] = new AreaNode(
                index: $areaIndex,
                name: $padreKey,
                analysisBlocks: $analysisBlocks,
                signature: $signature,
            );
            $areaIndex++;
        }

        $globalSignature = $showFirmaBlockEnd
            ? new SignatureBlockNode(
                id: self::signatureId(-1, SignatureBlockNode::SCOPE_BLOCK_END),
                areaIndex: -1,
                scope: SignatureBlockNode::SCOPE_BLOCK_END,
                areaName: '',
            )
            : null;

        return new ReportTree($areas, $globalSignature);
    }

    /**
     * @param list<mixed> $items
     *
     * @return list<AnalysisBlockNode>
     */
    private static function buildAnalysisBlocksForArea(
        int $areaIndex,
        string $padreKey,
        array $items,
        array $reportPriaTipoMuestra,
        array $reportPriaMetodo,
        array $reportPriaRefsConsolidada,
        bool $includeThead,
    ): array {
        $subgruposPorPria = [];
        $ordenPriaKeys = [];
        foreach ($items as $raw) {
            $it = is_array($raw) ? (object) $raw : $raw;
            $pid = (int) ($it->prianacategoria_id ?? 0);
            if (! isset($subgruposPorPria[$pid])) {
                $subgruposPorPria[$pid] = [];
                $ordenPriaKeys[] = $pid;
            }
            $subgruposPorPria[$pid][] = $raw;
        }

        $blocks = [];
        $blockIndex = 0;

        foreach ($ordenPriaKeys as $subIdx => $priaKey) {
            $subItems = $subgruposPorPria[$priaKey];

            $cultivoItem = null;
            foreach ($subItems as $rawCultivo) {
                $itCultivo = is_array($rawCultivo) ? (object) $rawCultivo : $rawCultivo;
                if (! empty($itCultivo->es_cultivo_matriz)) {
                    $cultivoItem = $itCultivo;
                    break;
                }
            }

            if ($cultivoItem !== null) {
                $block = self::buildCultivoAnalysisBlock(
                    $areaIndex,
                    $blockIndex,
                    $subIdx,
                    $cultivoItem,
                    $reportPriaTipoMuestra,
                    $reportPriaMetodo,
                    $includeThead,
                );
                if ($block !== null) {
                    $blocks[] = $block;
                    $blockIndex++;
                }
                continue;
            }

            $hijo = self::resolveHijoTitulo($subItems);
            $priaIdTitulo = (int) $priaKey;
            if ($priaIdTitulo < 1) {
                foreach ($subItems as $rawPria) {
                    $op = is_array($rawPria) ? (object) $rawPria : $rawPria;
                    $pid = (int) ($op->prianacategoria_id ?? 0);
                    if ($pid > 0) {
                        $priaIdTitulo = $pid;
                        break;
                    }
                }
            }

            $tipoMuestra = trim((string) ($reportPriaTipoMuestra[$priaIdTitulo] ?? ''));
            $metodo = trim((string) ($reportPriaMetodo[$priaIdTitulo] ?? ''));

            $segments = self::splitSegments($subItems);
            $tables = [];
            $sectionIndex = 0;
            $hasAnyResult = false;

            foreach ($segments as $seg) {
                $rowCount = self::countVisibleRows($seg['items']);
                if ($rowCount <= 0) {
                    continue;
                }
                $hasAnyResult = true;
                $tables[] = new TableSectionNode(
                    sectionIndex: $sectionIndex,
                    isMatrix: false,
                    hasSegmentTitle: $seg['title'] !== null,
                    rowCount: $rowCount,
                    includeThead: $includeThead,
                );
                $sectionIndex++;
            }

            if ($priaIdTitulo > 0 && ! empty($reportPriaRefsConsolidada[$priaIdTitulo])) {
                $matrixRows = self::countMatrixRows($reportPriaRefsConsolidada[$priaIdTitulo]);
                if ($matrixRows > 0) {
                    $hasAnyResult = true;
                    $tables[] = new TableSectionNode(
                        sectionIndex: $sectionIndex,
                        isMatrix: true,
                        hasSegmentTitle: true,
                        rowCount: $matrixRows,
                        includeThead: $includeThead,
                    );
                }
            }

            if (! $hasAnyResult) {
                continue;
            }

            $groupTitle = $hijo !== '' ? $hijo : $padreKey;
            $blocks[] = new AnalysisBlockNode(
                id: self::analysisBlockId($areaIndex, $blockIndex),
                areaIndex: $areaIndex,
                blockIndex: $blockIndex,
                groupTitle: $groupTitle,
                tipoMuestra: $tipoMuestra,
                metodo: $metodo,
                isSubPrueba: $subIdx > 0,
                isCultivoMatrix: false,
                tables: $tables,
            );
            $blockIndex++;
        }

        return $blocks;
    }

    private static function buildCultivoAnalysisBlock(
        int $areaIndex,
        int $blockIndex,
        int $subIdx,
        object $cultivoItem,
        array $reportPriaTipoMuestra,
        array $reportPriaMetodo,
        bool $includeThead,
    ): ?AnalysisBlockNode {
        $priaId = (int) ($cultivoItem->prianacategoria_id ?? 0);
        $padre = trim((string) ($cultivoItem->padre ?? ''));
        $hijo = trim((string) ($cultivoItem->hijo ?? ''));
        $tipoMuestra = trim((string) ($cultivoItem->tipo_muestra_nombre ?? ''));
        if ($tipoMuestra === '' && $priaId > 0) {
            $tipoMuestra = trim((string) ($reportPriaTipoMuestra[$priaId] ?? ''));
        }
        $metodo = trim((string) ($cultivoItem->metodo_nombre ?? ''));
        if ($metodo === '' && $priaId > 0) {
            $metodo = trim((string) ($reportPriaMetodo[$priaId] ?? ''));
        }

        $rowCount = self::countCultivoRows($cultivoItem);
        if ($rowCount <= 0) {
            return null;
        }

        return new AnalysisBlockNode(
            id: self::analysisBlockId($areaIndex, $blockIndex),
            areaIndex: $areaIndex,
            blockIndex: $blockIndex,
            groupTitle: $hijo !== '' ? $hijo : $padre,
            tipoMuestra: $tipoMuestra,
            metodo: $metodo,
            isSubPrueba: $subIdx > 0,
            isCultivoMatrix: true,
            tables: [
                new TableSectionNode(
                    sectionIndex: 0,
                    isMatrix: false,
                    hasSegmentTitle: false,
                    rowCount: $rowCount,
                    includeThead: $includeThead,
                ),
            ],
        );
    }

    /**
     * @param list<mixed> $subItems
     */
    private static function resolveHijoTitulo(array $subItems): string
    {
        foreach ($subItems as $rawHijo) {
            $itHijo = is_array($rawHijo) ? (object) $rawHijo : $rawHijo;
            if ((int) ($itHijo->es_separador ?? 0) === 1) {
                continue;
            }
            $h = trim((string) ($itHijo->hijo ?? ''));
            if ($h !== '') {
                return $h;
            }
        }
        if ($subItems === []) {
            return '';
        }
        $first = $subItems[0];
        $firstObj = is_array($first) ? (object) $first : $first;

        return trim((string) ($firstObj->hijo ?? ''));
    }

    /**
     * @param list<mixed> $subItems
     *
     * @return list<array{title: object|null, items: list<mixed>}>
     */
    private static function splitSegments(array $subItems): array
    {
        $segments = [];
        $cur = ['title' => null, 'items' => []];
        foreach ($subItems as $raw) {
            $it = is_array($raw) ? (object) $raw : $raw;
            if ((int) ($it->es_separador ?? 0) === 1) {
                $segments[] = $cur;
                $cur = ['title' => $it, 'items' => []];

                continue;
            }
            $cur['items'][] = $it;
        }
        $segments[] = $cur;

        return array_values(array_filter($segments, static function ($s) {
            return $s['title'] !== null || $s['items'] !== [];
        }));
    }

    /**
     * @param list<mixed> $items
     */
    private static function countVisibleRows(array $items): int
    {
        $rows = 0;
        foreach ($items as $rawIt) {
            $it = is_array($rawIt) ? (object) $rawIt : $rawIt;
            $v = trim((string) ($it->regvalues ?? ''));
            if (($v !== '' && $v !== '-') || ! empty($it->show_reference)) {
                $rows++;
            }
        }

        return $rows;
    }

    /**
     * @param list<array<string, mixed>> $matrixRows
     */
    private static function countMatrixRows(array $matrixRows): int
    {
        $rows = 0;
        foreach ($matrixRows as $mrow) {
            $mrow = is_array($mrow) ? $mrow : [];
            if (function_exists('registro_tiene_rango_referencial')
                && registro_tiene_rango_referencial($mrow['valor_min'] ?? '', $mrow['valor_max'] ?? '')) {
                $rows++;
            }
        }

        return $rows;
    }

    private static function countCultivoRows(object $cultivoItem): int
    {
        $secciones = is_array($cultivoItem->cultivo_display ?? null) ? $cultivoItem->cultivo_display : [];
        $rows = 0;
        foreach ($secciones as $sec) {
            if (! is_array($sec)) {
                continue;
            }
            $rows += self::countCultivoSectionRows($sec);
        }

        return $rows;
    }

    /**
     * Filas visibles de una sección cultivo (misma lógica que cultivo_matriz_reporte.php).
     *
     * @param array<string, mixed> $sec
     */
    private static function countCultivoSectionRows(array $sec): int
    {
        $grillaReporte = is_array($sec['grilla_reporte'] ?? null) ? $sec['grilla_reporte'] : null;
        if ($grillaReporte !== null) {
            $titulosFilasGrilla = is_array($grillaReporte['titulos_filas'] ?? null)
                ? $grillaReporte['titulos_filas']
                : [];
            $filasGrilla = is_array($grillaReporte['filas'] ?? null) ? $grillaReporte['filas'] : [];
            if ($titulosFilasGrilla === [] && $filasGrilla === []) {
                return 0;
            }

            $rows = count($titulosFilasGrilla);
            foreach ($filasGrilla as $fila) {
                if (is_array($fila) && $fila !== []) {
                    $rows++;
                }
            }

            return $rows;
        }

        $columnasDetalle = is_array($sec['columnas_detalle'] ?? null) ? $sec['columnas_detalle'] : [];
        if ($columnasDetalle !== []) {
            $titulosBanda = is_array($sec['titulos_banda'] ?? null) ? $sec['titulos_banda'] : [];
            $maxColumnRows = 0;
            $hasColumnContent = false;
            foreach ($columnasDetalle as $colDet) {
                if (! is_array($colDet)) {
                    continue;
                }
                $titulosFilasCol = is_array($colDet['titulos_filas'] ?? null) ? $colDet['titulos_filas'] : [];
                $valoresCol = is_array($colDet['valores'] ?? null) ? $colDet['valores'] : [];
                if ($titulosFilasCol === [] && $valoresCol === []) {
                    continue;
                }
                $hasColumnContent = true;
                $maxColumnRows = max($maxColumnRows, count($titulosFilasCol) + count($valoresCol));
            }

            return $hasColumnContent ? count($titulosBanda) + $maxColumnRows : 0;
        }

        $filas = is_array($sec['filas'] ?? null) ? $sec['filas'] : [];
        $rows = 0;
        foreach ($filas as $fila) {
            if (! is_array($fila)) {
                continue;
            }
            $celdas = is_array($fila['celdas'] ?? null) ? $fila['celdas'] : [];
            if ($celdas !== []) {
                $rows++;

                continue;
            }
            if ($fila !== []) {
                $rows++;
            }
        }

        return $rows;
    }

    public static function analysisBlockId(int $areaIndex, int $blockIndex): string
    {
        return 'area-' . $areaIndex . '-block-' . $blockIndex;
    }

    public static function signatureId(int $areaIndex, string $scope): string
    {
        if ($scope === SignatureBlockNode::SCOPE_BLOCK_END) {
            return 'signature-global';
        }

        return 'area-' . $areaIndex . '-signature';
    }
}
