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

        $fontSizePt = (string) ($ft['font_size_pt'] ?? 10);
        $bodyColor  = (string) ($ft['body_text_color'] ?? '#333333');

        $css = <<<'CSS'
.mpdf-ft-root,
.pdf-ft-block.footer-grid.mpdf-ft-root {
    margin: 0;
    padding: {{padTop}}px 0 0 0;
    background: {{bg}};
    box-sizing: border-box;
    width: 100%;
    font-family: dejavusans, sans-serif;
    font-size: {{fontSizePt}}pt;
    line-height: {{lh}};
    color: {{bodyColor}};
}
.mpdf-ft-root .mpdf-ft-top-border,
.pdf-ft-block.footer-grid.mpdf-ft-root .mpdf-ft-top-border {
    display: block;
    width: 100%;
    height: 0;
    margin: 0;
    padding: 0;
    font-size: 0;
    line-height: 0;
    box-sizing: border-box;
}
.mpdf-ft-root .pdf-ft-pagination-num::before,
.pdf-ft-block.footer-grid.mpdf-ft-root .pdf-ft-pagination-num::before {
    content: none !important;
    display: none !important;
}
.mpdf-ft-root .mpdf-ft-table,
.pdf-ft-block.footer-grid.mpdf-ft-root .pdf-section-table {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
    margin: 0;
    border-top: none;
}
.mpdf-ft-root .mpdf-ft-table td.mpdf-ft-cell,
.pdf-ft-block.footer-grid.mpdf-ft-root .pdf-section-table td.mpdf-ft-cell {
    vertical-align: top;
    padding: 0 {{cellPadH}}px;
    line-height: {{lh}};
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

        $css = str_replace(
            [
                '{{padTop}}',
                '{{bg}}',
                '{{fontSizePt}}',
                '{{lh}}',
                '{{bodyColor}}',
                '{{tableBorderTop}}',
                '{{cellPadH}}',
            ],
            [
                (string) $padTop,
                $bg,
                $fontSizePt,
                (string) $lh,
                $bodyColor,
                $tableBorderTop,
                (string) $cellPadH,
            ],
            $css,
        );

        if ($colW > 0) {
            $css .= "\n.mpdf-ft-root .mpdf-ft-cell + .mpdf-ft-cell,\n"
                . '.pdf-ft-block.footer-grid.mpdf-ft-root .mpdf-ft-cell + .mpdf-ft-cell { border-left: '
                . $colW . 'px solid ' . $colClr . "; }\n";
        }

        $nCols      = max(1, (int) ($ftSec['columns'] ?? 5));
        $colPct     = round(100 / $nCols, 4);
        $css .= "\n/* mPDF pie: anchos de columna y alineación */\n";
        $css .= '.mpdf-ft-root table.mpdf-ft-table[data-pdf-cols="' . $nCols . '"] > colgroup > col { width: '
            . $colPct . "%; }\n";
        for ($span = 1; $span <= $nCols; $span++) {
            $spanW = round($span * $colPct, 4);
            if ($span === 1) {
                $css .= '.mpdf-ft-root table.mpdf-ft-table[data-pdf-cols="' . $nCols
                    . '"] > tbody > tr > td.mpdf-ft-cell:not([colspan]) { width: '
                    . $spanW . '% !important; max-width: ' . $spanW . "% !important; }\n";
            } else {
                $css .= '.mpdf-ft-root table.mpdf-ft-table[data-pdf-cols="' . $nCols
                    . '"] > tbody > tr > td.mpdf-ft-cell[colspan="' . $span . '"] { width: '
                    . $spanW . '% !important; max-width: ' . $spanW . "% !important; }\n";
            }
        }
        $alignRules = [
            'left'   => 'left',
            'center' => 'center',
            'right'  => 'right',
        ];
        foreach ($alignRules as $cls => $val) {
            $css .= '.mpdf-ft-root td.pdf-cell--' . $cls . ",\n"
                . '.mpdf-ft-root td.pdf-cell--h-' . $cls . ",\n"
                . '.mpdf-ft-root td.pdf-cell-stack-item.pdf-cell--h-' . $cls . ",\n"
                . '.mpdf-ft-root td[align="' . $val . '"] { text-align: ' . $val . " !important; }\n";
            $css .= '.mpdf-ft-root td.pdf-cell--' . $cls . " .pdf-ft-piece,\n"
                . '.mpdf-ft-root td.pdf-cell--h-' . $cls . " .pdf-ft-piece,\n"
                . '.mpdf-ft-root td[align="' . $val . '"] .pdf-ft-piece,' . "\n"
                . '.mpdf-ft-root td.pdf-cell--' . $cls . " .pdf-ft-piece p,\n"
                . '.mpdf-ft-root td.pdf-cell--h-' . $cls . " .pdf-ft-piece p,\n"
                . '.mpdf-ft-root td[align="' . $val . '"] .pdf-ft-piece p { text-align: ' . $val . " !important; }\n";
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

        $footerInnerHtml = HtmlMpdfAdapter::adaptFooterForMpdf($footerInnerHtml);
        $footerInnerHtml = self::stripDompdfArtifacts($footerInnerHtml);
        $footerInnerHtml = self::ensureRootInlineStyle($footerInnerHtml, $layout);
        $footerInnerHtml = self::ensureFooterTopBorderSeparator($footerInnerHtml, $layout);
        $footerInnerHtml = self::ensureWellFormedFooterTable($footerInnerHtml);
        // Mantener grilla 5 cols + colspan 2+1+2 (40/20/40) igual que vista previa; el simplificador a 3 cols rompía el centro.
        $footerInnerHtml = HtmlMpdfAdapter::adaptFooterForMpdf($footerInnerHtml);

        return $footerInnerHtml;
    }

    /**
     * Paridad con plantilla: alineación H/V, tipografía y estilos inline legibles por mPDF.
     *
     * @param array<string, mixed> $layout
     */
    public static function materializeFooterCellLayout(string $html, array $layout = []): string
    {
        unset($layout);

        if (! str_contains($html, 'mpdf-ft-cell') && ! str_contains($html, 'pdf-cell-stack-item')) {
            return $html;
        }

        $prev = libxml_use_internal_errors(true);
        $dom  = new \DOMDocument('1.0', 'UTF-8');
        $wrap = '<?xml encoding="utf-8"><div id="mpdf-ft-materialize-root">' . $html . '</div>';
        if (! $dom->loadHTML($wrap, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
            libxml_clear_errors();
            libxml_use_internal_errors($prev);

            return $html;
        }
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $xpath = new \DOMXPath($dom);
        $cells = $xpath->query('//td[contains(@class,"mpdf-ft-cell") or contains(@class,"pdf-cell-stack-item") or contains(@class,"mpdf-order-sheet-patient") or contains(@class,"mpdf-order-sheet-order")]');
        if ($cells === false) {
            return $html;
        }

        /** @var list<\DOMElement> $tdList */
        $tdList = [];
        foreach ($cells as $cell) {
            if ($cell instanceof \DOMElement) {
                $tdList[] = $cell;
            }
        }

        foreach ($tdList as $td) {
            $hAlign = self::resolveFooterCellHorizontalAlignFromElement($td);
            if ($hAlign === null) {
                continue;
            }

            self::applyHorizontalAlignToFooterTdElement($td, $hAlign);
            self::stripTextAlignFromDomSubtree($td);

            $hasNestedTd = false;
            foreach ($td->getElementsByTagName('td') as $nested) {
                if ($nested !== $td) {
                    $hasNestedTd = true;
                    break;
                }
            }

            if ($hasNestedTd && str_contains($td->getAttribute('class'), 'mpdf-ft-cell')) {
                foreach ($td->getElementsByTagName('table') as $table) {
                    if ($table instanceof \DOMElement && str_contains($table->getAttribute('class'), 'mpdf-ft-stack')) {
                        self::wrapDomElementWithHorizontalAlign($table, $hAlign);
                    }
                }
                continue;
            }

            if (! $hasNestedTd) {
                self::wrapDomCellChildrenWithHorizontalAlign($td, $hAlign);
            }
        }

        $root = $dom->getElementById('mpdf-ft-materialize-root');
        if ($root === null) {
            return $html;
        }

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return $out;
    }

    private static function resolveFooterCellHorizontalAlignFromElement(\DOMElement $td): ?string
    {
        $align = strtolower($td->getAttribute('align'));
        if (in_array($align, ['left', 'center', 'right'], true)) {
            return $align;
        }

        return self::resolveFooterCellHorizontalAlign(
            ' class="' . $td->getAttribute('class') . '" style="' . $td->getAttribute('style') . '"',
        );
    }

    private static function applyHorizontalAlignToFooterTdElement(\DOMElement $td, string $hAlign): void
    {
        $td->setAttribute('align', $hAlign);
        $style = $td->getAttribute('style');
        $style = preg_replace('/\btext-align\s*:\s*[^;]+;?/i', '', $style) ?? $style;
        $style = trim($style, " \t\n\r\0\x0B;");
        $style = $style === '' ? '' : ($style . ';');
        $td->setAttribute('style', $style . 'text-align:' . $hAlign . ' !important');
    }

    private static function stripTextAlignFromDomSubtree(\DOMElement $root): void
    {
        foreach ($root->getElementsByTagName('p') as $p) {
            if ($p instanceof \DOMElement) {
                self::stripTextAlignFromStyleAttr($p);
            }
        }
        foreach ($root->getElementsByTagName('div') as $div) {
            if ($div instanceof \DOMElement
                && preg_match('/\b(?:pdf-ft-piece|pdf-ft-pagination|pdf-ft-custom-text|footer-piece)\b/', $div->getAttribute('class'))) {
                self::stripTextAlignFromStyleAttr($div);
            }
        }
    }

    private static function stripTextAlignFromStyleAttr(\DOMElement $el): void
    {
        $style = $el->getAttribute('style');
        if ($style === '') {
            return;
        }
        $style = preg_replace('/\btext-align\s*:\s*[^;]+;?/i', '', $style) ?? $style;
        $style = preg_replace('/\s*;+\s*/', ';', $style) ?? $style;
        $style = trim($style, " \t\n\r\0\x0B;");
        if ($style === '') {
            $el->removeAttribute('style');
        } else {
            $el->setAttribute('style', $style);
        }
    }

    private static function wrapDomElementWithHorizontalAlign(\DOMElement $node, string $hAlign): void
    {
        if ($hAlign === 'left') {
            return;
        }
        $parent = $node->parentNode;
        if ($parent instanceof \DOMElement
            && ($parent->nodeName === 'center' || str_contains($parent->getAttribute('class'), 'mpdf-ft-align-wrap'))) {
            return;
        }
        $dom = $node->ownerDocument;
        if ($dom === null) {
            return;
        }

        $wrapper = $hAlign === 'center' ? $dom->createElement('center') : $dom->createElement('div');
        $wrapper->setAttribute('class', 'mpdf-ft-align-wrap');
        if ($hAlign === 'right') {
            $wrapper->setAttribute('align', 'right');
            $wrapper->setAttribute('style', 'text-align:right !important');
        }
        if ($parent instanceof \DOMNode) {
            $parent->insertBefore($wrapper, $node);
        }
        $wrapper->appendChild($node);
    }

    private static function wrapDomCellChildrenWithHorizontalAlign(\DOMElement $td, string $hAlign): void
    {
        if ($hAlign === 'left' || $td->childNodes->length === 0) {
            return;
        }
        foreach ($td->childNodes as $child) {
            if ($child instanceof \DOMElement
                && ($child->nodeName === 'center' || str_contains($child->getAttribute('class'), 'mpdf-ft-align-wrap'))) {
                return;
            }
        }
        $dom = $td->ownerDocument;
        if ($dom === null) {
            return;
        }

        $wrapper = $hAlign === 'center' ? $dom->createElement('center') : $dom->createElement('div');
        $wrapper->setAttribute('class', 'mpdf-ft-align-wrap');
        if ($hAlign === 'right') {
            $wrapper->setAttribute('align', 'right');
            $wrapper->setAttribute('style', 'text-align:right !important');
        }
        while ($td->firstChild !== null) {
            $wrapper->appendChild($td->firstChild);
        }
        $td->appendChild($wrapper);
    }

    private static function resolveFooterCellHorizontalAlign(string $attrs): ?string
    {
        if (preg_match('/\balign="(left|center|right)"/i', $attrs, $m)) {
            return strtolower($m[1]);
        }
        if (preg_match('/\bstyle=(["\'])((?:\\\\.|(?!\1).)*)\1/s', $attrs, $sm)) {
            $style = MpdfFontMapper::decodeAttrValue($sm[2]);
            if (preg_match('/\btext-align\s*:\s*(left|center|right)\b/i', $style, $tm)) {
                return strtolower($tm[1]);
            }
        }
        if (preg_match('/\bpdf-cell--h-(left|center|right)\b/i', $attrs, $m)) {
            return strtolower($m[1]);
        }
        if (preg_match('/\bpdf-cell--(left|center|right)\b/i', $attrs, $m)) {
            return strtolower($m[1]);
        }

        return null;
    }

    private static function resolveFooterCellVerticalAlign(string $attrs): ?string
    {
        if (preg_match('/\bvalign="(top|middle|bottom)"/i', $attrs, $m)) {
            return strtolower($m[1]);
        }
        if (preg_match('/\bpdf-cell--v-(top|middle|bottom)\b/i', $attrs, $m)) {
            return strtolower($m[1]);
        }

        return null;
    }

    private static function mergeStyleProperty(string $attrs, string $property, string $value): string
    {
        $declaration = $property . ':' . $value;

        if (! preg_match('/\bstyle=(["\'])((?:\\\\.|(?!\1).)*)\1/s', $attrs, $sm)) {
            return $attrs . ' style="' . $declaration . '"';
        }

        $style = MpdfFontMapper::decodeAttrValue($sm[2]);
        $style = preg_replace('/\b' . preg_quote($property, '/') . '\s*:\s*[^;]+;?/i', '', $style) ?? $style;
        $style = trim($style, " \t\n\r\0\x0B;");
        $style = $style === '' ? $declaration : ($style . ';' . $declaration);

        $newStyle = ' style="' . htmlspecialchars($style, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';

        return preg_replace('/\bstyle=(["\'])((?:\\\\.|(?!\1).)*)\1/s', $newStyle, $attrs, 1) ?? $attrs;
    }

    private static function materializeFooterTdAlignAttrs(string $attrs, string $hAlign): string
    {
        $attrs = preg_replace('/\salign\s*=\s*(["\'])(?:left|center|right)\1/i', '', $attrs) ?? $attrs;

        return self::mergeStyleProperty($attrs . ' align="' . $hAlign . '"', 'text-align', $hAlign . ' !important');
    }

    /**
     * mPDF SetHTMLFooter: &lt;center&gt; / align en contenedor (mPDF ignora text-align en &lt;p&gt; anidados).
     */
    private static function wrapFooterCellContentForMpdfHorizontalAlign(string $inner, string $hAlign): string
    {
        if ($hAlign === 'left' || trim($inner) === '' || stripos($inner, 'mpdf-ft-align-wrap') !== false) {
            return $inner;
        }

        if ($hAlign === 'center') {
            return '<center class="mpdf-ft-align-wrap">' . $inner . '</center>';
        }

        return '<div class="mpdf-ft-align-wrap" align="right" style="text-align:right !important">'
            . $inner
            . '</div>';
    }

    private static function stripTextAlignFromFooterCellContent(string $inner): string
    {
        return preg_replace_callback(
            '/<(p|div)\b([^>]*)>/i',
            static function (array $m): string {
                $tag   = $m[1];
                $attrs = $m[2];
                if ($tag === 'div' && ! preg_match('/\b(?:pdf-ft-piece|pdf-ft-pagination|pdf-ft-custom-text|footer-piece)\b/', $attrs)) {
                    return $m[0];
                }
                if (! preg_match('/\bstyle=(["\'])((?:\\\\.|(?!\1).)*)\1/s', $attrs, $sm)) {
                    return $m[0];
                }

                $style = MpdfFontMapper::decodeAttrValue($sm[2]);
                $style = preg_replace('/\btext-align\s*:\s*(?:left|center|right)\s*!important\s*;?/i', '', $style) ?? $style;
                $style = preg_replace('/\btext-align\s*:\s*(?:left|center|right)\s*;?/i', '', $style) ?? $style;
                $style = preg_replace('/\s*;+\s*/', ';', $style) ?? $style;
                $style = trim($style, " \t\n\r\0\x0B;");

                if ($style === '') {
                    $newAttrs = preg_replace('/\bstyle=(["\'])((?:\\\\.|(?!\1).)*)\1/s', '', $attrs, 1) ?? $attrs;

                    return '<' . $tag . trim($newAttrs) . '>';
                }

                $newStyle = ' style="' . htmlspecialchars($style, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';

                return '<' . $tag . (preg_replace('/\bstyle=(["\'])((?:\\\\.|(?!\1).)*)\1/s', $newStyle, $attrs, 1) ?? $attrs) . '>';
            },
            $inner,
        ) ?? $inner;
    }

    /** @deprecated mPDF alinea en &lt;td&gt;, no en &lt;p&gt; */
    private static function applyHorizontalAlignToFooterCellContent(string $inner, string $hAlign): string
    {
        unset($hAlign);

        return $inner;
    }

    /**
     * @param array<string, mixed> $layout
     */
    public static function finalizeSetHtmlFooterFragment(string $html, array $layout = []): string
    {
        $html = HtmlMpdfAdapter::adaptFooterForMpdf($html);

        return self::materializeFooterCellLayout($html, $layout);
    }

    /** @deprecated use materializeFooterCellLayout */
    public static function materializeFooterCellAlignments(string $html): string
    {
        return self::materializeFooterCellLayout($html);
    }

    private static function mergeStyleDeclaration(string $attrs, string $declaration): string
    {
        if (preg_match('/^(text-align|vertical-align)\s*:/i', $declaration, $m)) {
            $prop = strtolower($m[1]);
            $val  = trim(substr($declaration, strlen($m[0])));

            return self::mergeStyleProperty($attrs, $prop, $val);
        }

        if (! preg_match('/\bstyle=(["\'])((?:\\\\.|(?!\1).)*)\1/s', $attrs, $sm)) {
            return $attrs . ' style="' . $declaration . '"';
        }

        $style = MpdfFontMapper::decodeAttrValue($sm[2]);
        $style = trim($style, " \t\n\r\0\x0B;");
        $style = $style === '' ? $declaration : ($style . ';' . $declaration);
        $newStyle = ' style="' . htmlspecialchars($style, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';

        return preg_replace('/\bstyle=(["\'])((?:\\\\.|(?!\1).)*)\1/s', $newStyle, $attrs, 1) ?? $attrs;
    }

    private static function propagateTextAlignToFooterPieces(string $inner, string $align): string
    {
        return self::applyHorizontalAlignToFooterCellContent($inner, $align);
    }

    /**
     * Garantiza que la etiqueta &lt;table class="mpdf-ft-table"&gt; esté bien cerrada.
     */
    private static function ensureWellFormedFooterTable(string $html): string
    {
        if (preg_match('/<table\b[^>]*\bmpdf-ft-table\b[^>]*>/i', $html)) {
            return $html;
        }

        $fixed = preg_replace(
            '/(<table\b[^>]*\bmpdf-ft-table\b[^>]*)(\s*(?:<colgroup|<tbody|<tr))/i',
            '$1>$2',
            $html,
            1,
        );

        return is_string($fixed) ? $fixed : $html;
    }

    /**
     * Línea superior del pie: div dedicado (mPDF no pinta bien border-top en la tabla del SetHTMLFooter).
     *
     * @param array<string, mixed> $layout
     */
    private static function ensureFooterTopBorderSeparator(string $html, array $layout): string
    {
        $ps = is_array($layout['page_style'] ?? null) ? $layout['page_style'] : [];
        $ft = \App\Services\ReportPdfLayoutService::normalizeFooterGridStyle($ps['footer_grid'] ?? []);
        $border = trim(\App\Services\ReportPdfLayoutService::footerGridSectionTableBorderStyleAttr($ft));

        $html = self::stripFooterTableBorderTop($html);

        if ($border === '' || str_starts_with(strtolower($border), 'border-top:none')) {
            return $html;
        }

        if (str_contains($html, 'mpdf-ft-top-border')) {
            return $html;
        }

        $sepStyle = 'display:block;width:100%;height:0;margin:0;padding:0;font-size:0;line-height:0;box-sizing:border-box;'
            . $border;
        $separator = '<div class="mpdf-ft-top-border" style="'
            . htmlspecialchars($sepStyle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '"></div>';

        $replaced = preg_replace(
            '/(<table\b[^>]*\bmpdf-ft-table\b)/i',
            $separator . '$1',
            $html,
            1,
        );

        return is_string($replaced) ? $replaced : $html;
    }

    private static function stripFooterTableBorderTop(string $html): string
    {
        return preg_replace_callback(
            '/(<table\b[^>]*\bmpdf-ft-table\b)([^>]*)(>)/i',
            static function (array $m): string {
                $attrs = $m[2];
                if (! preg_match('/\bstyle=(["\'])((?:\\\\.|(?!\1).)*)\1/s', $attrs, $sm)) {
                    return $m[0];
                }

                $style = MpdfFontMapper::decodeAttrValue($sm[2]);
                $style = preg_replace('/\bborder-top(?:-(?:width|style|color))?\s*:\s*[^;]+;?\s*/i', '', $style) ?? $style;
                $style = preg_replace('/\s*;+\s*/', ';', $style) ?? $style;
                $style = trim($style, " \t\n\r\0\x0B;");

                if ($style === '') {
                    $newAttrs = preg_replace('/\bstyle=(["\'])((?:\\\\.|(?!\1).)*)\1/s', '', $attrs, 1) ?? $attrs;
                } else {
                    $newAttrs = preg_replace(
                        '/\bstyle=(["\'])((?:\\\\.|(?!\1).)*)\1/s',
                        ' style=' . $sm[1] . $style . $sm[1],
                        $attrs,
                        1,
                    ) ?? $attrs;
                }

                return $m[1] . $newAttrs . $m[3];
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
        $padTop = \App\Services\ReportPdfLayoutService::footerBlockPadTopPx($ft);

        $rootStyle = sprintf(
            'margin:0;padding:%dpx 0 0 0;background:%s;box-sizing:border-box;width:100%%;',
            $padTop,
            $bg,
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

        return preg_replace_callback(
            '/\bstyle=(["\'])((?:\\\\.|(?!\1).)*)\1/s',
            static function (array $m): string {
                $style = str_contains($m[2], '&#') || str_contains($m[2], '&quot;')
                    ? MpdfFontMapper::decodeAttrValue($m[2])
                    : $m[2];
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

                if ($style === '') {
                    return '';
                }

                return 'style="' . htmlspecialchars($style, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
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
