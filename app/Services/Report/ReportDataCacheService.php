<?php

namespace App\Services\Report;

use App\Models\RegisterModel;
use App\Services\ConfigService;
use App\Services\RegisterService;
use App\Services\ReportPdfLayoutService;

/**
 * Caché de datos preparados del reporte (sin HTML ni PDF).
 */
class ReportDataCacheService
{
    private const SALT = 'report-data-prep-v1-chromium-pipeline';

    private RegisterModel $registerModel;

    public function __construct(?RegisterModel $registerModel = null)
    {
        $this->registerModel = $registerModel ?? model(RegisterModel::class);
    }

    /**
     * Huella determinística: resultados, plantilla, config, doctor, comentarios.
     */
    public function computeFingerprint(int $registroId): string
    {
        $parts = [(string) $registroId];

        foreach ($this->registerModel->getInfoAnalisis($registroId) as $row) {
            $parts[] = trim((string) ($row['name'] ?? '')) . '=' . trim((string) ($row['regvalues'] ?? ''));
        }

        $master = $this->registerModel->getInforeport($registroId);
        if ($master) {
            $parts[] = 'person_id=' . (int) ($master->person_id ?? 0);
            $parts[] = 'doctor_id=' . (int) ($master->doctor_id ?? 0);
            $parts[] = 'comentario=' . trim((string) ($master->comentario_resultado ?? ''));
        }

        $registerInfo = $this->registerModel->getInfoRefill($registroId);
        if ($registerInfo) {
            $parts[] = 'pruebas=' . trim((string) ($registerInfo->pruebas ?? ''));
        }

        $doctorId = $master ? (int) ($master->doctor_id ?? 0) : 0;
        $doctor   = ($doctorId > 0) ? model(\App\Models\DoctorModel::class)->getInfo($doctorId) : null;
        $parts[]  = (new RegisterService())->reportPdfDoctorFingerprintPart($doctor);

        $layoutService = new ReportPdfLayoutService();
        $pdfLayout     = $layoutService->getActiveLayoutForRender();
        $parts[]       = $this->hashLayout($pdfLayout);

        $labConfig = (new RegisterService())->getLabConfig();
        $parts[]   = md5(json_encode($labConfig, JSON_UNESCAPED_UNICODE) ?: '');

        $parts[] = (string) (config('Pdf')->renderer ?? 'dompdf');
        $parts[] = self::SALT;

        return hash('sha256', implode("\n", $parts));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function read(int $registroId, string $fingerprint): ?array
    {
        $path     = $this->dataPath($registroId);
        $metaPath = $path . '.meta';

        if (! is_file($path) || ! is_file($metaPath)) {
            return null;
        }

        $stored = trim((string) file_get_contents($metaPath));
        if ($stored === '' || ! hash_equals($fingerprint, $stored)) {
            return null;
        }

        $raw = file_get_contents($path);
        if ($raw === false || $raw === '') {
            return null;
        }

        $data = @unserialize($raw, ['allowed_classes' => true]);
        if (! is_array($data)) {
            return null;
        }

        ReportPipelineMetrics::getInstance()->log('report_data_cache_read', 0, ['registro_id' => $registroId]);

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function write(int $registroId, string $fingerprint, array $data): void
    {
        $dir = $this->cacheDir();
        if (! is_dir($dir)) {
            return;
        }

        $path = $this->dataPath($registroId);
        $tmp  = $path . '.tmp.' . getmypid();
        $blob = serialize($data);

        if (@file_put_contents($tmp, $blob, LOCK_EX) === false) {
            return;
        }

        @file_put_contents($tmp . '.meta', $fingerprint, LOCK_EX);
        @rename($tmp, $path);
        @rename($tmp . '.meta', $path . '.meta');

        ReportPipelineMetrics::getInstance()->log('report_data_cache_write', 0, [
            'registro_id' => $registroId,
            'bytes'       => strlen($blob),
        ]);
    }

    public function clear(int $registroId): void
    {
        if ($registroId < 1) {
            return;
        }

        $path = $this->dataPath($registroId);
        if (is_file($path)) {
            @unlink($path);
        }
        if (is_file($path . '.meta')) {
            @unlink($path . '.meta');
        }
    }

    /**
     * @param array<string, mixed> $pdfLayout
     */
    private function hashLayout(array $pdfLayout): string
    {
        $sorted = $pdfLayout;
        ksort($sorted);

        return md5(json_encode($sorted, JSON_UNESCAPED_UNICODE) ?: '');
    }

    private function cacheDir(): string
    {
        $dir = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'report_data_prep';
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return $dir;
    }

    private function dataPath(int $registroId): string
    {
        return $this->cacheDir() . DIRECTORY_SEPARATOR . 'registro_' . $registroId . '.dat';
    }
}
