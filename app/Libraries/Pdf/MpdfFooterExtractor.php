<?php

namespace App\Libraries\Pdf;

/**
 * Extrae el bloque de pie para SetHTMLFooter() y lo quita del flujo del body.
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
        $start = strpos($html, self::START_MARKER);
        $end   = strpos($html, self::END_MARKER);
        if ($start === false || $end === false || $end <= $start) {
            return [$html, null];
        }

        $footerInner = substr(
            $html,
            $start + strlen(self::START_MARKER),
            $end - $start - strlen(self::START_MARKER),
        );
        $footerInner = trim($footerInner);
        if ($footerInner === '') {
            return [$html, null];
        }

        $without = substr($html, 0, $start) . substr($html, $end + strlen(self::END_MARKER));

        return [$without, $footerInner];
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
