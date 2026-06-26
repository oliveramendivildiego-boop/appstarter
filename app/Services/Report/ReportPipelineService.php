<?php

namespace App\Services\Report;

use App\Services\RegisterService;

/**
 * Orquesta warm síncrono: datos preparados + PDF en caché.
 */
class ReportPipelineService
{
    private RegisterService $registerService;

    private ReportDataCacheService $dataCache;

    private ReportPipelineMetrics $metrics;

    public function __construct(
        ?RegisterService $registerService = null,
        ?ReportDataCacheService $dataCache = null,
    ) {
        $this->registerService = $registerService ?? new RegisterService();
        $this->dataCache       = $dataCache ?? new ReportDataCacheService();
        $this->metrics         = ReportPipelineMetrics::getInstance();
    }

    /**
     * Warm síncrono tras guardar resultados (antes del redirect a viewreport).
     */
    public function warmSync(int $registroId): bool
    {
        if ($registroId < 1) {
            return false;
        }

        $tTotal = microtime(true);

        $this->dataCache->clear($registroId);
        $this->registerService->clearReportPdfPreviewCache($registroId);

        $tData = microtime(true);
        $data  = $this->registerService->prepareReportData($registroId, false, true);
        $this->metrics->recordWarmData(microtime(true) - $tData, $data !== null ? 'built' : 'empty');

        if ($data === null || ($data['grupos'] ?? []) === []) {
            $this->metrics->recordWarmCache(microtime(true) - $tTotal, false);

            return false;
        }

        $tPdf  = microtime(true);
        $pdfOk = $this->registerService->warmReportPdfPreviewCache($registroId);
        $this->metrics->recordWarmPdf(microtime(true) - $tPdf, $pdfOk);

        $ok = $pdfOk;
        $this->metrics->recordWarmCache(microtime(true) - $tTotal, $ok);
        $this->metrics->recordTotal('warm_sync', microtime(true) - $tTotal, [
            'registro_id' => $registroId,
            'pdf'         => $pdfOk,
        ]);

        return $ok;
    }
}
