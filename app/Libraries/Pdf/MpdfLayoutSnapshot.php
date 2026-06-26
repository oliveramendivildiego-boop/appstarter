<?php

namespace App\Libraries\Pdf;

/**
 * Snapshot de plantilla embebido en el HTML para el pipeline mPDF.
 */
final class MpdfLayoutSnapshot
{
    private const MARKER_PREFIX = '<!-- pdf-mpdf-layout:';

    /**
     * @param array<string, mixed> $layout
     */
    public static function marker(array $layout): string
    {
        if ($layout === []) {
            return '';
        }

        $json = json_encode($layout, JSON_UNESCAPED_UNICODE);
        if (! is_string($json) || $json === '') {
            return '';
        }

        return self::MARKER_PREFIX . base64_encode($json) . ' -->';
    }

    /**
     * @return array<string, mixed>
     */
    public static function extractFromHtml(string $html): array
    {
        if (! preg_match('/<!--\s*pdf-mpdf-layout:([A-Za-z0-9+\/=_-]+)\s*-->/', $html, $matches)) {
            return [];
        }

        $json = base64_decode($matches[1], true);
        if ($json === false) {
            return [];
        }

        $layout = json_decode($json, true);

        return is_array($layout) ? $layout : [];
    }

    public static function stripMarker(string $html): string
    {
        return preg_replace('/<!--\s*pdf-mpdf-layout:[A-Za-z0-9+\/=_-]+\s*-->/', '', $html) ?? $html;
    }
}
