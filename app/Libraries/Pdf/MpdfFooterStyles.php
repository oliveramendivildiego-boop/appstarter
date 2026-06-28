<?php

namespace App\Libraries\Pdf;

/**
 * Estilos del pie para mPDF: CSS en el documento principal + HTML inline en SetHTMLFooter().
 */
final class MpdfFooterStyles
{
    /**
     * Inyecta reglas del pie en el &lt;head&gt; del documento (mPDF las aplica al SetHTMLFooter).
     *
     * @param array<string, mixed> $layout
     */
    public static function injectDocumentFooterCss(string $html, array $layout): string
    {
        if ($layout === [] || ! \App\Services\ReportPdfLayoutService::isPdfFooterBlockEnabledForLayout($layout)) {
            return $html;
        }

        $css = self::buildFooterCssRules($layout);
        if ($css === '') {
            return $html;
        }

        $inject = "<style>\n/* mPDF footer (plantilla) */\n" . $css . "\n</style>\n";

        if (stripos($html, '</head>') !== false) {
            return str_ireplace('</head>', $inject . '</head>', $html);
        }

        return $inject . $html;
    }

    /**
     * @param array<string, mixed> $layout
     */
    public static function buildFooterCssRules(array $layout): string
    {
        $ps = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : [];
        $ft = \App\Services\ReportPdfLayoutService::normalizeFooterGridStyle($ps['footer_grid'] ?? []);

        $bg     = ! empty($ft['body_transparent']) ? 'transparent' : (string) ($ft['body_bg_color'] ?? '#ffffff');
        $lh     = max(1.0, (float) ($ft['line_height'] ?? 1.35));
        $colW   = max(0, min(4, (int) ($ft['column_border_width_px'] ?? 0)));
        $colClr = (string) ($ft['column_border_color'] ?? '#DDDDDD');
        $padTop = \App\Services\ReportPdfLayoutService::footerBlockPadTopPx($ft);

        $secLayouts = is_array($layout['section_layouts'] ?? null) ? $layout['section_layouts'] : [];
        $ftSec      = is_array($secLayouts['footer'] ?? null) ? $secLayouts['footer'] : [];
        $rowGapPx   = max(0, min(40, (int) ($ftSec['row_gap_px'] ?? 0)));
        $cellPadH   = max(0, min(12, (int) round($rowGapPx / 2)));
        $tableBorderTop = \App\Services\ReportPdfLayoutService::footerGridSectionTableBorderTopCss($ft, true);

        $css = <<<CSS
.mpdf-ft-root,
.pdf-ft-block.footer-grid.mpdf-ft-root {
    margin: 0;
    padding: {$padTop}px 0 0 0;
    background: {$bg};
    box-sizing: border-box;
    width: 100%;
    font-family: dejavusans, sans-serif;
    font-size: {$ft['font_size_pt']}pt;
    line-height: {$lh};
    color: {$ft['body_text_color']};
}
.mpdf-ft-root .mpdf-ft-table,
.pdf-ft-block.footer-grid.mpdf-ft-root .pdf-section-table {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
    margin: 0;
    {$tableBorderTop}
}
.mpdf-ft-root .mpdf-ft-table td.mpdf-ft-cell,
.pdf-ft-block.footer-grid.mpdf-ft-root .pdf-section-table td.mpdf-ft-cell {
    vertical-align: top;
    padding: 0 {$cellPadH}px;
    line-height: {$lh};
    overflow-wrap: break-word;
    word-wrap: break-word;
    word-break: break-word;
    white-space: normal;
    box-sizing: border-box;
}
.mpdf-ft-root .pdf-ft-piece,
.mpdf-ft-root .pdf-ft-piece p,
.pdf-ft-block.footer-grid.mpdf-ft-root .pdf-ft-piece,
.pdf-ft-block.footer-grid.mpdf-ft-root .pdf-ft-piece p {
    overflow-wrap: break-word;
    word-wrap: break-word;
    word-break: break-word;
    white-space: normal;
    max-width: 100%;
}
.mpdf-ft-root .mpdf-ft-stack,
.pdf-ft-block.footer-grid.mpdf-ft-root .pdf-ft-stack-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    margin: 0;
}
.mpdf-ft-root .mpdf-ft-stack td,
.pdf-ft-block.footer-grid.mpdf-ft-root .pdf-ft-stack-table td {
    padding: 0;
    vertical-align: top;
    border: 0;
    overflow-wrap: break-word;
    word-wrap: break-word;
    word-break: break-word;
    white-space: normal;
}
.mpdf-ft-root .pdf-ft-piece p,
.mpdf-ft-root .mpdf-ft-piece,
.pdf-ft-block.footer-grid.mpdf-ft-root .pdf-ft-piece {
    margin: 0;
}
.mpdf-ft-root .pdf-ft-pagination p,
.pdf-ft-block.footer-grid.mpdf-ft-root .pdf-ft-pagination p {
    margin: 0;
    white-space: nowrap;
}
.mpdf-ft-root .pdf-ft-pagination-label,
.mpdf-ft-root .pdf-ft-pagination-num,
.pdf-ft-block.footer-grid.mpdf-ft-root .pdf-ft-pagination-label,
.pdf-ft-block.footer-grid.mpdf-ft-root .pdf-ft-pagination-num {
    display: inline;
    white-space: nowrap;
    vertical-align: baseline;
}
.mpdf-ft-root .mpdf-ft-table .mpdf-order-sheet-row td,
.pdf-ft-block.footer-grid.mpdf-ft-root .mpdf-ft-table .mpdf-order-sheet-row td {
    font-family: dejavusans, sans-serif;
    font-size: 8pt;
    font-weight: bold;
    color: #333333;
    white-space: nowrap;
    border-bottom: 1px solid #cccccc;
    padding-bottom: 4px;
    vertical-align: middle;
}
.mpdf-ft-root table.mpdf-order-sheet-above .mpdf-order-sheet-row td,
.pdf-ft-block.footer-grid.mpdf-ft-root table.mpdf-order-sheet-above .mpdf-order-sheet-row td {
    border-bottom: none !important;
    padding-bottom: 0;
}
.mpdf-ft-root .mpdf-order-sheet-patient,
.pdf-ft-block.footer-grid.mpdf-ft-root .mpdf-order-sheet-patient {
    text-align: left !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
}
.mpdf-ft-root .mpdf-order-sheet-order,
.pdf-ft-block.footer-grid.mpdf-ft-root .mpdf-order-sheet-order {
    text-align: right !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
}
.mpdf-ft-root table.mpdf-order-sheet-above,
.pdf-ft-block.footer-grid.mpdf-ft-root table.mpdf-order-sheet-above {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
    margin: 0 0 2px 0;
    border: 0;
}
.mpdf-ft-root table.mpdf-order-sheet-above td.mpdf-order-sheet-patient,
.pdf-ft-block.footer-grid.mpdf-ft-root table.mpdf-order-sheet-above td.mpdf-order-sheet-patient {
    width: 40% !important;
    max-width: 40% !important;
    text-align: left !important;
    font-weight: bold;
    padding: 0;
    vertical-align: middle;
}
.mpdf-ft-root table.mpdf-order-sheet-above td.mpdf-order-sheet-spacer,
.pdf-ft-block.footer-grid.mpdf-ft-root table.mpdf-order-sheet-above td.mpdf-order-sheet-spacer {
    width: 20% !important;
    max-width: 20% !important;
    padding: 0;
    border: 0;
}
.mpdf-ft-root table.mpdf-order-sheet-above td.mpdf-order-sheet-order,
.pdf-ft-block.footer-grid.mpdf-ft-root table.mpdf-order-sheet-above td.mpdf-order-sheet-order {
    width: 40% !important;
    max-width: 40% !important;
    text-align: right !important;
    font-weight: bold;
    padding: 0;
    vertical-align: middle;
}
CSS;

        if ($colW > 0) {
            $css .= "\n.mpdf-ft-root .mpdf-ft-cell + .mpdf-ft-cell,\n.pdf-ft-block.footer-grid.mpdf-ft-root .mpdf-ft-cell + .mpdf-ft-cell { border-left: {$colW}px solid {$colClr}; }\n";
        }

        $secLayouts = is_array($layout['section_layouts'] ?? null) ? $layout['section_layouts'] : [];
        $ftSec      = is_array($secLayouts['footer'] ?? null) ? $secLayouts['footer'] : [];
        $nCols      = max(1, (int) ($ftSec['columns'] ?? 5));
        $colPct     = round(100 / $nCols, 4);
        $css .= "\n/* mPDF pie: anchos de columna y alineación */\n";
        $css .= ".mpdf-ft-root table.mpdf-ft-table[data-pdf-cols=\"{$nCols}\"] > colgroup > col { width: {$colPct}%; }\n";
        for ($span = 1; $span <= $nCols; $span++) {
            $spanW = round($span * $colPct, 4);
            if ($span === 1) {
                $css .= ".mpdf-ft-root table.mpdf-ft-table[data-pdf-cols=\"{$nCols}\"] > tbody > tr > td.mpdf-ft-cell:not([colspan]) { width: {$spanW}% !important; max-width: {$spanW}% !important; }\n";
            } else {
                $css .= ".mpdf-ft-root table.mpdf-ft-table[data-pdf-cols=\"{$nCols}\"] > tbody > tr > td.mpdf-ft-cell[colspan=\"{$span}\"] { width: {$spanW}% !important; max-width: {$spanW}% !important; }\n";
            }
        }
        $alignRules = [
            'left'   => 'left',
            'center' => 'center',
            'right'  => 'right',
        ];
        foreach ($alignRules as $cls => $val) {
            $css .= ".mpdf-ft-root td.pdf-cell--{$cls},\n"
                . ".mpdf-ft-root td.pdf-cell--h-{$cls},\n"
                . ".mpdf-ft-root td.pdf-cell-stack-item.pdf-cell--h-{$cls},\n"
                . ".mpdf-ft-root td[align=\"{$val}\"] { text-align: {$val} !important; }\n";
            $css .= ".mpdf-ft-root td.pdf-cell--{$cls} .pdf-ft-piece,\n"
                . ".mpdf-ft-root td.pdf-cell--h-{$cls} .pdf-ft-piece,\n"
                . ".mpdf-ft-root td[align=\"{$val}\"] .pdf-ft-piece,\n"
                . ".mpdf-ft-root td.pdf-cell--{$cls} .pdf-ft-piece p,\n"
                . ".mpdf-ft-root td.pdf-cell--h-{$cls} .pdf-ft-piece p,\n"
                . ".mpdf-ft-root td[align=\"{$val}\"] .pdf-ft-piece p { text-align: {$val} !important; }\n";
        }

        return $css;
    }

    /**
     * Prepara el HTML del pie para SetHTMLFooter() — solo inline + clases (sin &lt;style&gt; en el fragmento).
     *
     * @param array<string, mixed> $layout
     */
    public static function wrapForSetHtmlFooter(string $footerInnerHtml, array $layout): string
    {
        $footerInnerHtml = trim($footerInnerHtml);
        if ($footerInnerHtml === '') {
            return '';
        }

        $footerInnerHtml = self::stripDompdfArtifacts($footerInnerHtml);
        $footerInnerHtml = HtmlMpdfAdapter::adaptFooterForMpdf($footerInnerHtml);
        $footerInnerHtml = self::ensureRootInlineStyle($footerInnerHtml, $layout);
        $footerInnerHtml = self::ensureFooterTableInlineBorderTop($footerInnerHtml, $layout);

        return $footerInnerHtml;
    }

    /**
     * Borde superior del pie en style inline de .mpdf-ft-table (mPDF SetHTMLFooter no usa var() del head).
     *
     * @param array<string, mixed> $layout
     */
    private static function ensureFooterTableInlineBorderTop(string $html, array $layout): string
    {
        $ps = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : [];
        $ft = \App\Services\ReportPdfLayoutService::normalizeFooterGridStyle($ps['footer_grid'] ?? []);
        $border = trim(\App\Services\ReportPdfLayoutService::footerGridSectionTableBorderStyleAttr($ft));
        if ($border === '') {
            return $html;
        }

        return preg_replace_callback(
            '/(<table\b[^>]*\bmpdf-ft-table\b[^>]*)\sstyle=(["\'])([^"\']*)\2/i',
            static function (array $m) use ($border): string {
                $style = preg_replace('/\bborder-top\s*:\s*[^;]+;?\s*/i', '', $m[3]) ?? $m[3];
                $style = trim($style, " \t\n\r\0\x0B;");
                if ($style !== '') {
                    $style .= ';';
                }
                $style .= $border;

                return $m[1] . ' style=' . $m[2] . $style . $m[2];
            },
            $html,
            1,
        ) ?? preg_replace_callback(
            '/(<table\b[^>]*\bmpdf-ft-table\b[^>]*)(>)/i',
            static function (array $m) use ($border): string {
                return $m[1] . ' style="' . htmlspecialchars($border, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"' . $m[2];
            },
            $html,
            1,
        ) ?? $html;
    }

    /**
     * @param array<string, mixed> $layout
     */
    private static function ensureRootInlineStyle(string $html, array $layout): string
    {
        $ps = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : [];
        $ft = \App\Services\ReportPdfLayoutService::normalizeFooterGridStyle($ps['footer_grid'] ?? []);
        $bg = ! empty($ft['body_transparent']) ? 'transparent' : (string) ($ft['body_bg_color'] ?? '#ffffff');
        $lh = max(1.0, (float) ($ft['line_height'] ?? 1.35));
        $padTop = \App\Services\ReportPdfLayoutService::footerBlockPadTopPx($ft);

        $rootStyle = sprintf(
            'margin:0;padding:%dpx 0 0 0;background:%s;box-sizing:border-box;width:100%%;font-family:dejavusans,sans-serif;font-size:%spt;line-height:%s;color:%s;',
            $padTop,
            $bg,
            (string) $ft['font_size_pt'],
            (string) $lh,
            (string) $ft['body_text_color'],
        );

        if (preg_match('/<div\s+class="[^"]*\bmpdf-ft-root\b[^"]*"([^>]*)>/i', $html, $m)) {
            $attrs = preg_replace('/\s*style\s*=\s*(["\']).*?\1/i', '', $m[1]) ?? $m[1];
            $attrs = trim($attrs);
            $attrSuffix = $attrs !== '' ? (' ' . $attrs) : '';
            $html = preg_replace(
                '/<div\s+class="([^"]*\bmpdf-ft-root\b[^"]*)"[^>]*>/i',
                '<div class="$1"' . $attrSuffix . " style='" . $rootStyle . "'>",
                $html,
                1,
            ) ?? $html;

            return $html;
        }

        return '<div class="mpdf-ft-root pdf-ft-block footer-grid" style=\'' . $rootStyle . '\'>' . $html . '</div>';
    }

    private static function stripDompdfArtifacts(string $html): string
    {
        $html = preg_replace('/<div\s+class="pdf-dompdf-footer-anchor"[^>]*>/i', '', $html) ?? $html;
        $html = preg_replace('/<\/div>\s*(?=<!--\s*report-pdf-footer:end)/i', '', $html) ?? $html;

        return preg_replace_callback(
            '/\bstyle=(["\'])([^"\']*)\1/i',
            static function (array $m): string {
                $style = $m[2];
                $style = preg_replace(
                    '/(?<![a-z-])(?:position|left|right|z-index|height|overflow)\s*:\s*[^;"\']+;?/i',
                    '',
                    $style,
                ) ?? $style;
                $style = preg_replace(
                    '/(?<![a-z-])top\s*:\s*[^;"\']+;?/i',
                    '',
                    $style,
                ) ?? $style;
                $style = preg_replace(
                    '/(?<![a-z-])bottom\s*:\s*[^;"\']+;?/i',
                    '',
                    $style,
                ) ?? $style;
                $style = preg_replace('/\s*;+\s*/', ';', $style) ?? $style;
                $style = trim($style, " \t\n\r\0\x0B;");

                return $style === '' ? '' : ('style=' . $m[1] . $style . $m[1]);
            },
            $html,
        ) ?? $html;
    }

    /**
     * @param array<string, mixed> $layout
     */
    public static function resolveLayoutMetrics(array $layout): array
    {
        $mm = is_array($layout['margins_mm'] ?? null)
            ? $layout['margins_mm']
            : \App\Services\ReportPdfLayoutService::defaultMarginsMmStatic();

        $footerReserve = \App\Services\ReportPdfLayoutService::isPdfFooterBlockEnabledForLayout($layout)
            ? \App\Services\ReportPdfLayoutService::estimatePdfFooterReserveMm($layout)
            : 0.0;

        return [
            'top'               => max(0.0, (float) ($mm['top'] ?? 15)),
            'right'             => max(0.0, (float) ($mm['right'] ?? 15)),
            'bottom'            => max(0.0, (float) ($mm['bottom'] ?? 15)),
            'left'              => max(0.0, (float) ($mm['left'] ?? 25)),
            'footer_reserve_mm' => max(0.0, $footerReserve),
        ];
    }
}
