<?php

namespace App\Libraries\Pdf;

/**
 * Convierte comentarios pdf-pagination de cabecera en HTML para SetHTMLHeader() de mPDF.
 */
class MpdfPaginationHtmlInjector
{
    /**
     * @return array{0: string, 1: string} [html sin comentarios, headerHtml]
     */
    public static function extractHeaderHtml(string $html): array
    {
        if (! preg_match_all('/<!--\s*pdf-pagination:([A-Za-z0-9+\/=_-]+)\s*-->/', $html, $matches, PREG_OFFSET_CAPTURE)) {
            return [$html, ''];
        }

        $headerBlocks = [];
        $remove       = [];

        foreach ($matches[1] as $i => $match) {
            $encoded = $match[0];
            $json    = base64_decode($encoded, true);
            if ($json === false) {
                continue;
            }
            $slot = json_decode($json, true);
            if (! is_array($slot)) {
                continue;
            }

            $zone = strtolower(trim((string) ($slot['zone'] ?? 'header')));
            $remove[] = $matches[0][$i][0];
            if ($zone === 'footer') {
                continue;
            }

            $headerBlocks[] = self::buildHeaderPaginationHtml($slot);
        }

        if ($remove !== []) {
            $html = str_replace($remove, '', $html);
        }

        return [$html, implode('', $headerBlocks)];
    }

    /** @deprecated Usar extractHeaderHtml(); mantiene compat con adapt() legado. */
    public static function injectFromComments(string $html): string
    {
        [$html, $headerHtml] = self::extractHeaderHtml($html);
        if ($headerHtml === '') {
            return $html;
        }

        $inject = '<div class="mpdf-header-pagination-layer" aria-hidden="true">' . $headerHtml . '</div>';

        if (stripos($html, '</body>') !== false) {
            return str_ireplace('</body>', $inject . "\n</body>", $html);
        }

        return $html . $inject;
    }

    /**
     * @param array<string, mixed> $slot
     */
    private static function buildHeaderPaginationHtml(array $slot): string
    {
        $prefix   = (string) ($slot['prefix'] ?? '');
        $format   = (string) ($slot['format'] ?? 'page_of_total');
        $align    = strtolower(trim((string) ($slot['align'] ?? 'left')));
        $fontSize = max(7.0, min(20.0, (float) ($slot['fontSize'] ?? 10)));
        $mr       = max(0.0, (float) ($slot['mr'] ?? 15));
        $ml       = max(0.0, (float) ($slot['ml'] ?? 15));
        $family   = self::mpdfFontFamily((string) ($slot['fontFamily'] ?? 'DejaVu Sans'));
        $weight   = (string) ($slot['fontWeight'] ?? 'normal');
        $style    = (string) ($slot['fontStyle'] ?? 'normal');
        $color    = (string) ($slot['color'] ?? '#333333');
        $lh       = max(1.0, (float) ($slot['lineHeight'] ?? 1.35));

        $text = $format === 'total_only'
            ? '{nbpg}'
            : htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8') . '{PAGENO} de {nbpg}';

        $alignCss = match ($align) {
            'right'  => 'text-align:right;padding-right:' . self::fmtMm($mr) . 'mm;padding-left:0;',
            'center' => 'text-align:center;padding-left:' . self::fmtMm($ml) . 'mm;padding-right:' . self::fmtMm($mr) . 'mm;',
            default  => 'text-align:left;padding-left:' . self::fmtMm($ml) . 'mm;padding-right:0;',
        };

        $css = 'width:100%;box-sizing:border-box;' . $alignCss
            . 'font-family:' . $family . ';font-size:' . self::fmtPt($fontSize) . 'pt;'
            . 'font-weight:' . htmlspecialchars($weight, ENT_QUOTES, 'UTF-8') . ';'
            . 'font-style:' . htmlspecialchars($style, ENT_QUOTES, 'UTF-8') . ';'
            . 'color:' . htmlspecialchars($color, ENT_QUOTES, 'UTF-8') . ';'
            . 'line-height:' . $lh . ';white-space:nowrap;margin:0;padding:0;';

        return '<div class="header-piece header-piece-pagination mpdf-injected-pagination" style="' . $css . '">'
            . $text
            . '</div>';
    }

    private static function mpdfFontFamily(string $family): string
    {
        $family = trim($family, " \t\"'");
        if (strcasecmp($family, 'DejaVu Sans') === 0) {
            return 'dejavusans';
        }

        return htmlspecialchars($family, ENT_QUOTES, 'UTF-8');
    }

    private static function fmtMm(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private static function fmtPt(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
