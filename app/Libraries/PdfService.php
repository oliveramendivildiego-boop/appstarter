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

    /**
     * Dompdf espera nombre estándar o [x0, y0, ancho_pt, alto_pt]; no [mm, mm].
     *
     * @param array<string, mixed>|null $pageSize
     *
     * @return string|array{0: float, 1: float, 2: float, 3: float}
     */
    protected function dompdfPaperFromPageSize(?array $pageSize): string|array
    {
        if (! is_array($pageSize) || ! isset($pageSize['key'])) {
            return 'letter';
        }

        $key = (string) $pageSize['key'];
        if (in_array($key, ['letter', 'a4', 'legal'], true)) {
            return $key;
        }

        if ($key === 'custom' && isset($pageSize['width_mm'], $pageSize['height_mm'])) {
            $widthPt  = (float) $pageSize['width_mm'] * 72 / 25.4;
            $heightPt = (float) $pageSize['height_mm'] * 72 / 25.4;

            return [0.0, 0.0, $widthPt, $heightPt];
        }

        return 'letter';
    }

    protected function makeDompdf(Options $options, ?array $pageSize = null): Dompdf
    {
        $dompdf = new Dompdf($options);
        $dompdf->setPaper($this->dompdfPaperFromPageSize($pageSize), 'portrait');

        return $dompdf;
    }

    /**
     * @param array<string, mixed>|null $watermarkData
     */
    protected function renderHtmlToDompdf(
        Dompdf $dompdf,
        string $html,
        ?array $watermarkData = null,
        array $paginationSlots = [],
    ): void {
        $this->registerDompdfCallbacks($dompdf, $watermarkData, $paginationSlots);

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
        ?array $watermarkData,
        array $paginationSlots = [],
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

        $callbacks = array_merge($callbacks, $this->buildPaginationCallbacks($paginationSlots));

        if ($callbacks !== []) {
            $dompdf->setCallbacks($callbacks);
        }
    }

    /**
     * @return list<array{0: string, 1: string, 2: float, 3: float, 4: string, 5: float, 6: string, 7: float, 8: float, 9: float, 10: float, 11: string}>
     */
    protected function extractPaginationSlots(string $html): array
    {
        if (! preg_match_all('/<!--\s*pdf-pagination:([A-Za-z0-9+\/=_-]+)\s*-->/', $html, $matches)) {
            return [];
        }

        $slots = [];
        foreach ($matches[1] as $encoded) {
            $json = base64_decode($encoded, true);
            if ($json === false) {
                continue;
            }
            $data = json_decode($json, true);
            if (! is_array($data)) {
                continue;
            }
            $slots[] = $this->normalizePaginationSlot($data);
        }

        return $slots;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array{prefix: string, zone: string, align: string, fontSize: float, fontFamily: string, color: string, mt: float, mr: float, mb: float, ml: float}
     */
    protected function normalizePaginationSlot(array $data): array
    {
        $zone = strtolower(trim((string) ($data['zone'] ?? 'header')));
        if (! in_array($zone, ['header', 'footer'], true)) {
            $zone = 'header';
        }
        $align = strtolower(trim((string) ($data['align'] ?? 'left')));
        if (! in_array($align, ['left', 'center', 'right'], true)) {
            $align = 'left';
        }

        return [
            'prefix'     => (string) ($data['prefix'] ?? ''),
            'zone'       => $zone,
            'align'      => $align,
            'fontSize'   => round(max(7.0, min(20.0, (float) ($data['fontSize'] ?? 10))), 2),
            'fontFamily' => trim((string) ($data['fontFamily'] ?? 'DejaVu Sans')) ?: 'DejaVu Sans',
            'color'      => trim((string) ($data['color'] ?? '#333333')) ?: '#333333',
            'mt'         => max(0.0, (float) ($data['mt'] ?? 15)),
            'mr'         => max(0.0, (float) ($data['mr'] ?? 15)),
            'mb'         => max(0.0, (float) ($data['mb'] ?? 15)),
            'ml'         => max(0.0, (float) ($data['ml'] ?? 15)),
        ];
    }

    /**
     * @param list<array{prefix: string, zone: string, align: string, fontSize: float, fontFamily: string, color: string, mt: float, mr: float, mb: float, ml: float}> $slots
     *
     * @return list<array{event: string, f: callable}>
     */
    protected function buildPaginationCallbacks(array $slots): array
    {
        if ($slots === []) {
            return [];
        }

        return [[
            'event' => 'end_document',
            'f'     => function (int $pageNumber, int $pageCount, $canvas, FontMetrics $fontMetrics) use ($slots): void {
                foreach ($slots as $slot) {
                    $this->paintPaginationOnPage($canvas, $fontMetrics, $slot, $pageNumber, $pageCount);
                }
            },
        ]];
    }

    /**
     * @param array{prefix: string, zone: string, align: string, fontSize: float, fontFamily: string, color: string, mt: float, mr: float, mb: float, ml: float} $slot
     */
    protected function paintPaginationOnPage(
        $canvas,
        FontMetrics $fontMetrics,
        array $slot,
        int $pageNumber,
        int $pageCount,
    ): void {
        if (! method_exists($canvas, 'get_cpdf')) {
            return;
        }

        try {
            $cpdf   = $canvas->get_cpdf();
            $pageW  = (float) $canvas->get_width();
            $pageH  = (float) $canvas->get_height();
            $mmToPt = 72 / 25.4;
            $mt     = $slot['mt'] * $mmToPt;
            $mr     = $slot['mr'] * $mmToPt;
            $mb     = $slot['mb'] * $mmToPt;
            $ml     = $slot['ml'] * $mmToPt;

            $fontSize   = $slot['fontSize'];
            $fontFamily = $slot['fontFamily'];
            $font       = $fontMetrics->getFont($fontFamily, 'normal');
            $text       = $slot['prefix'] . $pageNumber . ' de ' . $pageCount;

            $subset = $canvas->get_dompdf()->getOptions()->getIsFontSubsettingEnabled();
            $cpdf->selectFont($font, '', true, $subset);
            $textWidth = (float) $cpdf->getTextWidth($fontSize, $text);

            $y = ($slot['zone'] === 'footer')
                ? $mb + ($fontSize * 0.85)
                : $pageH - $mt - ($fontSize * 0.15);

            $x = match ($slot['align']) {
                'right'  => max($ml, $pageW - $mr - $textWidth),
                'center' => max($ml, ($pageW - $textWidth) / 2),
                default  => $ml,
            };

            $cpdf->setColor($this->hexColorToRgb($slot['color']), true);
            $cpdf->addText($x, $y, $fontSize, $text, 0);
        } catch (\Throwable $e) {
            // Sin paginación si la fuente o el canvas no están disponibles.
        }
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    protected function hexColorToRgb(string $hex): array
    {
        $hex = trim($hex);
        if ($hex === '') {
            return [0.2, 0.2, 0.2];
        }
        if ($hex[0] === '#') {
            $hex = substr($hex, 1);
        }
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return [0.2, 0.2, 0.2];
        }

        return [
            hexdec(substr($hex, 0, 2)) / 255,
            hexdec(substr($hex, 2, 2)) / 255,
            hexdec(substr($hex, 4, 2)) / 255,
        ];
    }

    /**
     * Opciones Dompdf compartidas (render principal y sondeo de páginas).
     */
    protected function makeDompdfOptions(bool $forPageCountProbe = false): Options
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', false);
        $options->set('isRemoteEnabled', ! $forPageCountProbe);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isFontSubsettingEnabled', true);

        $chroot = [];
        $publicRoot = realpath(FCPATH);
        if ($publicRoot !== false) {
            $chroot[] = $publicRoot;
        }
        $writableRoot = realpath(WRITEPATH);
        if ($writableRoot !== false) {
            $chroot[] = $writableRoot;
        }
        if ($chroot !== []) {
            $options->setChroot($chroot);
        }

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
     * El token en data-total de pdf_pagination es solo para impresión en navegador.
     */
    protected function htmlNeedsPageCountProbe(string $html): bool
    {
        if (! str_contains($html, self::TOTAL_PAGES_TOKEN)) {
            return false;
        }

        $withoutPaginationDataTotal = preg_replace(
            '/\sdata-total="' . preg_quote(self::TOTAL_PAGES_TOKEN, '/') . '"/',
            '',
            $html
        );

        return is_string($withoutPaginationDataTotal)
            && str_contains($withoutPaginationDataTotal, self::TOTAL_PAGES_TOKEN);
    }

    /**
     * Genera PDF desde HTML
     */
    public function generate(string $html, string $filename = 'resultados.pdf', ?array $pageSize = null): string
    {
        unset($filename);

        $watermarkData   = $this->extractWatermarkData($html);
        $paginationSlots = $this->extractPaginationSlots($html);

        if ($this->htmlNeedsPageCountProbe($html)) {
            $probe = $this->makeDompdf($this->makeDompdfOptions(true), $pageSize);
            $this->renderHtmlToDompdf($probe, $html, null, []);
            $pageCount = (int) $probe->getCanvas()->get_page_count();
            unset($probe);
            if ($pageCount < 1) {
                $pageCount = 1;
            }
            $html          = str_replace(self::TOTAL_PAGES_TOKEN, (string) $pageCount, $html);
            $watermarkData = $this->extractWatermarkData($html);
        }

        $dompdf = $this->makeDompdf($this->makeDompdfOptions(false), $pageSize);
        $this->renderHtmlToDompdf($dompdf, $html, $watermarkData, $paginationSlots);

        $output = $dompdf->output();
        unset($dompdf);

        return $output;
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
