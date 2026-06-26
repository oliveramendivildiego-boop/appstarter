<?php

namespace App\Services\Report;

/**
 * Caché del HTML del reporte (entre prepareReportData y el motor PDF).
 */
class ReportPdfHtmlCacheService
{
    private const SALT = 'report-pdf-html-v2-grupos-fingerprint';

    public static function salt(): string
    {
        return self::SALT;
    }

    public static function registroMarker(int $registroId): string
    {
        return '<!-- report-pipeline-registro:' . max(0, $registroId) . ' -->';
    }

    public static function htmlMatchesRegistroId(string $html, int $registroId): bool
    {
        if ($registroId < 1) {
            return true;
        }

        return str_contains($html, 'report-pipeline-registro:' . $registroId);
    }

    public function read(int $registroId, string $fingerprint): ?string
    {
        $path     = $this->htmlPath($registroId);
        $metaPath = $path . '.meta';

        if (! is_file($path) || ! is_file($metaPath)) {
            return null;
        }

        $stored = trim((string) file_get_contents($metaPath));
        if ($stored === '' || ! hash_equals($fingerprint, $stored)) {
            return null;
        }

        $html = file_get_contents($path);
        if ($html === false || $html === '') {
            return null;
        }

        if (! self::htmlMatchesRegistroId($html, $registroId)) {
            $this->clear($registroId);

            return null;
        }

        return $html;
    }

    public function write(int $registroId, string $fingerprint, string $html): void
    {
        if ($html === '') {
            return;
        }

        $dir = $this->cacheDir();
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (! is_dir($dir)) {
            return;
        }

        $path = $this->htmlPath($registroId);
        $tmp  = $path . '.tmp.' . getmypid();

        if (@file_put_contents($tmp, $html, LOCK_EX) === false) {
            return;
        }

        @file_put_contents($tmp . '.meta', $fingerprint, LOCK_EX);
        @rename($tmp, $path);
        @rename($tmp . '.meta', $path . '.meta');

        ReportPipelineMetrics::getInstance()->log('report_pdf_html_cache_write', 0, [
            'registro_id' => $registroId,
            'bytes'       => strlen($html),
        ]);
    }

    public function clear(int $registroId): void
    {
        if ($registroId < 1) {
            return;
        }

        $path = $this->htmlPath($registroId);
        if (is_file($path)) {
            @unlink($path);
        }
        if (is_file($path . '.meta')) {
            @unlink($path . '.meta');
        }
    }

    private function cacheDir(): string
    {
        return rtrim(WRITEPATH, '\\/') . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'report_pdf_html';
    }

    private function htmlPath(int $registroId): string
    {
        return $this->cacheDir() . DIRECTORY_SEPARATOR . 'registro_' . $registroId . '.html';
    }
}
