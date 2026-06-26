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
}
