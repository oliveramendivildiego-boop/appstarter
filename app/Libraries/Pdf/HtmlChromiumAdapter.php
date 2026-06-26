<?php

namespace App\Libraries\Pdf;

/**
 * Adapta el HTML del reporte (pensado para Dompdf) a impresión Chromium sin cambiar las vistas.
 */
class HtmlChromiumAdapter
{
    public const CACHE_REVISION = 'footer-pagination-php-v32-chromium-only';

    public static function adapt(string $html, ?PdfOptions $options = null): string
    {
        $html = self::addBodyClasses($html, 'report-browser-print pdf-chromium-print');

        $hasFooter = str_contains($html, 'pdf-ft-block') && str_contains($html, 'footer-grid');
        if ($hasFooter) {
            $html = self::addBodyClasses($html, 'js-print-footer-fixed');
        }

        if (str_contains($html, 'data-order-sheet-from-page-two="1"')) {
            $html = self::addBodyClasses($html, 'js-order-sheet-footer-table-row');
        }

        $html = self::sanitizeDompdfFooterInlineStyles($html);

        $metrics = self::extractPageMetrics($html);
        $pageCssSize = self::resolvePageCssSize($options);
        $inject = self::buildCompatStyles($metrics, $pageCssSize);

        if (stripos($html, '</head>') !== false) {
            $html = str_ireplace('</head>', $inject . "\n</head>", $html);
        } else {
            $html = $inject . $html;
        }

        if ($hasFooter) {
            $html = self::injectPrepareScript($html, $metrics, $pageCssSize);
        }

        return $html;
    }

    private static function resolvePageCssSize(?PdfOptions $options): string
    {
        if ($options === null) {
            return 'letter portrait';
        }

        $key = strtolower((string) ($options->paperKey ?? 'letter'));
        $orient = strtolower($options->orientation) === 'landscape' ? 'landscape' : 'portrait';

        return match ($key) {
            'a4' => 'A4 ' . $orient,
            'legal' => 'legal ' . $orient,
            'custom' => rtrim(rtrim(number_format((float) ($options->widthMm ?? 210.0), 2, '.', ''), '0'), '.')
                . 'mm '
                . rtrim(rtrim(number_format((float) ($options->heightMm ?? 297.0), 2, '.', ''), '0'), '.')
                . 'mm',
            default => 'letter ' . $orient,
        };
    }

    private static function addBodyClasses(string $html, string $classes): string
    {
        $classes = trim($classes);
        if ($classes === '') {
            return $html;
        }

        if (! preg_match('/<body\b([^>]*)>/i', $html, $match, PREG_OFFSET_CAPTURE)) {
            return $html;
        }

        $bodyTag  = $match[0][0];
        $offset   = (int) $match[0][1];
        $existing = [];
        if (preg_match('/\bclass="([^"]*)"/i', $bodyTag, $classMatch)) {
            $existing = preg_split('/\s+/', trim(html_entity_decode($classMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'))) ?: [];
        }

        foreach (preg_split('/\s+/', $classes) as $className) {
            if ($className !== '' && ! in_array($className, $existing, true)) {
                $existing[] = $className;
            }
        }

        $merged = implode(' ', $existing);
        if (preg_match('/\bclass="[^"]*"/i', $bodyTag)) {
            $newBody = preg_replace('/\bclass="[^"]*"/i', 'class="' . $merged . '"', $bodyTag, 1) ?? $bodyTag;
        } else {
            $newBody = preg_replace('/<body\b/i', '<body class="' . $merged . '"', $bodyTag, 1) ?? $bodyTag;
        }

        return substr_replace($html, $newBody, $offset, strlen($bodyTag));
    }

    private static function sanitizeDompdfFooterInlineStyles(string $html): string
    {
        return preg_replace_callback(
            '/(\bclass="[^"]*(?:\bfooter-grid\b[^"]*\bpdf-ft-block\b|\bpdf-ft-block\b[^"]*\bfooter-grid\b)[^"]*"[^>]*\sstyle=")([^"]*)(")/is',
            static function (array $matches): string {
                $style = html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $style = preg_replace('/\s*bottom\s*:\s*-?[\d.]+mm\s*;?/i', '', $style) ?? $style;
                $style = preg_replace('/\s*min-height\s*:\s*[\d.]+mm\s*;?/i', '', $style) ?? $style;
                $style = trim($style, " \t\n\r\0\x0B;");

                return $matches[1] . $style . $matches[3];
            },
            $html,
        ) ?? $html;
    }

    /**
     * @return array{mt: float, mr: float, mb: float, ml: float, footerReserveMm: float, footerBg: string}
     */
    private static function extractPageMetrics(string $html): array
    {
        $metrics = [
            'mt'              => 15.0,
            'mr'              => 15.0,
            'mb'              => 15.0,
            'ml'              => 15.0,
            'footerReserveMm' => 22.0,
            'footerBg'        => '#ffffff',
        ];

        if (preg_match('/@page\s*\{[^}]*margin-top:\s*([\d.]+)\s*mm/i', $html, $m)) {
            $metrics['mt'] = (float) $m[1];
        }
        if (preg_match('/@page\s*\{[^}]*margin-right:\s*([\d.]+)\s*mm/i', $html, $m)) {
            $metrics['mr'] = (float) $m[1];
        }
        if (preg_match('/@page\s*\{[^}]*margin-left:\s*([\d.]+)\s*mm/i', $html, $m)) {
            $metrics['ml'] = (float) $m[1];
        }

        $pageBottomMm = null;
        if (preg_match('/@page\s*\{[^}]*margin-bottom:\s*([\d.]+)\s*mm/i', $html, $m)) {
            $pageBottomMm = (float) $m[1];
        }

        if (preg_match('/\.pdf-ft-block\.footer-grid\s*\{[^}]*bottom:\s*-([\d.]+)\s*mm/i', $html, $m)) {
            $metrics['footerReserveMm'] = (float) $m[1];
        } elseif (preg_match('/bottom:-([\d.]+)mm;min-height:\1mm/i', $html, $m)) {
            $metrics['footerReserveMm'] = (float) $m[1];
        }

        if ($pageBottomMm !== null) {
            $metrics['mb'] = $metrics['footerReserveMm'] > 0
                ? max(0.0, $pageBottomMm - $metrics['footerReserveMm'])
                : $pageBottomMm;
        }

        if (preg_match('/\.pdf-ft-block\.footer-grid\s*\{[^}]*background:\s*([^;}\s]+)/i', $html, $m)) {
            $metrics['footerBg'] = trim($m[1]);
        }

        return $metrics;
    }

    /**
     * @param array{mt: float, mr: float, mb: float, ml: float, footerReserveMm: float, footerBg: string} $m
     */
    private static function buildCompatStyles(array $m, string $pageCssSize): string
    {
        $fmt = static fn (float $v): string => rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');

        $mt = $fmt($m['mt']);
        $mr = $fmt($m['mr']);
        $mb = $fmt($m['mb']);
        $ml = $fmt($m['ml']);
        $footerReserve = $fmt($m['footerReserveMm']);
        $footerBg      = htmlspecialchars($m['footerBg'], ENT_QUOTES, 'UTF-8');
        $pageSizeCss   = htmlspecialchars($pageCssSize, ENT_QUOTES, 'UTF-8');
        $fontFaces     = self::buildDejaVuFontFaceCss();

        return <<<CSS
<style id="pdf-chromium-fonts">
{$fontFaces}
</style>
<style id="pdf-chromium-compat">
:root {
    --print-margin-top-mm: {$mt};
    --print-margin-right-mm: {$mr};
    --print-margin-bottom-mm: {$mb};
    --print-margin-left-mm: {$ml};
    --print-footer-reserve-mm: {$footerReserve};
    --print-page-css-size: {$pageSizeCss};
}
@media print {
    @page {
        size: {$pageSizeCss};
        margin-top: {$mt}mm !important;
        margin-right: {$mr}mm !important;
        margin-bottom: {$mb}mm !important;
        margin-left: {$ml}mm !important;
    }
    html,
    body.pdf-chromium-print.report-browser-print {
        overflow: visible !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    body.pdf-chromium-print.report-browser-print .pdf-main-stack {
        overflow: visible !important;
        margin: 0 !important;
        padding-top: 0 !important;
        padding-bottom: calc(var(--print-footer-reserve-mm, {$footerReserve}) * 1mm) !important;
        box-sizing: border-box !important;
    }
    body.pdf-chromium-print.pdf-dompdf-download .pdf-ft-block.footer-grid,
    body.pdf-chromium-print.report-browser-print .pdf-ft-block.footer-grid {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: fixed !important;
        bottom: calc(var(--print-margin-bottom-mm, {$mb}) * 1mm) !important;
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
        min-height: auto !important;
        margin: 0 !important;
        padding-top: 8px !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        padding-bottom: 0 !important;
        background: {$footerBg} !important;
        box-sizing: border-box !important;
        z-index: 100 !important;
        break-inside: avoid-page !important;
        page-break-inside: avoid !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    body.pdf-chromium-print .pdf-ft-block.footer-grid table.pdf-section-table {
        width: 100% !important;
        max-width: 100% !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }
    body.pdf-chromium-print.pdf-dompdf-download .pdf-ft-block .pdf-ft-pagination-num,
    body.pdf-chromium-print .pdf-ft-block .pdf-ft-pagination-num {
        visibility: hidden !important;
        color: transparent !important;
    }
    body.pdf-chromium-print .pdf-ft-block .pdf-ft-pagination-label {
        visibility: hidden !important;
        color: transparent !important;
    }
    body.pdf-chromium-print.pdf-dompdf-download .pdf-ft-block .pdf-ft-pagination-num::before,
    body.pdf-chromium-print .pdf-ft-block .pdf-ft-pagination-num::before {
        content: '' !important;
    }
    body.pdf-chromium-print.js-order-sheet-footer-table-row .pdf-ft-block.footer-grid .pdf-order-sheet-table-row {
        display: none !important;
    }
}
.pdf-chromium-print,
.pdf-chromium-print * {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
}
</style>
CSS;
    }

    /**
     * Chromium no trae DejaVu Sans instalada: sin @font-face sustituye (p. ej. Times) y el pie
     * no coincide con Dompdf ni con el estampado TCPDF de la paginación.
     */
    private static function buildDejaVuFontFaceCss(): string
    {
        $toFileUrl = static function (string $fileName): ?string {
            return PdfDejaVuFonts::fileUrl($fileName);
        };

        $declarations = [];
        $faces        = [
            ['DejaVu Sans', 'normal', 'normal', 'DejaVuSans.ttf'],
            ['DejaVu Sans', 'bold', 'normal', 'DejaVuSans-Bold.ttf'],
            ['DejaVu Sans', 'normal', 'italic', 'DejaVuSans-Oblique.ttf'],
            ['DejaVu Sans', 'bold', 'italic', 'DejaVuSans-BoldOblique.ttf'],
            ['DejaVu Serif', 'normal', 'normal', 'DejaVuSerif.ttf'],
            ['DejaVu Serif', 'bold', 'normal', 'DejaVuSerif-Bold.ttf'],
            ['DejaVu Serif', 'normal', 'italic', 'DejaVuSerif-Italic.ttf'],
            ['DejaVu Serif', 'bold', 'italic', 'DejaVuSerif-BoldItalic.ttf'],
            ['Courier New', 'normal', 'normal', 'DejaVuSansMono.ttf'],
            ['Courier New', 'bold', 'normal', 'DejaVuSansMono-Bold.ttf'],
            ['Courier New', 'normal', 'italic', 'DejaVuSansMono-Oblique.ttf'],
            ['Courier New', 'bold', 'italic', 'DejaVuSansMono-BoldOblique.ttf'],
        ];

        foreach ($faces as [$family, $weight, $style, $fileName]) {
            $url = $toFileUrl($fileName);
            if ($url === null) {
                continue;
            }

            $familyEsc = htmlspecialchars($family, ENT_QUOTES, 'UTF-8');
            $urlEsc    = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
            $declarations[] = <<<CSS
@font-face {
    font-family: "{$familyEsc}";
    font-style: {$style};
    font-weight: {$weight};
    font-display: block;
    src: url("{$urlEsc}") format("truetype");
}
CSS;
        }

        return $declarations === [] ? '' : implode("\n", $declarations);
    }

    /**
     * @param array{mt: float, mr: float, mb: float, ml: float, footerReserveMm: float, footerBg: string} $m
     */
    private static function injectPrepareScript(string $html, array $m, string $pageCssSize): string
    {
        $payload = json_encode([
            'mt'              => $m['mt'],
            'mr'              => $m['mr'],
            'mb'              => $m['mb'],
            'ml'              => $m['ml'],
            'footerReserveMm' => $m['footerReserveMm'],
            'pageCssSize'     => $pageCssSize,
        ], JSON_UNESCAPED_UNICODE);

        if (! is_string($payload)) {
            return $html;
        }

        $script = <<<HTML
<script id="pdf-chromium-prepare">
(function () {
    var cfg = {$payload};
    function pxToMm(px) { return px / (96 / 25.4); }
    function applyPageMargins() {
        var el = document.getElementById('pdf-chromium-page-margins');
        if (!el) {
            el = document.createElement('style');
            el.id = 'pdf-chromium-page-margins';
            el.media = 'print';
            document.head.appendChild(el);
        }
        el.textContent = '@page { size: ' + cfg.pageCssSize + '; '
            + 'margin-top: ' + cfg.mt + 'mm; '
            + 'margin-right: ' + cfg.mr + 'mm; '
            + 'margin-bottom: ' + cfg.mb + 'mm; '
            + 'margin-left: ' + cfg.ml + 'mm; }';
    }
    function syncFooterMetrics() {
        var footer = document.querySelector('.pdf-ft-block.footer-grid');
        if (!footer) {
            return;
        }
        if (footer.parentNode !== document.body) {
            document.body.appendChild(footer);
        }
        document.body.classList.add('js-print-footer-fixed');
        var heightMm = pxToMm(footer.offsetHeight || footer.scrollHeight || 0);
        if (isFinite(heightMm) && heightMm > 0) {
            var reserve = Math.max(10, Math.min(90, Math.ceil((heightMm + 4) * 10) / 10));
            document.documentElement.style.setProperty('--print-footer-reserve-mm', String(reserve));
        }
        applyPageMargins();
    }
    function run() {
        syncFooterMetrics();
        requestAnimationFrame(syncFooterMetrics);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
</script>
HTML;

        if (stripos($html, '</body>') !== false) {
            return str_ireplace('</body>', $script . "\n</body>", $html);
        }

        return $html . $script;
    }
}
