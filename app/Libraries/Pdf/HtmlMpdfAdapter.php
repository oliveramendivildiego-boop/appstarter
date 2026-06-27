<?php

namespace App\Libraries\Pdf;

/**
 * Ajustes HTML/CSS para mPDF (misma plantilla visual que el layout PDF fijo).
 */
class HtmlMpdfAdapter
{
    public const CACHE_REVISION = 'mpdf-native-v78';

    private const TOTAL_PAGES_TOKEN = '__PDF_TOTAL_PAGES__';

    private const PAGINATION_PAGE_OF_TOTAL = '{PAGENO} de {nbpg}';

    public static function adapt(string $html, ?PdfOptions $options = null): string
    {
        unset($options);
        $html = self::stripAtPageRulesForMpdf($html);
        $html = self::expandInlineCustomProperties($html);
        $html = self::stripDompdfPagedMediaCounters($html);
        $html = self::replacePaginationTokens($html);
        $html = self::neutralizeFixedLayoutInBody($html);
        $html = self::neutralizeGridMarginAuto($html);
        $html = self::injectMpdfCompatStyles($html);
        $html = MpdfCssVariablesResolver::resolveInHtml($html);
        $html = MpdfFontMapper::normalizeInlineStylesInHtml($html);
        $html = self::stripResultsLayoutInlineConflictsForMpdf($html);
        $html = MpdfFontMapper::normalizeClassAttrsInHtml($html);

        return $html;
    }

    /**
     * Todos los PDF con mPDF: el bloque Resultados usa buildResultsTableParityCss (cualquier plantilla/registro).
     * Los style="" densos son para Dompdf; aquí se eliminan propiedades que compiten con ese CSS.
     */
    private static function stripResultsLayoutInlineConflictsForMpdf(string $html): string
    {
        $cabeceraLayoutProps = [
            'font-size',
            'padding-top',
            'padding-bottom',
            'font-family',
            'font-weight',
            'color',
            'line-height',
            'text-shadow',
            'font-style',
            'text-transform',
            'letter-spacing',
        ];

        $blockGapProps = ['padding-top', 'margin-top'];

        foreach ([
            'report-pdf-grupo-cabecera-line',
            'report-metodo-prueba',
            'report-tipo-muestra',
        ] as $classPattern) {
            $html = self::stripInlineStylePropertiesOnMatchingElements(
                $html,
                $classPattern,
                $cabeceraLayoutProps,
            );
        }

        $html = self::stripInlineStylePropertiesOnMatchingElements(
            $html,
            'report-pdf-grupo-area-separator',
            ['padding-top', 'padding-bottom'],
        );

        return self::stripInlineStylePropertiesOnMatchingElements(
            $html,
            'report-pdf-subgrupo-prueba',
            $blockGapProps,
        );
    }

    /**
     * @param list<string> $properties
     */
    private static function stripInlineStylePropertiesOnMatchingElements(
        string $html,
        string $classPattern,
        array $properties,
    ): string {
        $regex = '/(<(?:div|h4)\b[^>]*\b' . $classPattern . '\b[^>]*)\sstyle=(["\'])((?:\\\\.|(?!\2).)*)(\2)/is';

        return preg_replace_callback(
            $regex,
            static function (array $m) use ($properties): string {
                $style = self::stripInlineCssProperties($m[3], $properties);
                if ($style === '') {
                    return $m[1] . '>';
                }

                return $m[1] . ' style=' . $m[2] . $style . $m[4];
            },
            $html,
        ) ?? $html;
    }

    /**
     * @param list<string> $properties
     */
    private static function stripInlineCssProperties(string $style, array $properties): string
    {
        foreach ($properties as $property) {
            $style = preg_replace('/\b' . preg_quote($property, '/') . '\s*:\s*[^;]+;?/i', '', $style) ?? $style;
        }

        $style = preg_replace('/;\s*;/', ';', $style) ?? $style;

        return trim($style, " \t\n\r\0\x0B;");
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function extractWatermarkData(string $html): ?array
    {
        if (! preg_match('/<!--\s*pdf-watermark-dompdf:([A-Za-z0-9+\/=_-]+)\s*-->/', $html, $matches)) {
            return null;
        }

        $json = base64_decode($matches[1], true);
        if ($json === false) {
            return null;
        }

        $data = json_decode($json, true);
        if (! is_array($data)) {
            return null;
        }

        $uri = trim((string) ($data['uri'] ?? ''));
        if ($uri === '') {
            return null;
        }

        return [
            'uri'          => $uri,
            'path'         => trim((string) ($data['path'] ?? '')),
            'opacity'      => (float) ($data['opacity'] ?? 0.12),
            'size_percent' => (int) ($data['size_percent'] ?? 45),
        ];
    }

    public static function stripWatermarkMarker(string $html): string
    {
        return preg_replace('/<!--\s*pdf-watermark-dompdf:[A-Za-z0-9+\/=_-]+\s*-->/', '', $html) ?? $html;
    }

    private static function neutralizeFixedLayoutInBody(string $html): string
    {
        $html = preg_replace_callback(
            '/(<div\s+class="[^"]*pdf-dompdf-footer-anchor[^"]*"[^>]*style=")([^"]*)(")/i',
            static function (array $m): string {
                $style = preg_replace('/\bposition\s*:\s*fixed[^;"]*;?/i', 'position:static;', $m[2]) ?? $m[2];

                return $m[1] . $style . $m[3];
            },
            $html,
        ) ?? $html;

        return preg_replace_callback(
            '/(<div\s+class="[^"]*pdf-ft-block[^"]*footer-grid[^"]*"[^>]*style=")([^"]*)(")/i',
            static function (array $m): string {
                $style = preg_replace('/\bposition\s*:\s*(fixed|absolute)[^;"]*;?/i', '', $m[2]) ?? $m[2];

                return $m[1] . $style . $m[3];
            },
            $html,
            1,
        ) ?? $html;
    }

    private static function expandInlineCustomProperties(string $html): string
    {
        if (! preg_match_all(
            '/<([a-z][a-z0-9]*)\b([^>]*)\bstyle=(["\'])([^"\']*--pdf-[a-z0-9_-]+\s*:[^"\']*)\3([^>]*)>/i',
            $html,
            $matches,
            PREG_SET_ORDER,
        )) {
            return $html;
        }

        $rules = [];
        foreach ($matches as $match) {
            $attrs = $match[2] . $match[5];
            if (! preg_match('/\bclass=(["\'])([^"\']*)\1/i', $attrs, $classMatch)) {
                continue;
            }

            $classes = preg_split('/\s+/', trim($classMatch[2])) ?: [];
            $style   = html_entity_decode($match[4], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (! preg_match_all('/--([a-zA-Z0-9_-]+)\s*:\s*([^;]+)/', $style, $vars, PREG_SET_ORDER)) {
                continue;
            }

            foreach ($classes as $class) {
                $class = trim($class);
                if ($class === '') {
                    continue;
                }
                foreach ($vars as $var) {
                    $rules['.' . $class]['--' . $var[1]] = trim($var[2]);
                }
            }
        }

        if ($rules === []) {
            return $html;
        }

        $inject = "<style>\n/* mPDF: inline custom properties */\n";
        foreach ($rules as $selector => $vars) {
            $inject .= $selector . " {\n";
            foreach ($vars as $name => $value) {
                $inject .= '    ' . $name . ': ' . $value . ";\n";
            }
            $inject .= "}\n";
        }
        $inject .= "</style>\n";

        if (stripos($html, '</head>') !== false) {
            return str_ireplace('</head>', $inject . '</head>', $html);
        }

        return $inject . $html;
    }

    private static function stripDompdfPagedMediaCounters(string $html): string
    {
        $html = preg_replace('/\bcontent\s*:\s*[^;{}]*counter\s*\(\s*pages?\s*\)[^;{}]*;/i', 'content: none;', $html) ?? $html;

        return preg_replace('/\bcontent\s*:\s*attr\s*\(\s*data-prefix\s*\)[^;{}]*;/i', 'content: none;', $html) ?? $html;
    }

    /**
     * @page en el CSS del reporte anula SetHTMLFooter() (márgenes vía createMpdf()).
     */
    private static function stripAtPageRulesForMpdf(string $html): string
    {
        return preg_replace('/@page\s*\{[^}]*\}/is', '', $html) ?? $html;
    }

    private static function replacePaginationTokens(string $html): string
    {
        $replacement = self::PAGINATION_PAGE_OF_TOTAL;

        $html = preg_replace_callback(
            '/<span\b([^>]*)\bdata-total="' . preg_quote(self::TOTAL_PAGES_TOKEN, '/') . '"([^>]*)>\s*<\/span>/i',
            static fn (array $m): string => '<span' . $m[1] . 'data-total="mpdf"' . $m[2] . '>'
                . $replacement
                . '</span>',
            $html,
        ) ?? $html;

        return str_replace(self::TOTAL_PAGES_TOKEN, $replacement, $html);
    }

    private static function neutralizeGridMarginAuto(string $html): string
    {
        $html = preg_replace('/\bmargin-top\s*:\s*auto\s*!important\s*;?/i', '', $html) ?? $html;

        return preg_replace('/\bmargin-bottom\s*:\s*auto\s*!important\s*;?/i', '', $html) ?? $html;
    }

    private static function injectMpdfCompatStyles(string $html): string
    {
        $inject = '<style>
/* mPDF: paridad con plantilla PDF fija */
body.pdf-engine-mpdf .pdf-pagination-line::before,
body.pdf-engine-mpdf .pdf-ft-pagination-num::before,
body.pdf-dompdf-download .pdf-pagination-line::before,
body.pdf-dompdf-download .pdf-ft-pagination-num::before { content: none !important; }
body.pdf-engine-mpdf table.results thead,
body.pdf-dompdf-download table.results thead { display: table-header-group; }
body.pdf-engine-mpdf .mpdf-injected-pagination,
body.pdf-dompdf-download .mpdf-injected-pagination { display: block !important; }
body.pdf-engine-mpdf .pdf-dompdf-footer-anchor,
body.pdf-engine-mpdf .pdf-ft-block.footer-grid { position: static !important; }
body.pdf-engine-mpdf .mpdf-html-footer .mpdf-ft-root { position: static !important; padding: 0 !important; margin: 0 !important; background: transparent !important; }
body.pdf-engine-mpdf .report-lab-firma-grupo-inline,
body.pdf-engine-mpdf .lab-firmas-pdf-block-inline {
    position: relative !important; z-index: 3 !important; display: block !important;
}
body.pdf-engine-mpdf .lab-firmas-pdf-block-inline .pdf-section-table,
body.pdf-engine-mpdf .lab-firmas-body-grid {
    position: static !important; left: auto !important; right: auto !important; bottom: auto !important;
}
body.pdf-engine-mpdf .pdf-ft-pagination p,
body.pdf-engine-mpdf .pdf-ft-pagination-label,
body.pdf-engine-mpdf .pdf-ft-pagination-num {
    white-space: nowrap !important; display: inline !important; vertical-align: baseline !important;
}
body.pdf-engine-mpdf .pdf-hg-block .header-piece-logo img {
    max-width: 100% !important;
}
body.pdf-engine-mpdf .pdf-hg-block .pdf-section-table td.pdf-cell[valign="middle"] .header-piece-logo,
body.pdf-engine-mpdf .pdf-hg-block .pdf-section-table td.pdf-cell[valign="bottom"] .header-piece-logo {
    display: inline-block !important;
    vertical-align: middle !important;
}
body.pdf-engine-mpdf .pdf-hg-block .pdf-section-table td.pdf-cell > table.pdf-cell-valign-table td[valign="bottom"] {
    vertical-align: bottom !important;
}
body.pdf-engine-mpdf .pdf-hg-block .header-piece-company h1 {
    margin: 0 !important;
}
body.pdf-engine-mpdf .pdf-hg-block .pdf-section-table,
body.pdf-engine-mpdf .pdf-pd-block .pdf-section-table,
body.pdf-engine-mpdf .pdf-ft-block .pdf-section-table {
    table-layout: fixed !important;
    width: 100% !important;
}
body.pdf-engine-mpdf .pdf-hg-block .pdf-section-table td.pdf-cell[valign="middle"],
body.pdf-engine-mpdf .pdf-hg-block .pdf-section-table td.pdf-cell[valign="bottom"] {
    height: auto !important;
    min-height: 0 !important;
}
body.pdf-engine-mpdf .pdf-section-table td.pdf-cell,
body.pdf-engine-mpdf .pdf-section-table td.pdf-cell-stack-item {
    margin-top: 0 !important;
    margin-bottom: 0 !important;
}
body.pdf-engine-mpdf .pdf-section-table td.pdf-cell[valign="middle"]:not(.pdf-cell--has-explicit-height),
body.pdf-engine-mpdf .pdf-section-table td.pdf-cell[valign="bottom"]:not(.pdf-cell--has-explicit-height) {
    height: 1px !important;
}
body.pdf-engine-mpdf .pdf-section-table td.pdf-cell.pdf-cell--has-explicit-height[valign="middle"],
body.pdf-engine-mpdf .pdf-section-table td.pdf-cell.pdf-cell--has-explicit-height[valign="bottom"] {
    vertical-align: middle !important;
}
body.pdf-engine-mpdf .pdf-section-table td.pdf-cell > table.pdf-cell-valign-table {
    width: 100% !important;
    height: 100% !important;
    border-collapse: collapse !important;
    margin: 0 !important;
    border: 0 !important;
    table-layout: fixed !important;
}
body.pdf-engine-mpdf .pdf-section-table td.pdf-cell > table.pdf-cell-valign-table td {
    padding: 0 !important;
    border: 0 !important;
}
body.pdf-engine-mpdf .pdf-section-table td.pdf-cell > table.pdf-cell-valign-table td[valign="middle"] {
    vertical-align: middle !important;
}
body.pdf-engine-mpdf .report-pdf-grupo-cabecera-table {
    border-collapse: collapse !important;
    width: 100% !important;
    border: 0 !important;
}
body.pdf-engine-mpdf .report-pdf-grupo-cabecera-table td {
    border: 0 !important;
    padding: 0 !important;
    margin: 0 !important;
    vertical-align: top !important;
}
body.pdf-engine-mpdf .report-pdf-grupo-cabecera-line {
    display: block !important;
    box-sizing: border-box !important;
    width: 100% !important;
    margin: 0 !important;
}
body.pdf-engine-mpdf .report-pdf-grupo-cabecera + .report-segment-table-wrap table.results,
body.pdf-engine-mpdf .report-pdf-grupo-cabecera + .report-refs-matrix-wrap table.results {
    margin-top: 0 !important;
}
body.pdf-engine-mpdf .report-pdf-grupo-prueba:not(.report-pdf-grupo-prueba-first) {
    margin-top: 0 !important;
}
body.pdf-engine-mpdf.pdf-layout-engine .report-pdf-grupo-area-separator,
body.pdf-engine-mpdf.pdf-dompdf-download .report-pdf-grupo-area-separator {
    margin-top: 0 !important;
    margin-bottom: 0 !important;
    page-break-after: auto !important;
    break-after: auto !important;
}
body.pdf-engine-mpdf.pdf-layout-engine .report-pdf-grupo-area-separator + .report-pdf-subgrupo-block,
body.pdf-engine-mpdf.pdf-dompdf-download .report-pdf-grupo-area-separator + .report-pdf-subgrupo-block {
    page-break-before: auto !important;
    break-before: auto !important;
}
body.pdf-engine-mpdf.pdf-layout-engine .report-pdf-grupo-cabecera,
body.pdf-engine-mpdf.pdf-dompdf-download .report-pdf-grupo-cabecera {
    page-break-inside: auto !important;
    break-inside: auto !important;
}
body.pdf-engine-mpdf.pdf-layout-engine .report-pdf-grupo-cabecera-line--title,
body.pdf-engine-mpdf.pdf-layout-engine .report-pdf-grupo-cabecera .group-title.report-pdf-grupo-cabecera-line--title,
body.pdf-engine-mpdf.pdf-dompdf-download .report-pdf-grupo-cabecera-line--title,
body.pdf-engine-mpdf.pdf-dompdf-download .report-pdf-grupo-cabecera .group-title.report-pdf-grupo-cabecera-line--title {
    page-break-after: auto !important;
    break-after: auto !important;
    overflow: visible !important;
}
body.pdf-engine-mpdf.pdf-layout-engine .report-pdf-grupo-cabecera + .report-segment-table-wrap,
body.pdf-engine-mpdf.pdf-dompdf-download .report-pdf-grupo-cabecera + .report-segment-table-wrap {
    page-break-before: auto !important;
    break-before: auto !important;
}
body.pdf-engine-mpdf .pdf-rs-block table.results:not(.pdf-notes-table):not(.report-refs-matrix) th,
body.pdf-engine-mpdf .pdf-rs-block table.results:not(.pdf-notes-table):not(.report-refs-matrix) td {
    border: 1px solid var(--pdf-results-border-color, #ddd) !important;
}
body.pdf-engine-mpdf .report-refs-matrix-wrap > .report-refs-matrix-title {
    margin-top: 0 !important;
}
</style>';

        if (stripos($html, '</head>') !== false) {
            return str_ireplace('</head>', $inject . "\n</head>", $html);
        }

        return $inject . $html;
    }

    /**
     * Tokens de paginación, grilla 3 columnas y fuentes en el fragmento de pie (SetHTMLFooter).
     */
    public static function adaptFooterForMpdf(string $footerHtml): string
    {
        $footerHtml = MpdfFooterGridSimplifier::simplify($footerHtml);
        $footerHtml = self::replacePaginationTokens($footerHtml);
        $footerHtml = MpdfFontMapper::sanitizeFooterFragmentHtml($footerHtml);

        return $footerHtml;
    }
}
