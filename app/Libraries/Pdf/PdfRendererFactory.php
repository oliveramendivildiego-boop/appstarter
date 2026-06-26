<?php

namespace App\Libraries\Pdf;

use App\Services\Report\ReportPipelineMetrics;
use RuntimeException;

/**
 * Fábrica del renderizador PDF (Chromium headless).
 */
class PdfRendererFactory
{
    public static function create(?string $engine = null): PdfRendererInterface
    {
        $engine = strtolower(trim($engine ?? (string) (config('Pdf')->renderer ?? 'chromium')));

        return match ($engine) {
            'chromium', 'chrome' => new ChromiumPdfRenderer(),
            default              => throw new RuntimeException(
                'Motor PDF no soportado: ' . $engine . '. Use chromium y tenga Chrome/Chromium instalado.',
            ),
        };
    }

    /**
     * Genera PDF con Chromium headless.
     */
    public static function renderWithFallback(string $html, PdfOptions $options): string
    {
        $t0       = microtime(true);
        $renderer = self::create();

        try {
            $binary = $renderer->renderHtml($html, $options);
        } catch (\Throwable $e) {
            ReportPipelineMetrics::getInstance()->recordChromiumRender(
                microtime(true) - $t0,
                false,
                $renderer->engineName(),
                $e->getMessage(),
            );

            throw $e;
        }

        ReportPipelineMetrics::getInstance()->recordPdfRender(
            microtime(true) - $t0,
            $renderer->engineName(),
            'rendered',
        );

        return $binary;
    }
}
