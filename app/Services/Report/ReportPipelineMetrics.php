<?php

namespace App\Services\Report;

/**
 * Métricas del pipeline FormFill → Warm → ViewReport → PDF.
 */
class ReportPipelineMetrics
{
    private static ?self $instance = null;

    /** @var array<string, mixed> */
    private array $last = [];

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function recordSaveRegvalues(float $seconds): void
    {
        $this->log('save_regvalues_ms', $seconds * 1000);
    }

    public function recordWarmCache(float $seconds, bool $success): void
    {
        $this->log('warm_cache_ms', $seconds * 1000, ['success' => $success]);
    }

    public function recordWarmData(float $seconds, string $cacheStatus): void
    {
        $this->log('warm_data_ms', $seconds * 1000, ['report_data_cache' => $cacheStatus]);
    }

    public function recordWarmPdf(float $seconds, bool $success): void
    {
        $this->log('warm_pdf_ms', $seconds * 1000, ['success' => $success]);
    }

    public function recordViewreport(float $seconds, string $dataCacheStatus): void
    {
        $this->log('viewreport_ms', $seconds * 1000, ['report_data_cache' => $dataCacheStatus]);
    }

    public function recordPdfRender(float $seconds, string $engine, string $pdfCacheStatus): void
    {
        $this->log('pdf_render_ms', $seconds * 1000, [
            'engine'            => $engine,
            'pdf_preview_cache' => $pdfCacheStatus,
        ]);
    }

    public function recordTotal(string $phase, float $seconds, array $extra = []): void
    {
        $this->log('total_' . $phase . '_ms', $seconds * 1000, $extra);
    }

    /**
     * @return array<string, mixed>
     */
    public function getLast(): array
    {
        return $this->last;
    }

    /**
     * @param array<string, mixed> $extra
     */
    public function log(string $metric, float $valueMs, array $extra = []): void
    {
        $payload = array_merge([
            'metric' => $metric,
            'ms'     => round($valueMs, 2),
        ], $extra);

        $this->last[$metric] = $payload;
        log_message('info', '[report_pipeline] {json}', [
            'json' => json_encode($payload, JSON_UNESCAPED_UNICODE) ?: '{}',
        ]);
    }
}
