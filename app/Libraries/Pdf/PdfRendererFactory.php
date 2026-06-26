<?php

namespace App\Libraries\Pdf;

use App\Services\Report\ReportPipelineMetrics;
use RuntimeException;

/**
 * Fábrica de renderizadores PDF (Chromium / Dompdf) con fallback configurable.
 */
class PdfRendererFactory
{
    private static string $lastRenderEngine = '';

    public static function lastRenderEngine(): string
    {
        return self::$lastRenderEngine;
    }

    public static function create(?string $engine = null): PdfRendererInterface
    {
        $engine = strtolower(trim($engine ?? (string) (config('Pdf')->renderer ?? 'dompdf')));

        if ($engine === 'dompdf') {
            self::assertDompdfEnabled();
        }

        return match ($engine) {
            'chromium', 'chrome' => new ChromiumPdfRenderer(),
            'dompdf'             => new DompdfPdfRenderer(),
            default              => throw new RuntimeException('Motor PDF no soportado: ' . $engine),
        };
    }

    private static function assertDompdfEnabled(): void
    {
        if (! (bool) (config('Pdf')->dompdfEnabled ?? true)) {
            throw new RuntimeException(
                'Dompdf está desactivado temporalmente. Use PDF_RENDERER=chromium con Chrome instalado, '
                . 'o pida reactivar dompdfEnabled en app/Config/Pdf.php.',
            );
        }
    }

    /**
     * Renderiza con el motor configurado; si falla Chromium y fallback está activo, usa Dompdf.
     */
    public static function renderWithFallback(string $html, PdfOptions $options): string
    {
        $engine  = strtolower(trim((string) (config('Pdf')->renderer ?? 'dompdf')));

        if ($engine === 'dompdf') {
            self::assertDompdfEnabled();
        }

        if ($engine === 'chromium' || $engine === 'chrome') {
            $t0 = microtime(true);
            try {
                $renderer = new ChromiumPdfRenderer();
                $binary   = $renderer->renderHtml($html, $options);
                ReportPipelineMetrics::getInstance()->recordPdfRender(
                    microtime(true) - $t0,
                    $renderer->engineName(),
                    'rendered',
                );
                self::$lastRenderEngine = 'chromium';

                return $binary;
            } catch (\Throwable $e) {
                ReportPipelineMetrics::getInstance()->recordChromiumRender(
                    microtime(true) - $t0,
                    false,
                    'chromium',
                    $e->getMessage(),
                );
                if (! (bool) (config('Pdf')->fallbackToDompdf ?? true)) {
                    throw $e;
                }
                self::assertDompdfEnabled();
                log_message('warning', 'Chromium PDF fallback to Dompdf: {msg}', ['msg' => $e->getMessage()]);
            }
        }

        self::assertDompdfEnabled();
        $t1       = microtime(true);
        $renderer = new DompdfPdfRenderer();
        $binary   = $renderer->renderHtml($html, $options);
        ReportPipelineMetrics::getInstance()->recordPdfRender(
            microtime(true) - $t1,
            $renderer->engineName(),
            'rendered',
        );
        self::$lastRenderEngine = ($engine === 'chromium' || $engine === 'chrome')
            ? 'dompdf-fallback'
            : 'dompdf';

        return $binary;
    }
}
