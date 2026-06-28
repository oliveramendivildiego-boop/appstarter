<?php

namespace App\Libraries\Pdf;

/**
 * Extrae el bloque de pie para SetHTMLFooter() y lo quita del flujo del body.
 *
 * mPDF: el pie viaja en <!-- report-pdf-footer-payload:... --> (nunca en el DOM del body).
 */
class MpdfFooterExtractor
{
    private const START_MARKER = '<!-- report-pdf-footer:start -->';
    private const END_MARKER   = '<!-- report-pdf-footer:end -->';

    /**
     * @return array{0: string, 1: string|null} [bodyHtml, footerInnerHtml sin envolver]
     */
    public static function extract(string $html): array
    {
        if (preg_match('/<!--\s*report-pdf-footer-payload:([A-Za-z0-9+\/=_-]+)\s*-->/', $html, $payloadMatch)) {
            $raw = base64_decode($payloadMatch[1], true);
            $footerInner = is_string($raw) ? self::normalizeFooterInner($raw) : '';
            $html = preg_replace('/<!--\s*report-pdf-footer-payload:[A-Za-z0-9+\/=_-]+\s*-->/', '', $html, 1) ?? $html;

            return [self::purgeFooterBlocksFromBody($html), $footerInner !== '' ? $footerInner : null];
        }

        $start = strpos($html, self::START_MARKER);
        $end   = strpos($html, self::END_MARKER);
        if ($start === false || $end === false || $end <= $start) {
            return [self::purgeFooterBlocksFromBody($html), null];
        }

        $footerInner = self::normalizeFooterInner(substr(
            $html,
            $start + strlen(self::START_MARKER),
            $end - $start - strlen(self::START_MARKER),
        ));
        if ($footerInner === '') {
            return [self::purgeFooterBlocksFromBody($html), null];
        }

        $without = substr($html, 0, $start) . substr($html, $end + strlen(self::END_MARKER));

        return [self::purgeFooterBlocksFromBody($without), $footerInner];
    }

    /**
     * Evita pie duplicado: el bloque no debe quedar en el body (SetHTMLFooter ya lo pinta).
     */
    public static function purgeFooterBlocksFromBody(string $html): string
    {
        $html = preg_replace('/<!--\s*report-pdf-footer-payload:[A-Za-z0-9+\/=_-]+\s*-->/', '', $html) ?? $html;

        while (($start = strpos($html, self::START_MARKER)) !== false) {
            $end = strpos($html, self::END_MARKER, $start);
            if ($end === false) {
                $html = str_replace(self::START_MARKER, '', $html);
                break;
            }
            $html = substr($html, 0, $start) . substr($html, $end + strlen(self::END_MARKER));
        }

        if (! str_contains($html, 'mpdf-ft-root')
            && ! str_contains($html, 'footer-grid')
            && ! str_contains($html, 'pdf-dompdf-footer-anchor')) {
            return $html;
        }

        $html = self::removeAllBalancedDivsWithClass($html, 'mpdf-ft-root');
        $html = self::removeAllBalancedDivsWithClass($html, 'pdf-dompdf-footer-anchor');
        $html = self::removeAllFooterGridDivs($html);

        return preg_replace('/<div\b[^>]*\bpdf-dompdf-footer-anchor\b[^>]*>\s*<\/div>\s*/i', '', $html) ?? $html;
    }

    private static function normalizeFooterInner(string $html): string
    {
        $html = str_replace([self::START_MARKER, self::END_MARKER], '', $html);

        return trim($html);
    }

    private static function removeAllFooterGridDivs(string $html): string
    {
        $pattern = '/<div\b[^>]*\bclass="[^"]*(?:\bpdf-ft-block\b[^"]*\bfooter-grid\b|\bfooter-grid\b[^"]*\bpdf-ft-block\b)[^"]*"[^>]*>/i';

        while (preg_match($pattern, $html, $match, PREG_OFFSET_CAPTURE)) {
            $removed = self::removeBalancedDivAt($html, $match[0][1]);
            if ($removed === null) {
                break;
            }
            $html = $removed;
        }

        return $html;
    }

    private static function removeAllBalancedDivsWithClass(string $html, string $className): string
    {
        $pattern = '/<div\b[^>]*\bclass="[^"]*\b' . preg_quote($className, '/') . '\b[^"]*"[^>]*>/i';

        while (preg_match($pattern, $html, $match, PREG_OFFSET_CAPTURE)) {
            $removed = self::removeBalancedDivAt($html, $match[0][1]);
            if ($removed === null) {
                break;
            }
            $html = $removed;
        }

        return $html;
    }

    private static function removeBalancedDivAt(string $html, int $start): ?string
    {
        if (! preg_match('/^<div\b[^>]*>/i', substr($html, $start), $open)) {
            return null;
        }

        $pos   = $start + strlen($open[0]);
        $depth = 1;

        while ($depth > 0 && $pos < strlen($html)) {
            if (! preg_match('/<\/?div\b[^>]*>/i', $html, $tag, PREG_OFFSET_CAPTURE, $pos)) {
                return null;
            }

            $depth += str_starts_with(strtolower($tag[0][0]), '</div') ? -1 : 1;
            $pos    = $tag[0][1] + strlen($tag[0][0]);

            if ($depth === 0) {
                return substr($html, 0, $start) . substr($html, $pos);
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $layout
     */
    public static function extractAndWrap(string $html, array $layout): array
    {
        [$body, $footerInner] = self::extract($html);
        if ($footerInner === null || $footerInner === '') {
            return [$body, null];
        }

        return [$body, MpdfFooterStyles::wrapForSetHtmlFooter($footerInner, $layout)];
    }
}
