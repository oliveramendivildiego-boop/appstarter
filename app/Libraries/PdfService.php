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

    private const ORDER_SHEET_HEADER_MARKER = 'pdf-order-sheet-header-dompdf';

    private const WATERMARK_MARKER = 'pdf-watermark-dompdf';

    protected function makeDompdf(Options $options): Dompdf
    {
        $dompdf = new Dompdf($options);
        $dompdf->setPaper('letter', 'portrait');

        return $dompdf;
    }

    /**
     * @param array<string, mixed>|null $orderSheetHeaderData
     * @param array<string, mixed>|null $watermarkData
     */
    protected function renderHtmlToDompdf(
        Dompdf $dompdf,
        string $html,
        ?array $orderSheetHeaderData = null,
        ?array $watermarkData = null
    ): void {
        $this->registerDompdfCallbacks($dompdf, $orderSheetHeaderData, $watermarkData);

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function extractOrderSheetHeaderData(string $html): ?array
    {
        if (strpos($html, self::ORDER_SHEET_HEADER_MARKER) === false) {
            return null;
        }
        if (! preg_match('/<!--\s*pdf-order-sheet-header-data:([A-Za-z0-9+\/=_-]+)\s*-->/', $html, $matches)) {
            return null;
        }
        $json = base64_decode($matches[1], true);
        if ($json === false) {
            return null;
        }
        $data = json_decode($json, true);

        return is_array($data) ? $data : null;
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
     * @param array<string, mixed>|null $orderSheetHeaderData
     * @param array<string, mixed>|null $watermarkData
     */
    protected function registerDompdfCallbacks(
        Dompdf $dompdf,
        ?array $orderSheetHeaderData,
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

        if (is_array($orderSheetHeaderData)) {
            $patientLine = trim((string) ($orderSheetHeaderData['patient'] ?? ''));
            $orderLine   = trim((string) ($orderSheetHeaderData['order'] ?? ''));
            if ($patientLine !== '' || $orderLine !== '') {
                $marginBottomMm   = (float) ($orderSheetHeaderData['margin_bottom_mm'] ?? 15);
                $marginLeftMm     = (float) ($orderSheetHeaderData['margin_left_mm'] ?? 15);
                $marginRightMm    = (float) ($orderSheetHeaderData['margin_right_mm'] ?? 15);
                $footerReserveMm  = ! empty($orderSheetHeaderData['footer_enabled'])
                    ? (float) ($orderSheetHeaderData['footer_reserve_mm'] ?? 22)
                    : 0.0;
                $gapAboveFooterMm = (float) ($orderSheetHeaderData['gap_above_footer_mm'] ?? 1.5);
                $mmToPt           = 72 / 25.4;

                $callbacks[] = [
                    'event' => 'end_document',
                    'f'     => static function (
                        int $pageNumber,
                        int $pageCount,
                        $pdf,
                        FontMetrics $fontMetrics
                    ) use (
                        $patientLine,
                        $orderLine,
                        $marginBottomMm,
                        $marginLeftMm,
                        $marginRightMm,
                        $footerReserveMm,
                        $gapAboveFooterMm,
                        $mmToPt
                    ): void {
                        unset($pageCount);
                        if ($pageNumber <= 1) {
                            return;
                        }

                        try {
                            $font = $fontMetrics->getFont('DejaVu Sans', 'bold');
                        } catch (\Throwable $e) {
                            $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
                        }

                        $size  = 9.0;
                        $color = [0.15, 0.15, 0.15];
                        $offsetFromBottomMm = $marginBottomMm + $footerReserveMm + $gapAboveFooterMm;
                        $y                  = $pdf->get_height() - ($offsetFromBottomMm * $mmToPt);
                        $xLeft              = $marginLeftMm * $mmToPt;
                        $xPad               = $marginRightMm * $mmToPt;

                        if ($patientLine !== '') {
                            $pdf->text($xLeft, $y, $patientLine, $font, $size, $color);
                        }
                        if ($orderLine !== '') {
                            $orderWidth = $fontMetrics->getTextWidth($orderLine, $font, $size);
                            $xOrder     = $pdf->get_width() - $xPad - $orderWidth;
                            $pdf->text($xOrder, $y, $orderLine, $font, $size, $color);
                        }
                    },
                ];
            }
        }

        if ($callbacks !== []) {
            $dompdf->setCallbacks($callbacks);
        }
    }

    /**
     * Genera PDF desde HTML
     */
    public function generate(string $html, string $filename = 'resultados.pdf'): string
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $orderSheetHeaderData = $this->extractOrderSheetHeaderData($html);
        $watermarkData        = $this->extractWatermarkData($html);

        if (strpos($html, self::TOTAL_PAGES_TOKEN) !== false) {
            $probe = $this->makeDompdf($options);
            $this->renderHtmlToDompdf($probe, $html, null, null);
            $pageCount = (int) $probe->getCanvas()->get_page_count();
            if ($pageCount < 1) {
                $pageCount = 1;
            }
            $html = str_replace(self::TOTAL_PAGES_TOKEN, (string) $pageCount, $html);
            $orderSheetHeaderData = $this->extractOrderSheetHeaderData($html);
            $watermarkData        = $this->extractWatermarkData($html);
        }

        $dompdf = $this->makeDompdf($options);
        $this->renderHtmlToDompdf($dompdf, $html, $orderSheetHeaderData, $watermarkData);

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
