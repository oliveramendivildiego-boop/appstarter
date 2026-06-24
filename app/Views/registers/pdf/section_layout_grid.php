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
    $n_rows_local = max($n_rows_local, $maxGridRow + 1);
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
        $regions = $regionsArr;

        // Para evitar dobles renders cuando hay colisiones (no deberían existir si el UI valida),
        // marcamos columnas ocupadas.
        $occupiedUntil = array_fill(0, $n, null);
        foreach ($regions as $reg) {
            $colStart = (int) $reg['col'];
            $spanReg  = (int) $reg['span'];
            for ($cc = $colStart; $cc < $colStart + $spanReg && $cc < $n; $cc++) {
                // Si hay superposición, nos quedamos con la primera región encontrada.
                if ($occupiedUntil[$cc] !== null) {
                    continue 2;
                }
            }
            for ($cc = $colStart; $cc < $colStart + $spanReg && $cc < $n; $cc++) {
                $occupiedUntil[$cc] = $colStart;
            }

            // Orden de stack por grid_stack.
            $stackItems = $reg['items'];
            usort($stackItems, static function ($A, $B) {
                return ($A['stack'] <=> $B['stack']) ?: 0;
            });
            $cellItems = [];
            foreach ($stackItems as $si) {
                $cellItems[] = $cellFromExplicitItem($si['it'], $colStart, $spanReg);
            }

            if ($spanReg > 1) {
                $colspans[] = ['col' => $colStart, 'span' => $spanReg, 'items' => $cellItems];
            } else {
                $stacks[$colStart] = $cellItems;
            }
        }

        $rows[] = ['colspans' => $colspans, 'stacks' => $stacks];
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
    $ts = \App\Services\ReportPdfLayoutService::normalizeTextStyle($raw);
    $shadowMap = [
        'none'   => 'none',
        'soft'   => '0.4px 0.4px 1px rgba(0,0,0,0.28)',
        'medium' => '0.7px 0.7px 1.4px rgba(0,0,0,0.35)',
        'strong' => '1px 1px 2px rgba(0,0,0,0.45)',
    ];
    $shadow = $shadowMap[$ts['text_shadow']] ?? 'none';

    return 'font-family:' . $ts['font_family'] . ';'
        . 'font-size:' . $ts['font_size_pt'] . 'pt;'
        . 'font-weight:' . $ts['font_weight'] . ';'
        . 'color:' . $ts['font_color'] . ';'
        . 'font-style:' . $ts['font_style'] . ';'
        . 'text-transform:' . $ts['text_transform'] . ';'
        . 'letter-spacing:' . $ts['letter_spacing_em'] . 'em;'
        . 'line-height:' . $ts['line_height'] . ';'
        . 'text-shadow:' . $shadow . ';';
};

$itemAlignH = static function (array $item, int $col) use ($colAlignH): string {
    return \App\Services\ReportPdfLayoutService::resolveInstanceAlignH($item, $colAlignH, $col);
};
$itemAlignV = static function (array $item, int $col) use ($colAlignV): string {
    return \App\Services\ReportPdfLayoutService::resolveInstanceAlignV($item, $colAlignV, $col);
};
$itemWrapperStyle = static function (array $item, int $col, string $typography = '') use ($colAlignH, $colAlignV): string {
    return \App\Services\ReportPdfLayoutService::instanceAlignInlineStyle($item, $colAlignH, $colAlignV, $col, $typography);
};
$itemWrapperClasses = static function (array $item, int $col) use ($colAlignH, $colAlignV): string {
    return \App\Services\ReportPdfLayoutService::instanceAlignItemClasses($item, $colAlignH, $colAlignV, $col);
};
$pdfTdStyle = static function (int $startCol, int $span, float $pctUnit, int $rowIndex, array $cellItems = []) use ($n, $lineHeight, $cellPadCss, $rowGapPx, $cellBorderCss, $itemAlignH, $itemAlignV): array {
    $startCol = max(0, min($n - 1, $startCol));
    $h        = 'left';
    $v        = 'top';
    if (count($cellItems) === 1) {
        $only = $cellItems[0];
        $h    = $itemAlignH($only, $startCol);
        $v    = $itemAlignV($only, $startCol);
    } elseif (count($cellItems) > 1) {
        $h = 'left';
        $v = 'top';
    }
    $h        = in_array($h, ['left', 'center', 'right'], true) ? $h : 'left';
    $v        = in_array($v, ['top', 'middle', 'bottom'], true) ? $v : 'top';
    $alignCls = $h === 'left' ? 'left' : ($h === 'right' ? 'right' : 'center');
    $spanPct  = round($span * $pctUnit, 4);
    $rowPad   = ($rowIndex > 0 && $rowGapPx > 0) ? ('padding-top:' . $rowGapPx . 'px;') : '';
    /* !important: dompdf a veces aplica vertical-align:top de hojas de estilo sobre el td sin esto */
    $style    = 'width:' . $spanPct . '%;line-height:' . $lineHeight . ';text-align:' . $h . ' !important;vertical-align:' . $v . ' !important;padding:' . $cellPadCss . ';' . $rowPad . $cellBorderCss($startCol);

    return ['alignCls' => $alignCls, 'style' => $style];
};

$pdfEmptyTdStyle = static function (int $colIdx, float $pctUnit, int $rowIndex = 0) use ($lineHeight, $emptyCellPad, $rowGapPx, $cellBorderCss): string {
    $rowPad = ($rowIndex > 0 && $rowGapPx > 0) ? ('padding-top:' . $rowGapPx . 'px;') : '';

    return 'width:' . $pctUnit . '%;vertical-align:top !important;padding:' . $emptyCellPad . ';line-height:' . $lineHeight . ';' . $rowPad . $cellBorderCss($colIdx);
};
?>
<div class="<?= esc($section_wrapper_class) ?>"<?php
$sectionWrapperStyle = trim((string) ($section_wrapper_style ?? ''));
echo $sectionWrapperStyle !== '' ? ' style="' . esc($sectionWrapperStyle, 'attr') . '"' : '';
?>>
<?php if (trim((string) ($section_prepend_markup ?? '')) !== ''): ?>
<?= $section_prepend_markup ?>
<?php endif; ?>
<?php if (count($rows) > 0): ?>
<table class="pdf-section-table" width="100%" data-pdf-lh="1" style="table-layout:fixed;border-collapse:collapse;line-height:<?= esc((string) $lineHeight, 'attr') ?>;">
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
            $tdInfo   = $pdfTdStyle($startCol, $span, $pct, (int) $rowIndex, $block['items']);
            ?>
        <td class="pdf-cell pdf-cell--<?= esc($tdInfo['alignCls']) ?>" colspan="<?= $span ?>" style="<?= esc($tdInfo['style'], 'attr') ?>">
            <?php foreach ($block['items'] as $cellItem):
                $elType = (string) ($cellItem['element_type'] ?? '');
                $isCustomText = ($elType === 'custom_text');
                $typography   = $isCustomText ? '' : $textStyleCss(is_array($cellItem['text_style'] ?? null) ? $cellItem['text_style'] : []);
                $itemCol      = (int) ($cellItem['col'] ?? $startCol);
                $wrapStyle    = $itemWrapperStyle($cellItem, $itemCol, $typography !== '' ? $typography . ';' : '');
                $wrapClasses  = $itemWrapperClasses($cellItem, $itemCol);
                $itemAlignCls = $itemAlignH($cellItem, $itemCol);
                $elCtx        = array_merge($element_ctx, [
                    'pdf_element_type' => $cellItem['element_type'],
                    'pdf_instance_uid' => (string) ($cellItem['uid'] ?? ''),
                    'pdf_section_key'  => (string) ($section_key ?? ''),
                    'pdf_cell_align'   => $itemAlignCls,
                    'pdf_grid_row'     => (int) $rowIndex,
                    'pdf_grid_column'  => (int) ($cellItem['col'] ?? 0),
                    'pdf_grid_column_span' => (int) ($cellItem['span'] ?? 1),
                    'pdf_grid_stack'   => array_key_exists('grid_stack', $cellItem) ? (int) ($cellItem['grid_stack'] ?? 0) : 0,
                    'pdf_text_style'   => is_array($cellItem['text_style'] ?? null) ? $cellItem['text_style'] : [],
                    'pdf_label_value_gap_px' => array_key_exists('label_value_gap_px', $cellItem) ? (int) ($cellItem['label_value_gap_px'] ?? 0) : null,
                    'pdf_label_space_above_px' => array_key_exists('label_space_above_px', $cellItem) ? (int) ($cellItem['label_space_above_px'] ?? 0) : null,
                    'pdf_label_space_below_px' => array_key_exists('label_space_below_px', $cellItem) ? (int) ($cellItem['label_space_below_px'] ?? 0) : null,
                    'pdf_custom_text'  => $isCustomText
                        ? \App\Services\ReportPdfLayoutService::normalizeCustomTextPayload($cellItem['custom_text'] ?? [])
                        : null,
                ]);
                if ($elType === 'lab_firmas_title') {
                    echo view('registers/pdf/partials/element', $elCtx);
                } else {
                    ?>
            <div class="pdf-el-item <?= esc($wrapClasses, 'attr') ?>" style="<?= esc($wrapStyle, 'attr') ?>">
                <?= view('registers/pdf/partials/element', $elCtx) ?>
            </div>
            <?php
                }
            endforeach; ?>
        </td>
<?php
            $c += $span;
        elseif ($stacks[$c] !== null && count($stacks[$c]) > 0):
            $stackItems = $stacks[$c];
            $tdInfo = $pdfTdStyle($c, 1, $pct, (int) $rowIndex, $stackItems);
            ?>
        <td class="pdf-cell pdf-cell--<?= esc($tdInfo['alignCls']) ?>" style="<?= esc($tdInfo['style'], 'attr') ?>">
            <?php foreach ($stackItems as $stackItem):
                $elType = (string) ($stackItem['element_type'] ?? '');
                $isCustomText = ($elType === 'custom_text');
                $typography   = $isCustomText ? '' : $textStyleCss(is_array($stackItem['text_style'] ?? null) ? $stackItem['text_style'] : []);
                $itemCol      = (int) ($stackItem['col'] ?? $c);
                $wrapStyle    = $itemWrapperStyle($stackItem, $itemCol, $typography !== '' ? $typography . ';' : '');
                $wrapClasses  = $itemWrapperClasses($stackItem, $itemCol);
                $itemAlignCls = $itemAlignH($stackItem, $itemCol);
                $elCtx        = array_merge($element_ctx, [
                    'pdf_element_type' => $stackItem['element_type'],
                    'pdf_instance_uid' => (string) ($stackItem['uid'] ?? ''),
                    'pdf_section_key'  => (string) ($section_key ?? ''),
                    'pdf_cell_align'   => $itemAlignCls,
                    'pdf_grid_row'     => (int) $rowIndex,
                    'pdf_grid_column'  => (int) ($stackItem['col'] ?? $c),
                    'pdf_grid_column_span' => (int) ($stackItem['span'] ?? 1),
                    'pdf_grid_stack'   => array_key_exists('grid_stack', $stackItem) ? (int) ($stackItem['grid_stack'] ?? 0) : 0,
                    'pdf_text_style'   => is_array($stackItem['text_style'] ?? null) ? $stackItem['text_style'] : [],
                    'pdf_label_value_gap_px' => array_key_exists('label_value_gap_px', $stackItem) ? (int) ($stackItem['label_value_gap_px'] ?? 0) : null,
                    'pdf_label_space_above_px' => array_key_exists('label_space_above_px', $stackItem) ? (int) ($stackItem['label_space_above_px'] ?? 0) : null,
                    'pdf_label_space_below_px' => array_key_exists('label_space_below_px', $stackItem) ? (int) ($stackItem['label_space_below_px'] ?? 0) : null,
                    'pdf_custom_text'  => $isCustomText
                        ? \App\Services\ReportPdfLayoutService::normalizeCustomTextPayload($stackItem['custom_text'] ?? [])
                        : null,
                ]);
                if ($elType === 'lab_firmas_title') {
                    echo view('registers/pdf/partials/element', $elCtx);
                } else {
                    ?>
            <div class="pdf-el-item <?= esc($wrapClasses, 'attr') ?>" style="<?= esc($wrapStyle, 'attr') ?>">
                <?= view('registers/pdf/partials/element', $elCtx) ?>
            </div>
            <?php
                }
            endforeach; ?>
        </td>
<?php
            $c++;
        else: ?>
        <td style="<?= esc($pdfEmptyTdStyle($c, $pct, (int) $rowIndex), 'attr') ?>"></td>
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
