<?php

namespace App\Libraries\Pdf;

use App\Services\Report\ReportPipelineMetrics;
use RuntimeException;

/**
 * Fábrica de renderizado PDF (mPDF por defecto; Dompdf opcional vía PDF_RENDERER=dompdf).
 */
class PdfRendererFactory
{
    private static string $lastRenderEngine = 'mpdf';

    public static function lastRenderEngine(): string
    {
        return self::$lastRenderEngine;
    }

    public static function create(): PdfRendererInterface
    {
        return self::createForEngine((string) (config('Pdf')->renderer ?? 'mpdf'));
    }

    public static function renderWithFallback(string $html, PdfOptions $options): string
    {
        self::prepareRuntimeLimits();

        $engine   = strtolower(trim((string) (config('Pdf')->renderer ?? 'mpdf')));
        $t0       = microtime(true);
        $renderer = self::createForEngine($engine);
        $binary   = $renderer->renderHtml($html, $options);
        ReportPipelineMetrics::getInstance()->recordPdfRender(
            microtime(true) - $t0,
            $renderer->engineName(),
            'rendered',
        );
        self::$lastRenderEngine = $renderer->engineName();

        return $binary;
    }

    private static function createForEngine(string $engine): PdfRendererInterface
    {
        return match (strtolower(trim($engine))) {
            'mpdf'   => new MpdfPdfRenderer(),
            'dompdf' => new DompdfPdfRenderer(),
            default  => throw new RuntimeException(
                'Motor PDF no soportado: ' . $engine . '. Use dompdf o mpdf.',
            ),
        };
    }

    /**
     * Hosting compartido suele limitar memoria/PCRE; reportes grandes fallan en web pero no en CLI.
     */
    private static function prepareRuntimeLimits(): void
    {
        @ini_set('pcre.backtrack_limit', '10000000');
        @ini_set('pcre.recursion_limit', '1000000');
        @set_time_limit(180);

        $current = trim((string) ini_get('memory_limit'));
        if ($current === '' || $current === '-1') {
            return;
        }
        $bytes = self::parseIniMemoryBytes($current);
        if ($bytes > 0 && $bytes < 512 * 1024 * 1024) {
            @ini_set('memory_limit', '512M');
        }
    }

    private static function parseIniMemoryBytes(string $value): int
    {
        if (preg_match('/^(\d+(?:\.\d+)?)\s*([KMG])?/i', $value, $m) !== 1) {
            return (int) $value;
        }
        $num = (float) $m[1];
        $unit = strtoupper($m[2] ?? '');

        return (int) match ($unit) {
            'G'     => $num * 1024 * 1024 * 1024,
            'M'     => $num * 1024 * 1024,
            'K'     => $num * 1024,
            default => $num,
        };
    }
}
