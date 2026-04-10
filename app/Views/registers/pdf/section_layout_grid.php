<?php
declare(strict_types=1);

/** @var string $section_wrapper_class */
/** @var int $n_columns */
/** @var list<array{element_type: string, column: int, column_span: int, text_style?: array<string, mixed>}> $grid_items */
/** @var array<string, mixed> $element_ctx */
/** @var array<string, mixed>|null $section_layout opcional: interlineado y alineación por columna */
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

    return ['element_type' => $type, 'col' => $col, 'span' => $span, 'text_style' => $textStyle];
};

$rangesOverlap = static function (int $a0, int $a1, int $b0, int $b1): bool {
    return $a0 < $b1 && $b0 < $a1;
};

/** @var list<array{colspans: list<array{col: int, span: int, items: list<array}>}, stacks: list<list<array>|null>}> */
$rows = [];
$i    = 0;

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

$secLayoutRaw = is_array($section_layout ?? null) ? $section_layout : [];
$secStyle     = \App\Services\ReportPdfLayoutService::resolveSectionLayoutStyle($secLayoutRaw, $n);
$colAlignH    = $secStyle['column_align_h'];
$colAlignV    = $secStyle['column_align_v'];
$lineHeight   = $secStyle['line_height'];
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

$pdfTdStyle = static function (int $startCol, int $span, float $pctUnit) use ($n, $colAlignH, $colAlignV, $lineHeight): array {
    $startCol = max(0, min($n - 1, $startCol));
    $h        = $colAlignH[$startCol] ?? 'left';
    $v        = $colAlignV[$startCol] ?? 'top';
    $h        = in_array($h, ['left', 'center', 'right'], true) ? $h : 'left';
    $v        = in_array($v, ['top', 'middle', 'bottom'], true) ? $v : 'top';
    $alignCls = $h === 'left' ? 'left' : ($h === 'right' ? 'right' : 'center');
    $spanPct  = round($span * $pctUnit, 4);
    /* !important: dompdf a veces aplica vertical-align:top de hojas de estilo sobre el td sin esto */
    $style    = 'width:' . $spanPct . '%;line-height:' . $lineHeight . ';text-align:' . $h . ' !important;vertical-align:' . $v . ' !important;padding:0 6px;';

    return ['alignCls' => $alignCls, 'style' => $style];
};

$pdfEmptyTdStyle = static function (int $colIdx, float $pctUnit) use ($n, $colAlignV, $lineHeight): string {
    $colIdx = max(0, min($n - 1, $colIdx));
    $v      = $colAlignV[$colIdx] ?? 'top';
    $v      = in_array($v, ['top', 'middle', 'bottom'], true) ? $v : 'top';

    return 'width:' . $pctUnit . '%;vertical-align:' . $v . ' !important;padding:0 4px;line-height:' . $lineHeight . ';';
};
?>
<div class="<?= esc($section_wrapper_class) ?>">
<?php foreach ($rows as $row): ?>
<table class="pdf-section-table" width="100%" data-pdf-lh="1" style="table-layout:fixed;border-collapse:collapse;margin-bottom:6px;line-height:<?= esc((string) $lineHeight, 'attr') ?>;">
    <tr>
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
            $tdInfo   = $pdfTdStyle($startCol, $span, $pct);
            ?>
        <td class="pdf-cell pdf-cell--<?= esc($tdInfo['alignCls']) ?>" colspan="<?= $span ?>" style="<?= esc($tdInfo['style'], 'attr') ?>">
            <?php foreach ($block['items'] as $cellItem):
                $inlineStyle = $textStyleCss(is_array($cellItem['text_style'] ?? null) ? $cellItem['text_style'] : []);
                $elType      = (string) ($cellItem['element_type'] ?? '');
                $elCtx       = array_merge($element_ctx, [
                    'pdf_element_type' => $cellItem['element_type'],
                    'pdf_text_style'   => is_array($cellItem['text_style'] ?? null) ? $cellItem['text_style'] : [],
                ]);
                if ($elType === 'lab_firmas_title') {
                    echo view('registers/pdf/partials/element', $elCtx);
                } else {
                    ?>
            <div class="pdf-el-item" style="<?= esc($inlineStyle, 'attr') ?>">
                <?= view('registers/pdf/partials/element', $elCtx) ?>
            </div>
            <?php
                }
            endforeach; ?>
        </td>
<?php
            $c += $span;
        elseif ($stacks[$c] !== null && count($stacks[$c]) > 0):
            $tdInfo = $pdfTdStyle($c, 1, $pct);
            ?>
        <td class="pdf-cell pdf-cell--<?= esc($tdInfo['alignCls']) ?>" style="<?= esc($tdInfo['style'], 'attr') ?>">
            <?php foreach ($stacks[$c] as $stackItem):
                $inlineStyle = $textStyleCss(is_array($stackItem['text_style'] ?? null) ? $stackItem['text_style'] : []);
                $elType      = (string) ($stackItem['element_type'] ?? '');
                $elCtx       = array_merge($element_ctx, [
                    'pdf_element_type' => $stackItem['element_type'],
                    'pdf_text_style'   => is_array($stackItem['text_style'] ?? null) ? $stackItem['text_style'] : [],
                ]);
                if ($elType === 'lab_firmas_title') {
                    echo view('registers/pdf/partials/element', $elCtx);
                } else {
                    ?>
            <div class="pdf-el-item" style="<?= esc($inlineStyle, 'attr') ?>">
                <?= view('registers/pdf/partials/element', $elCtx) ?>
            </div>
            <?php
                }
            endforeach; ?>
        </td>
<?php
            $c++;
        else: ?>
        <td style="<?= esc($pdfEmptyTdStyle($c, $pct), 'attr') ?>"></td>
<?php
            $c++;
        endif;
    endwhile;
    ?>
    </tr>
</table>
<?php endforeach; ?>
</div>
