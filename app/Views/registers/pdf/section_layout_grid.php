<?php
declare(strict_types=1);

/** @var string $section_wrapper_class */
/** @var int $n_columns */
/** @var list<array{element_type: string, column: int, column_span: int, text_style?: array<string, mixed>}> $grid_items */
/** @var array<string, mixed> $element_ctx */
/** @var array<string, mixed>|null $section_layout opcional: interlineado y alineación por columna */
$secLayoutRawEarly = is_array($section_layout ?? null) ? $section_layout : [];
$n_rows_config     = max(1, min(50, (int) ($secLayoutRawEarly['rows'] ?? 1)));
$n_rows_local      = isset($n_rows) ? max(1, (int) $n_rows) : $n_rows_config;
$n         = max(1, (int) $n_columns);
$pct       = round(100 / $n, 4);
$items     = $grid_items;
$itemCount = count($items);

$normalizeItem = static function (array $it, int $n): array {
    $type = (string) ($it['element_type'] ?? '');
    $col  = max(0, min($n - 1, (int) ($it['column'] ?? 0)));
    $span = max(1, (int) ($it['column_span'] ?? 1));
    $span = min($span, max(1, $n - $col));
    $textStyle = is_array($it['text_style'] ?? null) ? $it['text_style'] : [];

    $out = ['element_type' => $type, 'col' => $col, 'span' => $span, 'text_style' => $textStyle];
    if (! empty($it['uid'])) {
        $out['uid'] = (string) $it['uid'];
    }
    if ($type === 'custom_text' && is_array($it['custom_text'] ?? null)) {
        $out['custom_text'] = $it['custom_text'];
    }
    // Overrides opcionales por instancia (paciente/médico).
    foreach (['label_value_gap_px', 'label_space_above_px', 'label_space_below_px'] as $k) {
        if (array_key_exists($k, $it)) {
            $out[$k] = (int) ($it[$k] ?? 0);
        }
    }
    foreach (['align_h', 'align_v'] as $ak) {
        if (array_key_exists($ak, $it) && $it[$ak] !== null && $it[$ak] !== '') {
            $out[$ak] = (string) $it[$ak];
        }
    }

    return $out;
};

$rangesOverlap = static function (int $a0, int $a1, int $b0, int $b1): bool {
    return $a0 < $b1 && $b0 < $a1;
};

// Si existe `grid_row` en las instancias, renderizamos en modo grilla explícita.
$explicitGrid = false;
$maxGridRow   = 0;
foreach ($items as $it) {
    if (! is_array($it)) {
        continue;
    }
    if (array_key_exists('grid_row', $it) && $it['grid_row'] !== null) {
        $explicitGrid = true;
        $gr = (int) ($it['grid_row'] ?? 0);
        $maxGridRow = max($maxGridRow, $gr);
    }
}

/** @var list<array{colspans: list<array{col: int, span: int, items: list<array}>}, stacks: list<list<array>|null>}> */
$rows = [];

if ($explicitGrid) {
    // Solo filas con instancias (evita filas vacías del canvas del editor en el PDF).
    $n_rows_local = max(1, $maxGridRow + 1);
    $itemsByRow = array_fill(0, $n_rows_local, []);
    foreach ($items as $it) {
        if (! is_array($it)) {
            continue;
        }
        if (! array_key_exists('grid_row', $it) || $it['grid_row'] === null) {
            continue;
        }
        $r = (int) ($it['grid_row'] ?? 0);
        if ($r < 0 || $r >= $n_rows_local) {
            continue;
        }
        $itemsByRow[$r][] = $it;
    }

    $cellFromExplicitItem = static function (array $it, int $col, int $span): array {
        $type = (string) ($it['element_type'] ?? '');
        $ts   = is_array($it['text_style'] ?? null) ? $it['text_style'] : [];
        $out  = ['element_type' => $type, 'col' => $col, 'span' => $span, 'text_style' => $ts];
        if (! empty($it['uid'])) {
            $out['uid'] = (string) $it['uid'];
        }
        if (array_key_exists('grid_stack', $it)) {
            $out['grid_stack'] = (int) ($it['grid_stack'] ?? 0);
        }
        foreach (['label_value_gap_px', 'label_space_above_px', 'label_space_below_px'] as $k) {
            if (array_key_exists($k, $it)) {
                $out[$k] = (int) ($it[$k] ?? 0);
            }
        }
        foreach (['align_h', 'align_v'] as $ak) {
            if (array_key_exists($ak, $it) && $it[$ak] !== null && $it[$ak] !== '') {
                $out[$ak] = (string) $it[$ak];
            }
        }
        if ($type === 'custom_text' && is_array($it['custom_text'] ?? null)) {
            $out['custom_text'] = $it['custom_text'];
        }
        return $out;
    };

    $placeRegionsOnRow = static function (array $regionsArr, int $nCols, callable $cellFromItem): array {
        $colspans      = [];
        $stacks        = array_fill(0, $nCols, null);
        $occupiedUntil = array_fill(0, $nCols, null);
        foreach ($regionsArr as $reg) {
            $colStart = (int) $reg['col'];
            $spanReg  = (int) $reg['span'];
            for ($cc = $colStart; $cc < $colStart + $spanReg && $cc < $nCols; $cc++) {
                if ($occupiedUntil[$cc] !== null) {
                    continue 2;
                }
            }
            for ($cc = $colStart; $cc < $colStart + $spanReg && $cc < $nCols; $cc++) {
                $occupiedUntil[$cc] = $colStart;
            }

            $stackItems = $reg['items'];
            usort($stackItems, static function ($A, $B) {
                return ($A['stack'] <=> $B['stack']) ?: 0;
            });
            $cellItems = [];
            foreach ($stackItems as $si) {
                $cellItems[] = $cellFromItem($si['it'], $colStart, $spanReg);
            }

            if ($spanReg > 1) {
                $colspans[] = ['col' => $colStart, 'span' => $spanReg, 'items' => $cellItems];
            } else {
                $stacks[$colStart] = $cellItems;
            }
        }

        return ['colspans' => $colspans, 'stacks' => $stacks];
    };

    for ($r = 0; $r < $n_rows_local; $r++) {
        $colspans = [];
        $stacks   = array_fill(0, $n, null);

        // Regiones agrupadas por (col inicio + span) => stack de instancias.
        /** @var array<string, array{col:int, span:int, items:list<array>}> $regions */
        $regions = [];
        foreach ($itemsByRow[$r] as $it) {
            if (! is_array($it)) {
                continue;
            }
            $colStart = max(0, min($n - 1, (int) ($it['column'] ?? 0)));
            $span     = max(1, (int) ($it['column_span'] ?? 1));
            $span     = min($span, max(1, $n - $colStart));
            $stackIx  = (int) ($it['grid_stack'] ?? 0);

            $key = $colStart . ':' . $span;
            if (! isset($regions[$key])) {
                $regions[$key] = ['col' => $colStart, 'span' => $span, 'items' => []];
            }
            $regions[$key]['items'][] = ['stack' => $stackIx, 'it' => $it];
        }

        // Renderizamos por orden de columna inicial para que la iteración sea consistente.
        $regionsArr = array_values($regions);
        usort($regionsArr, static function ($a, $b) {
            return ($a['col'] <=> $b['col']) ?: ($a['span'] <=> $b['span']);
        });

        $rows[] = $placeRegionsOnRow($regionsArr, $n, $cellFromExplicitItem);
    }
} else {
    $i = 0;
    while ($i < $itemCount) {
    /** @var list<array{col: int, span: int, items: list<array}>} */
    $colspans = [];
    /** @var list<list<array{element_type: string, col: int, span: int}>|null> */
    $stacks = array_fill(0, $n, null);

    $canPlace = static function (array $norm) use (&$colspans, &$stacks, $rangesOverlap): bool {
        $c = $norm['col'];
        $s = $norm['span'];
        if ($s > 1) {
            foreach ($colspans as $C) {
                if ($C['col'] === $c && $C['span'] === $s) {
                    return true;
                }
            }
            foreach ($colspans as $C) {
                if ($rangesOverlap($c, $c + $s, $C['col'], $C['col'] + $C['span'])) {
                    return false;
                }
            }
            for ($k = $c; $k < $c + $s; $k++) {
                if ($stacks[$k] !== null && count($stacks[$k]) > 0) {
                    return false;
                }
            }

            return true;
        }
        foreach ($colspans as $C) {
            if ($c >= $C['col'] && $c < $C['col'] + $C['span']) {
                return false;
            }
        }

        return true;
    };

    $place = static function (array $norm) use (&$colspans, &$stacks): void {
        $c = $norm['col'];
        $s = $norm['span'];
        if ($s > 1) {
            foreach ($colspans as $idx => $C) {
                if ($C['col'] === $c && $C['span'] === $s) {
                    $colspans[$idx]['items'][] = $norm;

                    return;
                }
            }
            $colspans[] = ['col' => $c, 'span' => $s, 'items' => [$norm]];
        } else {
            if ($stacks[$c] === null) {
                $stacks[$c] = [];
            }
            $stacks[$c][] = $norm;
        }
    };

    $j = $i;
    while ($j < $itemCount) {
        $norm = $normalizeItem($items[$j], $n);
        if (! $canPlace($norm)) {
            break;
        }
        $place($norm);
        $j++;
    }

    if ($j === $i) {
        $i++;

        continue;
    }

        $rows[] = ['colspans' => $colspans, 'stacks' => $stacks];
        $i      = $j;
    }
}

$secLayoutRaw = $secLayoutRawEarly;
$secStyle     = \App\Services\ReportPdfLayoutService::resolveSectionLayoutStyle($secLayoutRaw, $n);
$colAlignH    = $secStyle['column_align_h'];
$colAlignV    = $secStyle['column_align_v'];
$lineHeight   = $secStyle['line_height'];
$sectionKeyStr = (string) ($section_key ?? '');
$mpdfFooterMode = ! empty($mpdf_footer_mode) || ! empty($element_ctx['mpdf_footer_mode'] ?? false);
$mpdfGridWidths = \App\Libraries\Pdf\PdfEngine::isMpdf();
$mpdfNoColspan    = $mpdfGridWidths || $mpdfFooterMode;
$cellWidthAttrForSpan = static function (int $spanCols) use ($pct, $mpdfGridWidths, $mpdfFooterMode): string {
    if (! $mpdfGridWidths && ! $mpdfFooterMode) {
        return '';
    }
    $spanCols = max(1, $spanCols);

    return ' width="' . esc((string) round($spanCols * $pct, 4), 'attr') . '%"';
};
$defRowGap     = $sectionKeyStr === 'patient_doctor' ? 2 : ($sectionKeyStr === 'footer' ? 0 : 6);
$rowGapPx      = max(0, min(40, (int) ($secLayoutRaw['row_gap_px'] ?? $defRowGap)));
$gridStyleRaw  = [];
if ($sectionKeyStr === 'footer') {
    $gridStyleRaw = is_array($element_ctx['pdf_footer_grid_style'] ?? null) ? $element_ctx['pdf_footer_grid_style'] : [];
} elseif ($sectionKeyStr === 'header') {
    $gridStyleRaw = is_array($element_ctx['pdf_header_grid_style'] ?? null) ? $element_ctx['pdf_header_grid_style'] : [];
} elseif ($sectionKeyStr === 'patient_doctor') {
    $gridStyleRaw = is_array($element_ctx['pdf_patient_doctor_grid_style'] ?? null) ? $element_ctx['pdf_patient_doctor_grid_style'] : [];
}
$colBorderW    = max(0, min(4, (int) ($gridStyleRaw['column_border_width_px'] ?? 0)));
$colBorderColor = \App\Services\ReportPdfLayoutService::isValidPdfHexColor((string) ($gridStyleRaw['column_border_color'] ?? ''))
    ? (string) $gridStyleRaw['column_border_color']
    : '#DDDDDD';
$footerHPadPx  = ($sectionKeyStr === 'footer') ? max(0, min(12, (int) round($rowGapPx / 2))) : 0;
$cellPadCss    = $sectionKeyStr === 'footer'
    ? ($footerHPadPx > 0 ? ('0 ' . $footerHPadPx . 'px') : '0')
    : '0 6px';
$emptyCellPad  = $sectionKeyStr === 'footer'
    ? ($footerHPadPx > 0 ? ('0 ' . $footerHPadPx . 'px') : '0')
    : '0 4px';
$cellBorderCss = static function (int $startCol) use ($colBorderW, $colBorderColor): string {
    if ($startCol <= 0 || $colBorderW <= 0) {
        return '';
    }

    return 'border-left:' . $colBorderW . 'px solid ' . $colBorderColor . ';';
};
$textStyleCss = static function (array $raw): string {
    return \App\Services\ReportPdfLayoutService::textStyleNormalizedToInlineCss(
        \App\Services\ReportPdfLayoutService::normalizeTextStyle($raw)
    );
};

$itemAlignH = static function (array $item, int $col) use ($colAlignH): string {
    return \App\Services\ReportPdfLayoutService::resolveInstanceAlignH($item, $colAlignH, $col);
};
$itemAlignV = static function (array $item, int $col) use ($colAlignV): string {
    return \App\Services\ReportPdfLayoutService::resolveInstanceAlignV($item, $colAlignV, $col);
};
$resolveGridStack = static function (array $item, int $indexInCell): int {
    return $indexInCell;
};
$itemTypographyCss = static function (array $item, string $elType) use ($textStyleCss, $lineHeight, $sectionKeyStr, $gridStyleRaw): string {
    if ($elType === 'custom_text' && is_array($item['custom_text'] ?? null)) {
        $ct = \App\Services\ReportPdfLayoutService::normalizeCustomTextPayload($item['custom_text']);

        return \App\Services\ReportPdfLayoutService::textStyleArrayToInlineCss($ct['value_style']);
    }

    $sectionTypo = \App\Services\ReportPdfLayoutService::gridSectionBodyTypographyCss($sectionKeyStr, $gridStyleRaw);
    if ($sectionTypo !== '') {
        return $sectionTypo;
    }

    return $textStyleCss(is_array($item['text_style'] ?? null) ? $item['text_style'] : []);
};
$resolveItemTextStyle = static function (array $item) use ($sectionKeyStr, $gridStyleRaw): array {
    return \App\Services\ReportPdfLayoutService::resolveGridInstanceTextStyle(
        $sectionKeyStr,
        $gridStyleRaw,
        is_array($item['text_style'] ?? null) ? $item['text_style'] : [],
    );
};
$headerRowHeightsPx = [];
if ($sectionKeyStr === 'header') {
    foreach ($rows as $rowIndex => $row) {
        $maxH = 0;
        $scanItems = static function (array $items) use (&$maxH, $element_ctx, $n, $gridStyleRaw, $resolveItemTextStyle, $lineHeight): void {
            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $itemResolved         = $item;
                $itemResolved['text_style'] = $resolveItemTextStyle($item);
                $elType               = (string) ($item['element_type'] ?? '');
                $maxH = max($maxH, \App\Services\ReportPdfLayoutService::gridLogoRenderedHeightPx($itemResolved, $element_ctx, $n));
                if ($elType === 'lab_company') {
                    $maxH = max(
                        $maxH,
                        \App\Services\ReportPdfLayoutService::typographyBlockHeightPx($itemResolved['text_style'], $lineHeight),
                    );
                }
                if ($elType === 'qr') {
                    $maxH = max($maxH, \App\Services\ReportPdfLayoutService::gridQrRenderedHeightPx($gridStyleRaw));
                }
            }
        };
        foreach ($row['colspans'] as $C) {
            $scanItems(is_array($C['items'] ?? null) ? $C['items'] : []);
        }
        foreach ($row['stacks'] as $stackItems) {
            if (is_array($stackItems)) {
                $scanItems($stackItems);
            }
        }
        if ($maxH <= 0) {
            $hasItems = false;
            foreach ($row['colspans'] as $C) {
                if (count(is_array($C['items'] ?? null) ? $C['items'] : []) > 0) {
                    $hasItems = true;
                    break;
                }
            }
            if (! $hasItems) {
                foreach ($row['stacks'] as $stackItems) {
                    if (is_array($stackItems) && count($stackItems) > 0) {
                        $hasItems = true;
                        break;
                    }
                }
            }
            if ($hasItems) {
                $maxH = 48;
            }
        }
        if ($maxH > 0) {
            $headerRowHeightsPx[(int) $rowIndex] = $maxH;
        }
    }
}
$pdfTdStyle = static function (int $startCol, int $span, float $pctUnit, int $rowIndex, array $cellItems = [], int $rowHeightPx = 0) use ($n, $lineHeight, $cellPadCss, $rowGapPx, $cellBorderCss, $itemAlignH, $itemAlignV, $sectionKeyStr, $colAlignH, $colAlignV, $itemTypographyCss, $mpdfFooterMode, $mpdfGridWidths): array {
    $startCol = max(0, min($n - 1, $startCol));
    $h        = 'left';
    $v        = 'top';
    $alignCellClasses = '';
    $typography       = '';
    if (count($cellItems) === 1) {
        $only = $cellItems[0];
        $h    = $itemAlignH($only, $startCol);
        $v    = $itemAlignV($only, $startCol);
        $alignCellClasses = \App\Services\ReportPdfLayoutService::instanceAlignCellClasses($only, $colAlignH, $colAlignV, $startCol);
        $typography       = $itemTypographyCss($only, (string) ($only['element_type'] ?? ''));
        // Encabezado con altura de fila fija: respetar align_v guardado en logo, QR y company.
        if ($sectionKeyStr === 'header' && $rowHeightPx > 0) {
            $onlyType = (string) ($only['element_type'] ?? '');
            if (in_array($onlyType, ['logo', 'qr', 'lab_company'], true)) {
                $explicitV = isset($only['align_v']) ? strtolower(trim((string) $only['align_v'])) : '';
                if (in_array($explicitV, ['middle', 'bottom'], true)) {
                    $v = $explicitV;
                    $alignCellClasses = \App\Services\ReportPdfLayoutService::instanceAlignCellClasses(
                        array_merge($only, ['align_v' => $explicitV]),
                        $colAlignH,
                        $colAlignV,
                        $startCol,
                    );
                }
            }
        }
    } elseif (count($cellItems) > 1 && $sectionKeyStr === 'footer') {
        $first = $cellItems[0];
        $h     = $itemAlignH($first, $startCol);
        $v     = 'top';
        $alignCellClasses = \App\Services\ReportPdfLayoutService::instanceAlignCellClasses($first, $colAlignH, $colAlignV, $startCol);
    } elseif (count($cellItems) > 1) {
        $h = 'left';
        $v = 'top';
    }
    $h        = in_array($h, ['left', 'center', 'right'], true) ? $h : 'left';
    $v        = in_array($v, ['top', 'middle', 'bottom'], true) ? $v : 'top';
    $alignCls = $h === 'left' ? 'left' : ($h === 'right' ? 'right' : 'center');
    $spanPct  = round($span * $pctUnit, 4);
    $rowPad   = ($rowIndex > 0 && $rowGapPx > 0) ? ('padding-top:' . $rowGapPx . 'px;') : '';
    $widthCss = 'width:' . $spanPct . '%;';
    if ($mpdfFooterMode && $sectionKeyStr === 'footer') {
        $widthCss .= 'max-width:' . $spanPct . '%;overflow-wrap:break-word;word-wrap:break-word;word-break:break-word;white-space:normal;';
    } elseif ($mpdfGridWidths && in_array($sectionKeyStr, ['header', 'patient_doctor'], true)) {
        $widthCss .= 'max-width:' . $spanPct . '%;';
    }
    /* !important: dompdf a veces aplica vertical-align:top de hojas de estilo sobre el td sin esto */
    $style    = $widthCss . 'line-height:' . $lineHeight . ';text-align:' . $h . ' !important;vertical-align:' . $v . ' !important;padding:' . $cellPadCss . ';' . $rowPad . $cellBorderCss($startCol);
    if ($typography !== '') {
        $style .= $typography . ';';
    }
    $explicitHeightPx = 0;
    if ($rowHeightPx > 0 && $v !== 'top') {
        $explicitHeightPx = $rowHeightPx;
        $style .= 'height:' . $explicitHeightPx . 'px;';
    }
    $valign   = match ($v) {
        'middle' => 'middle',
        'bottom' => 'bottom',
        default  => 'top',
    };

    return [
        'alignCls'           => $alignCls,
        'style'              => $style,
        'valign'             => $valign,
        'alignCellClasses'   => $alignCellClasses,
        'explicitHeightPx'   => $explicitHeightPx,
    ];
};

$cellItemTdInfo = static function (array $cellItem, int $startCol) use ($itemAlignH, $itemAlignV, $itemTypographyCss, $colAlignH, $colAlignV): array {
    $itemCol  = (int) ($cellItem['col'] ?? $startCol);
    $elType   = (string) ($cellItem['element_type'] ?? '');
    $h        = $itemAlignH($cellItem, $itemCol);
    $v        = $itemAlignV($cellItem, $itemCol);
    $h        = in_array($h, ['left', 'center', 'right'], true) ? $h : 'left';
    $v        = in_array($v, ['top', 'middle', 'bottom'], true) ? $v : 'top';
    $alignCls = $h === 'left' ? 'left' : ($h === 'right' ? 'right' : 'center');
    $typography = $itemTypographyCss($cellItem, $elType);
    $style    = 'padding:0;border:0;line-height:inherit;text-align:' . $h . ' !important;vertical-align:' . $v . ' !important;';
    if ($typography !== '') {
        $style .= $typography . ';';
    }
    $valign = match ($v) {
        'middle' => 'middle',
        'bottom' => 'bottom',
        default  => 'top',
    };

    return [
        'alignCls'     => $alignCls,
        'style'        => $style,
        'valign'       => $valign,
        'cellClasses'  => \App\Services\ReportPdfLayoutService::instanceAlignCellClasses($cellItem, $colAlignH, $colAlignV, $itemCol),
    ];
};

$pdfEmptyTdStyle = static function (int $colIdx, float $pctUnit, int $rowIndex = 0) use ($lineHeight, $emptyCellPad, $rowGapPx, $cellBorderCss): string {
    $rowPad = ($rowIndex > 0 && $rowGapPx > 0) ? ('padding-top:' . $rowGapPx . 'px;') : '';

    return 'width:' . $pctUnit . '%;vertical-align:top !important;padding:' . $emptyCellPad . ';line-height:' . $lineHeight . ';' . $rowPad . $cellBorderCss($colIdx);
};

$renderCellStackItems = static function (array $cellItems, int $startCol, int $gridRowIndex) use (
    $sectionKeyStr,
    $rowGapPx,
    $element_ctx,
    $section_key,
    $n,
    $itemAlignH,
    $itemAlignV,
    $resolveGridStack,
    $mpdfFooterMode,
    $cellItemTdInfo,
    $headerRowHeightsPx,
    $resolveItemTextStyle
): void {
    $useInnerStackTable = count($cellItems) > 1;
    if ($useInnerStackTable) {
        $stackTableClass = 'pdf-cell-stack-table';
        if ($sectionKeyStr === 'footer') {
            $stackTableClass .= ' pdf-ft-stack-table';
            if ($mpdfFooterMode) {
                $stackTableClass .= ' mpdf-ft-stack';
            }
        }
        echo '<table class="' . esc($stackTableClass, 'attr') . '" width="100%" cellpadding="0" cellspacing="0" style="table-layout:fixed;width:100%;border-collapse:collapse;">';
    }
    foreach ($cellItems as $stackIndex => $cellItem) {
        $elType       = (string) ($cellItem['element_type'] ?? '');
        $isCustomText = ($elType === 'custom_text');
        $itemCol      = (int) ($cellItem['col'] ?? $startCol);
        $itemAlignCls = $itemAlignH($cellItem, $itemCol);
        $itemVAlign   = $itemAlignV($cellItem, $itemCol);
        $elCtx        = array_merge($element_ctx, [
            'pdf_element_type'       => $cellItem['element_type'],
            'pdf_instance_uid'       => (string) ($cellItem['uid'] ?? ''),
            'pdf_section_key'        => (string) ($section_key ?? ''),
            'pdf_section_columns'    => $n,
            'pdf_cell_align'         => $itemAlignCls,
            'pdf_cell_valign'        => $itemVAlign,
            'pdf_row_height_px'      => (int) ($headerRowHeightsPx[$gridRowIndex] ?? 0),
            'pdf_grid_row'           => (int) $gridRowIndex,
            'pdf_grid_column'        => (int) ($cellItem['col'] ?? $startCol),
            'pdf_grid_column_span'   => (int) ($cellItem['span'] ?? 1),
            'pdf_grid_stack'         => $resolveGridStack($cellItem, (int) $stackIndex),
            'pdf_text_style'         => $resolveItemTextStyle($cellItem),
            'pdf_label_value_gap_px' => array_key_exists('label_value_gap_px', $cellItem) ? (int) ($cellItem['label_value_gap_px'] ?? 0) : null,
            'pdf_label_space_above_px' => array_key_exists('label_space_above_px', $cellItem) ? (int) ($cellItem['label_space_above_px'] ?? 0) : null,
            'pdf_label_space_below_px' => array_key_exists('label_space_below_px', $cellItem) ? (int) ($cellItem['label_space_below_px'] ?? 0) : null,
            'pdf_custom_text'        => $isCustomText
                ? \App\Services\ReportPdfLayoutService::normalizeCustomTextPayload($cellItem['custom_text'] ?? [])
                : null,
        ]);
        if ($useInnerStackTable) {
            $stackInfo = $cellItemTdInfo($cellItem, $startCol);
            $stackPad  = ($stackIndex > 0 && $rowGapPx > 0)
                ? ('padding-top:' . (int) $rowGapPx . 'px;')
                : '';
            $innerClass = 'pdf-cell pdf-cell-stack-item pdf-cell--' . $stackInfo['alignCls'] . ' ' . $stackInfo['cellClasses'];
            echo '<tr><td class="' . esc(trim($innerClass), 'attr') . '" align="' . esc($stackInfo['alignCls'], 'attr') . '" valign="' . esc($stackInfo['valign'], 'attr') . '" style="' . esc($stackInfo['style'] . $stackPad, 'attr') . '">';
            echo view('registers/pdf/partials/element', $elCtx);
            echo '</td></tr>';
        } else {
            echo view('registers/pdf/partials/element', $elCtx);
        }
    }
    if ($useInnerStackTable) {
        echo '</table>';
    }
};

$wrapCellValignTable = static function (array $tdInfo, callable $render): void {
    $valign = (string) ($tdInfo['valign'] ?? 'top');
    if ($valign === 'top') {
        $render();

        return;
    }
    $explicitH = (int) ($tdInfo['explicitHeightPx'] ?? 0);
    $hAttr     = $explicitH > 0 ? (' height="' . $explicitH . '"') : '';
    $hCss      = $explicitH > 0 ? ('height:' . $explicitH . 'px;') : 'height:100%;';
    $hAlign = match ((string) ($tdInfo['alignCls'] ?? 'left')) {
        'center' => 'center',
        'right'  => 'right',
        default  => 'left',
    };
    ?>
    <table class="pdf-cell-valign-table" width="100%"<?= $hAttr ?> cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%;<?= esc($hCss, 'attr') ?>table-layout:fixed;">
        <tr<?= $hAttr ?>>
            <td valign="<?= esc($valign, 'attr') ?>" align="<?= esc($hAlign, 'attr') ?>"<?= $hAttr ?> style="padding:0;border:0;line-height:inherit;text-align:<?= esc($hAlign, 'attr') ?> !important;vertical-align:<?= esc($valign, 'attr') ?> !important;<?= esc($hCss, 'attr') ?>">
                <?php $render(); ?>
            </td>
        </tr>
    </table>
    <?php
};
?>
<div class="<?= esc($section_wrapper_class) ?>"<?php
// Solo el pie fijo Dompdf usa section_wrapper_style. Con View::$saveData, section_key y
// section_wrapper_style del footer persisten y no deben aplicarse a otras cuadrículas.
$sectionWrapperClass = (string) ($section_wrapper_class ?? '');
$sectionWrapperStyle = '';
if (str_contains($sectionWrapperClass, 'pdf-ft-block') || str_contains($sectionWrapperClass, 'mpdf-ft-root')) {
    $sectionWrapperStyle = trim((string) ($section_wrapper_style ?? ''));
}
echo $sectionWrapperStyle !== '' ? ' style="' . esc($sectionWrapperStyle, 'attr') . '"' : '';
?>>
<?php if (trim((string) ($section_prepend_markup ?? '')) !== ''): ?>
<?= $section_prepend_markup ?>
<?php endif; ?>
<?php if (count($rows) > 0):
    $sectionTableStyle = 'table-layout:fixed;border-collapse:collapse;line-height:' . esc((string) $lineHeight, 'attr') . ';';
    $sectionTableClass = 'pdf-section-table';
    if ($sectionKeyStr === 'footer') {
        $sectionTableStyle .= \App\Services\ReportPdfLayoutService::footerGridSectionTableBorderStyleAttr($gridStyleRaw);
        if ($mpdfFooterMode) {
            $sectionTableClass .= ' mpdf-ft-table';
        }
    }
?>
<table class="<?= esc($sectionTableClass, 'attr') ?>" width="100%" data-pdf-lh="1" data-pdf-cols="<?= (int) $n ?>" style="<?= esc($sectionTableStyle, 'attr') ?>">
<?php if (! $mpdfNoColspan): ?>
<colgroup>
<?php for ($colIdx = 0; $colIdx < $n; $colIdx++): ?>
    <col style="width:<?= esc((string) $pct, 'attr') ?>%;" />
<?php endfor; ?>
</colgroup>
<?php endif; ?>
<?php if (trim((string) ($section_table_prepend_rows ?? '')) !== ''): ?>
<?= $section_table_prepend_rows ?>
<?php endif; ?>
<?php foreach ($rows as $rowIndex => $row): ?>
    <tr class="pdf-section-row" data-pdf-row="<?= (int) $rowIndex ?>">
<?php
    $colspans = $row['colspans'];
    $stacks   = $row['stacks'];
    $c        = 0;
    while ($c < $n):
        $block = null;
        foreach ($colspans as $C) {
            if ($C['col'] === $c) {
                $block = $C;
                break;
            }
        }
        if ($block !== null):
            $span     = $block['span'];
            $first    = $block['items'][0];
            $startCol = (int) $first['col'];
            $rowHeightPx = (int) ($headerRowHeightsPx[(int) $rowIndex] ?? 0);
            $tdInfo   = $pdfTdStyle($startCol, $span, $pct, (int) $rowIndex, $block['items'], $rowHeightPx);
            $tdCellClass = 'pdf-cell pdf-cell--' . $tdInfo['alignCls']
                . ($tdInfo['alignCellClasses'] !== '' ? ' ' . $tdInfo['alignCellClasses'] : '')
                . ((int) ($tdInfo['explicitHeightPx'] ?? 0) > 0 ? ' pdf-cell--has-explicit-height' : '')
                . ($mpdfFooterMode && $sectionKeyStr === 'footer' ? ' mpdf-ft-cell' : '');
            ?>
        <td class="<?= esc(trim($tdCellClass), 'attr') ?>"<?= $mpdfNoColspan ? '' : (' colspan="' . (int) $span . '"') ?> align="<?= esc($tdInfo['alignCls'], 'attr') ?>" valign="<?= esc($tdInfo['valign'], 'attr') ?>"<?= $cellWidthAttrForSpan($span) ?><?= (int) ($tdInfo['explicitHeightPx'] ?? 0) > 0 ? ' height="' . (int) $tdInfo['explicitHeightPx'] . '"' : '' ?> style="<?= esc($tdInfo['style'], 'attr') ?>">
            <?php $wrapCellValignTable($tdInfo, static function () use ($renderCellStackItems, $block, $startCol, $rowIndex): void {
                $renderCellStackItems($block['items'], $startCol, (int) $rowIndex);
            }); ?>
        </td>
<?php
            $c += $span;
        elseif ($stacks[$c] !== null && count($stacks[$c]) > 0):
            $stackItems = $stacks[$c];
            $rowHeightPx = (int) ($headerRowHeightsPx[(int) $rowIndex] ?? 0);
            $tdInfo = $pdfTdStyle($c, 1, $pct, (int) $rowIndex, $stackItems, $rowHeightPx);
            $tdCellClass = 'pdf-cell pdf-cell--' . $tdInfo['alignCls']
                . ($tdInfo['alignCellClasses'] !== '' ? ' ' . $tdInfo['alignCellClasses'] : '')
                . ((int) ($tdInfo['explicitHeightPx'] ?? 0) > 0 ? ' pdf-cell--has-explicit-height' : '')
                . ($mpdfFooterMode && $sectionKeyStr === 'footer' ? ' mpdf-ft-cell' : '');
            ?>
        <td class="<?= esc(trim($tdCellClass), 'attr') ?>" align="<?= esc($tdInfo['alignCls'], 'attr') ?>" valign="<?= esc($tdInfo['valign'], 'attr') ?>"<?= $cellWidthAttrForSpan(1) ?><?= (int) ($tdInfo['explicitHeightPx'] ?? 0) > 0 ? ' height="' . (int) $tdInfo['explicitHeightPx'] . '"' : '' ?> style="<?= esc($tdInfo['style'], 'attr') ?>">
            <?php $wrapCellValignTable($tdInfo, static function () use ($renderCellStackItems, $stackItems, $c, $rowIndex): void {
                $renderCellStackItems($stackItems, $c, (int) $rowIndex);
            }); ?>
        </td>
<?php
            $c++;
        else: ?>
        <td style="<?= esc($pdfEmptyTdStyle($c, $pct, (int) $rowIndex), 'attr') ?>"<?= $cellWidthAttrForSpan(1) ?>></td>
<?php
            $c++;
        endif;
    endwhile;
    ?>
    </tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</div>
