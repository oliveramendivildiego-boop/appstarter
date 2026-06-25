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

    private const ORDER_SHEET_HEADER_MARKER = 'pdf-order-sheet-header';

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
        ?array $orderSheetSlot = null,
    ): void {
        $this->registerDompdfCallbacks($dompdf, $watermarkData, $paginationSlots, $orderSheetSlot);

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
        ?array $orderSheetSlot = null,
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
        $callbacks = array_merge($callbacks, $this->buildOrderSheetHeaderCallbacks($orderSheetSlot));

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
     * @return array{prefix: string, zone: string, align: string, fontSize: float, fontFamily: string, color: string, mt: float, mr: float, mb: float, ml: float, footerReserveMm: float, gridColumn: int, gridColumnSpan: int, gridRow: int, gridStack: int, footerColumns: int, footerRows: int, footerRowGapPx: float, lineHeight: float}
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
            'prefix'          => (string) ($data['prefix'] ?? ''),
            'zone'            => $zone,
            'align'           => $align,
            'fontSize'        => round(max(7.0, min(20.0, (float) ($data['fontSize'] ?? 10))), 2),
            'fontFamily'      => trim((string) ($data['fontFamily'] ?? 'DejaVu Sans')) ?: 'DejaVu Sans',
            'fontWeight'      => trim((string) ($data['fontWeight'] ?? 'normal')) ?: 'normal',
            'fontStyle'       => trim((string) ($data['fontStyle'] ?? 'normal')) ?: 'normal',
            'color'           => trim((string) ($data['color'] ?? '#333333')) ?: '#333333',
            'mt'              => max(0.0, (float) ($data['mt'] ?? 15)),
            'mr'              => max(0.0, (float) ($data['mr'] ?? 15)),
            'mb'              => max(0.0, (float) ($data['mb'] ?? 15)),
            'ml'              => max(0.0, (float) ($data['ml'] ?? 15)),
            'footerReserveMm' => max(0.0, (float) ($data['footerReserveMm'] ?? 0)),
            'gridColumn'      => max(0, (int) ($data['gridColumn'] ?? 0)),
            'gridColumnSpan'  => max(1, (int) ($data['gridColumnSpan'] ?? 1)),
            'gridRow'         => max(0, (int) ($data['gridRow'] ?? 0)),
            'gridStack'       => max(0, (int) ($data['gridStack'] ?? 0)),
            'footerColumns'   => max(0, (int) ($data['footerColumns'] ?? 0)),
            'footerRows'      => max(1, (int) ($data['footerRows'] ?? 1)),
            'footerRowGapPx'  => max(0.0, (float) ($data['footerRowGapPx'] ?? 0)),
            'lineHeight'      => max(1.0, (float) ($data['lineHeight'] ?? 1.35)),
            'labelStacked'    => ! empty($data['labelStacked']),
            'format'          => (string) ($data['format'] ?? 'page_of_total'),
        ];
    }

    /**
     * @param list<array{prefix: string, zone: string, align: string, fontSize: float, fontFamily: string, color: string, mt: float, mr: float, mb: float, ml: float}> $slots
     *
     * @return list<array{event: string, f: callable}>
     */
    protected function buildPaginationCallbacks(array $slots): array
    {
        // Encabezado: canvas. Pie: HTML en la cuadrícula del footer (posición y estilos de plantilla).
        $slots = array_values(array_filter(
            $slots,
            static fn (array $slot): bool => strtolower((string) ($slot['zone'] ?? 'header')) !== 'footer'
        ));
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
     * @param array{prefix: string, zone: string, align: string, fontSize: float, fontFamily: string, color: string, mt: float, mr: float, mb: float, ml: float, footerReserveMm: float} $slot
     */
    protected function paintPaginationOnPage(
        $canvas,
        FontMetrics $fontMetrics,
        array $slot,
        int $pageNumber,
        int $pageCount,
    ): void {
        if (! method_exists($canvas, 'text') || ! method_exists($canvas, 'get_text_width')) {
            return;
        }

        try {
            $pageW  = (float) $canvas->get_width();
            $pageH  = (float) $canvas->get_height();
            $mmToPt = 72 / 25.4;
            $mt     = $slot['mt'] * $mmToPt;
            $mr     = $slot['mr'] * $mmToPt;
            $mb     = $slot['mb'] * $mmToPt;
            $ml     = $slot['ml'] * $mmToPt;

            $fontSize   = $slot['fontSize'];
            $fontFamily = $slot['fontFamily'];
            $font       = $fontMetrics->getFont(
                $fontFamily,
                $this->dompdfFontVariant(
                    (string) ($slot['fontWeight'] ?? 'normal'),
                    (string) ($slot['fontStyle'] ?? 'normal')
                )
            );
            $text       = $slot['prefix'] . $pageNumber . ' de ' . $pageCount;
            $textWidth  = (float) $canvas->get_text_width($text, $font, $fontSize);
            $lineHeight = max(1.0, (float) ($slot['lineHeight'] ?? 1.35));
            $linePt     = $fontSize * $lineHeight;
            $pxToPt     = 72.0 / 96.0;

            if ($slot['zone'] === 'footer' && ($slot['footerColumns'] ?? 0) > 0) {
                $footerCols    = max(1, (int) $slot['footerColumns']);
                $gridCol       = min(max(0, (int) ($slot['gridColumn'] ?? 0)), $footerCols - 1);
                $gridColSpan   = max(1, min((int) ($slot['gridColumnSpan'] ?? 1), $footerCols - $gridCol));
                $gridRow       = max(0, (int) ($slot['gridRow'] ?? 0));
                $gridStack     = max(0, (int) ($slot['gridStack'] ?? 0));
                $rowGapPt      = max(0.0, (float) ($slot['footerRowGapPx'] ?? 0)) * $pxToPt;
                $contentW      = max(1.0, $pageW - $ml - $mr);
                $colW          = $contentW / $footerCols;
                $cellX0        = $ml + ($gridCol * $colW);
                $cellW         = $colW * $gridColSpan;
                $footerReservePt = max(0.0, (float) ($slot['footerReserveMm'] ?? 0)) * $mmToPt;
                $footerTopY    = $pageH - $mb - $footerReservePt;
                $padTopPt      = 6.0 * $pxToPt;
                $rowOffset     = ($gridRow * ($linePt + $rowGapPt)) + ($gridStack * $linePt);
                if (! empty($slot['labelStacked'])) {
                    $rowOffset += $linePt;
                }
                $y             = $footerTopY + $padTopPt + $rowOffset + ($fontSize * 0.82);
                $y             = max($mt + $fontSize, min($pageH - $mb - $fontSize * 0.5, $y));

                $x = match ($slot['align']) {
                    'right'  => $cellX0 + max(0.0, $cellW - $textWidth),
                    'center' => $cellX0 + max(0.0, ($cellW - $textWidth) / 2),
                    default  => $cellX0,
                };
            } else {
                $x = match ($slot['align']) {
                    'right'  => max($ml, $pageW - $mr - $textWidth),
                    'center' => max($ml, ($pageW - $textWidth) / 2),
                    default  => $ml,
                };

                if ($slot['zone'] === 'footer') {
                    $footerReservePt = max(0.0, (float) ($slot['footerReserveMm'] ?? 0)) * $mmToPt;
                    $bandPt          = $footerReservePt > 0 ? $footerReservePt : ($fontSize * 2.4);
                    $y               = $pageH - $mb - ($bandPt * 0.42) - ($fontSize * 0.15);
                    $y               = max($mt + $fontSize, min($pageH - $mb - $fontSize * 0.5, $y));
                } else {
                    $y = $mt + ($fontSize * 0.85);
                }
            }

            $canvas->text($x, $y, $text, $font, $fontSize, $this->hexColorToRgb($slot['color']));
        } catch (\Throwable $e) {
            // Sin paginación si la fuente o el canvas no están disponibles.
        }
    }

    protected function dompdfFontVariant(string $fontWeight, string $fontStyle): string
    {
        $weight = strtolower(trim($fontWeight));
        $style  = strtolower(trim($fontStyle));
        $bold   = in_array($weight, ['bold', '600', '700', '800'], true);
        $italic = in_array($style, ['italic', 'oblique'], true);

        if ($bold && $italic) {
            return 'bold_italic';
        }
        if ($bold) {
            return 'bold';
        }
        if ($italic) {
            return 'italic';
        }

        return 'normal';
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
     * @return array{patient: string, order: string, ml: float, mr: float, mb: float, footerReserveMm: float, gapMm: float, footerBg: string, rowHeightMm: float}|null
     */
    protected function extractOrderSheetHeaderSlot(string $html): ?array
    {
        $pattern = '/<!--\s*' . preg_quote(self::ORDER_SHEET_HEADER_MARKER, '/') . ':([A-Za-z0-9+\/=_-]+)\s*-->/';
        if (! preg_match($pattern, $html, $matches)) {
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

        $patient = trim((string) ($data['patient'] ?? ''));
        $order   = trim((string) ($data['order'] ?? ''));
        if ($patient === '' && $order === '') {
            return null;
        }

        return [
            'patient'         => $patient,
            'order'           => $order,
            'ml'              => max(0.0, (float) ($data['ml'] ?? 15)),
            'mr'              => max(0.0, (float) ($data['mr'] ?? 15)),
            'mb'              => max(0.0, (float) ($data['mb'] ?? 15)),
            'footerReserveMm' => max(0.0, (float) ($data['footerReserveMm'] ?? 0)),
            'gapMm'           => max(0.0, (float) ($data['gapMm'] ?? 1.5)),
            'footerBg'        => (string) ($data['footerBg'] ?? '#ffffff'),
            'rowHeightMm'     => max(1.0, (float) ($data['rowHeightMm'] ?? 4.5)),
        ];
    }

    /**
     * @param array{patient: string, order: string, ml: float, mr: float, mb: float, footerReserveMm: float, gapMm: float, footerBg: string, rowHeightMm: float}|null $slot
     *
     * @return list<array{event: string, f: callable}>
     */
    protected function buildOrderSheetHeaderCallbacks(?array $slot): array
    {
        if ($slot === null) {
            return [];
        }

        return [[
            'event' => 'begin_page_reflow',
            'f'     => function ($frame, $canvas, FontMetrics $fontMetrics) use ($slot): void {
                unset($slot, $fontMetrics);
                if (! $frame instanceof \Dompdf\Frame) {
                    return;
                }
                if ((int) $canvas->get_page_number() >= 2) {
                    return;
                }
                $this->stripOrderSheetRowsForPageOne($frame);
            },
        ]];
    }

    protected function isOrderSheetRowFrame(\Dompdf\Frame $frame): bool
    {
        $node = $frame->get_node();

        return $node instanceof \DOMElement
            && $node->hasAttribute('data-order-sheet-from-page-two')
            && str_contains($node->getAttribute('class'), 'pdf-order-sheet-table-row');
    }

    /**
     * Elimina la fila Paciente / No. Orden del árbol en hoja 1 (antes del reflow).
     */
    protected function stripOrderSheetRowsForPageOne(\Dompdf\Frame $frame): void
    {
        $rows = [];
        $this->collectOrderSheetRowFrames($frame, $rows);
        foreach ($rows as $rowFrame) {
            $rowFrame->dispose(false);
        }
    }

    /**
     * @param list<\Dompdf\Frame> $rows
     */
    protected function collectOrderSheetRowFrames(\Dompdf\Frame $frame, array &$rows): void
    {
        if ($this->isOrderSheetRowFrame($frame)) {
            $rows[] = $frame;

            return;
        }

        foreach ($frame->get_children() as $child) {
            if ($child instanceof \Dompdf\Frame) {
                $this->collectOrderSheetRowFrames($child, $rows);
            }
        }
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
        if (str_contains($html, 'data-total="' . self::TOTAL_PAGES_TOKEN . '"')) {
            return true;
        }

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
        $orderSheetSlot  = $this->extractOrderSheetHeaderSlot($html);

        if ($this->htmlNeedsPageCountProbe($html)) {
            $probe = $this->makeDompdf($this->makeDompdfOptions(true), $pageSize);
            $this->renderHtmlToDompdf($probe, $html, null, []);
            $pageCount = (int) $probe->getCanvas()->get_page_count();
            unset($probe);
            if ($pageCount < 1) {
                $pageCount = 1;
            }
            $html          = str_replace(self::TOTAL_PAGES_TOKEN, (string) $pageCount, $html);
            $html          = str_replace(
                'data-total="' . self::TOTAL_PAGES_TOKEN . '"',
                'data-total="' . $pageCount . '"',
                $html
            );
            $watermarkData   = $this->extractWatermarkData($html);
            $paginationSlots = $this->extractPaginationSlots($html);
            $orderSheetSlot  = $this->extractOrderSheetHeaderSlot($html);
        }

        $dompdf = $this->makeDompdf($this->makeDompdfOptions(false), $pageSize);
        $this->renderHtmlToDompdf($dompdf, $html, $watermarkData, $paginationSlots, $orderSheetSlot);

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
