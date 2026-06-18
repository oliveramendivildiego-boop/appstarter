<?php

namespace App\Libraries;

use Dompdf\Dompdf;
use Dompdf\FontMetrics;
use Dompdf\Helpers;
use Dompdf\Options;

/**
 * Servicio para generar PDF de resultados de laboratorio
 */
class PdfService
{
    private const TOTAL_PAGES_TOKEN = '__PDF_TOTAL_PAGES__';

    private const WATERMARK_MARKER = 'pdf-watermark-dompdf';

    protected function makeDompdf(Options $options): Dompdf
    {
        $dompdf = new Dompdf($options);
        $dompdf->setPaper('letter', 'portrait');

        return $dompdf;
    }

    /**
     * @param array<string, mixed>|null $watermarkData
     */
    protected function renderHtmlToDompdf(
        Dompdf $dompdf,
        string $html,
        ?array $watermarkData = null
    ): void {
        $this->registerDompdfCallbacks($dompdf, $watermarkData);

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function extractWatermarkData(string $html): ?array
    {
        if (strpos($html, self::WATERMARK_MARKER) === false) {
            return null;
        }
        if (! preg_match('/<!--\s*pdf-watermark-dompdf:([A-Za-z0-9+\/=_-]+)\s*-->/', $html, $matches)) {
            return null;
        }
        $json = base64_decode($matches[1], true);
        if ($json === false) {
            return null;
        }
        $data = json_decode($json, true);
        if (! is_array($data)) {
            return null;
        }
        $uri = trim((string) ($data['uri'] ?? ''));
        if ($uri === '') {
            return null;
        }

        return [
            'uri'          => $uri,
            'path'         => trim((string) ($data['path'] ?? '')),
            'opacity'      => (float) ($data['opacity'] ?? 0.12),
            'size_percent' => (int) ($data['size_percent'] ?? 45),
        ];
    }

    /**
     * Dompdf/Imagick requieren ruta de archivo real; los data URI fallan en Cpdf::addPngFromFile.
     */
    protected function resolveDompdfImagePath(string $uri, string $path = '', ?string $tempDir = null): ?string
    {
        $path = trim($path);
        if ($path !== '' && is_file($path) && is_readable($path)) {
            return $path;
        }

        $uri = trim($uri);
        if ($uri === '') {
            return null;
        }

        if (! str_starts_with($uri, 'data:') && is_file($uri) && is_readable($uri)) {
            return $uri;
        }

        if (preg_match('#^data:([^;]+);base64,(.+)$#i', $uri, $matches)) {
            $binary = base64_decode($matches[2], true);
            if ($binary === false || $binary === '') {
                return null;
            }

            $tempDir = $tempDir ?: sys_get_temp_dir();
            $mime    = strtolower($matches[1]);
            $ext     = 'png';
            if (str_contains($mime, 'jpeg') || str_contains($mime, 'jpg')) {
                $ext = 'jpg';
            } elseif (str_contains($mime, 'gif')) {
                $ext = 'gif';
            } elseif (str_contains($mime, 'webp')) {
                $ext = 'webp';
            }

            $base = tempnam($tempDir, 'pdf_wm_');
            if ($base === false) {
                return null;
            }
            $file = $base . '.' . $ext;
            @unlink($base);
            if (file_put_contents($file, $binary) === false) {
                return null;
            }

            return $file;
        }

        return $uri;
    }

    /**
     * @param array<string, mixed>|null $watermarkData
     */
    protected function registerDompdfCallbacks(
        Dompdf $dompdf,
        ?array $watermarkData
    ): void {
        $callbacks = [];

        if (is_array($watermarkData)) {
            $uri = trim((string) ($watermarkData['uri'] ?? ''));
            if ($uri !== '') {
                $imagePath = $this->resolveDompdfImagePath(
                    $uri,
                    (string) ($watermarkData['path'] ?? ''),
                    $dompdf->getOptions()->getTempDir()
                );
                if ($imagePath !== null && $imagePath !== '') {
                    $opacity = max(0.05, min(0.9, (float) ($watermarkData['opacity'] ?? 0.12)));
                    $sizePct = max(10, min(95, (int) ($watermarkData['size_percent'] ?? 45)));
                    $callbacks[] = [
                        'event' => 'begin_page_render',
                        'f'     => static function ($frame, $canvas, FontMetrics $fontMetrics) use ($imagePath, $opacity, $sizePct): void {
                            unset($frame, $fontMetrics);
                            try {
                                $info = Helpers::dompdf_getimagesize($imagePath, $canvas->get_dompdf()->getHttpContext());
                            } catch (\Throwable $e) {
                                return;
                            }
                            if (! is_array($info) || count($info) < 2) {
                                return;
                            }
                            $srcW = (float) ($info[0] ?? 0);
                            $srcH = (float) ($info[1] ?? 0);
                            if ($srcW <= 0 || $srcH <= 0) {
                                return;
                            }
                            $pageW   = (float) $canvas->get_width();
                            $pageH   = (float) $canvas->get_height();
                            $targetW = $pageW * ($sizePct / 100);
                            $targetH = $targetW * ($srcH / $srcW);
                            $x       = ($pageW - $targetW) / 2;
                            $y       = ($pageH - $targetH) / 2;
                            $canvas->set_opacity($opacity);
                            $canvas->image($imagePath, $x, $y, $targetW, $targetH);
                            $canvas->set_opacity(1.0);
                        },
                    ];
                }
            }
        }

        if ($callbacks !== []) {
            $dompdf->setCallbacks($callbacks);
        }
    }

    /**
     * Opciones Dompdf compartidas (render principal y sondeo de páginas).
     */
    protected function makeDompdfOptions(bool $forPageCountProbe = false): Options
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', ! $forPageCountProbe);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isFontSubsettingEnabled', true);

        $tempDir = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'dompdf';
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }
        if (is_dir($tempDir) && is_writable($tempDir)) {
            $options->set('tempDir', $tempDir);
            $options->set('fontCache', $tempDir);
        }

        return $options;
    }

    /**
     * Genera PDF desde HTML
     */
    public function generate(string $html, string $filename = 'resultados.pdf'): string
    {
        unset($filename);

        $watermarkData = $this->extractWatermarkData($html);

        if (strpos($html, self::TOTAL_PAGES_TOKEN) !== false) {
            $probe = $this->makeDompdf($this->makeDompdfOptions(true));
            $this->renderHtmlToDompdf($probe, $html, null);
            $pageCount = (int) $probe->getCanvas()->get_page_count();
            if ($pageCount < 1) {
                $pageCount = 1;
            }
            $html          = str_replace(self::TOTAL_PAGES_TOKEN, (string) $pageCount, $html);
            $watermarkData = $this->extractWatermarkData($html);
        }

        $dompdf = $this->makeDompdf($this->makeDompdfOptions(false));
        $this->renderHtmlToDompdf($dompdf, $html, $watermarkData);

        return $dompdf->output();
    }

    /**
     * Devuelve el PDF como respuesta HTTP para descarga
     */
    public function download(string $html, string $filename = 'resultados.pdf'): void
    {
        $pdf = $this->generate($html, $filename);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $pdf;
        exit;
    }
}
