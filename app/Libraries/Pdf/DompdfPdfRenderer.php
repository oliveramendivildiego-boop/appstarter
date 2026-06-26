<?php

namespace App\Libraries\Pdf;

use Dompdf\Dompdf;
use Dompdf\FontMetrics;
use Dompdf\Helpers;
use Dompdf\Options;

/**
 * Renderizador PDF basado en Dompdf (legacy / fallback).
 */
class DompdfPdfRenderer implements PdfRendererInterface
{
    private const TOTAL_PAGES_TOKEN = '__PDF_TOTAL_PAGES__';

    private const WATERMARK_MARKER = 'pdf-watermark-dompdf';

    private const ORDER_SHEET_HEADER_MARKER = 'pdf-order-sheet-header';

    /**
     * Dompdf espera nombre est├índar o [x0, y0, ancho_pt, alto_pt]; no [mm, mm].
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

        $slot = [
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
            'footerPrependedRowMm' => max(0.0, (float) ($data['footerPrependedRowMm'] ?? 0)),
            'cellPadHPx'      => max(0, (int) ($data['cellPadHPx'] ?? 0)),
            'stackOffsetPt'   => isset($data['stackOffsetPt']) ? (float) $data['stackOffsetPt'] : null,
            'footerPadTopPx'  => max(0.0, (float) ($data['footerPadTopPx'] ?? 6)),
            'inlineAfterLabel' => ! empty($data['inlineAfterLabel']),
            'inlineLabelText' => (string) ($data['inlineLabelText'] ?? ''),
            'inlineLabelFontSize' => round(max(7.0, min(20.0, (float) ($data['inlineLabelFontSize'] ?? ($data['fontSize'] ?? 10)))), 2),
            'inlineLabelFontWeight' => (string) ($data['inlineLabelFontWeight'] ?? 'normal'),
            'inlineLabelFontStyle'  => (string) ($data['inlineLabelFontStyle'] ?? 'normal'),
            'inlineLabelColor'      => (string) ($data['inlineLabelColor'] ?? '#333333'),
            'inlineLabelFontFamily' => trim((string) ($data['inlineLabelFontFamily'] ?? 'DejaVu Sans')) ?: 'DejaVu Sans',
            'textTransform'         => (string) ($data['textTransform'] ?? 'none'),
            'inlineLabelTextTransform' => (string) ($data['inlineLabelTextTransform'] ?? 'none'),
        ];

        return $slot;
    }

    /**
     * @param list<array<string, mixed>> $slots
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
     * @param array<string, mixed> $slot
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

        if (! empty($slot['inlineAfterLabel']) && ($slot['zone'] ?? '') === 'footer') {
            $this->paintInlineFooterPaginationOnPage($canvas, $fontMetrics, $slot, $pageNumber, $pageCount);

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

            $fontSize = (float) $slot['fontSize'];
            $font     = $fontMetrics->getFont(
                (string) $slot['fontFamily'],
                $this->dompdfFontVariant(
                    (string) ($slot['fontWeight'] ?? 'normal'),
                    (string) ($slot['fontStyle'] ?? 'normal')
                )
            );
            $format = (string) ($slot['format'] ?? 'page_of_total');
            $text   = $format === 'total_only'
                ? (string) $pageCount
                : (string) ($slot['prefix'] ?? '') . $pageNumber . ' de ' . $pageCount;
            $text     = $this->applyPaginationTextTransform($text, (string) ($slot['textTransform'] ?? 'none'));
            $textWidth = (float) $canvas->get_text_width($text, $font, $fontSize);

            if (($slot['zone'] ?? '') === 'footer' && ($slot['footerColumns'] ?? 0) > 0) {
                $pxToPt = 72.0 / 96.0;
                [$cellX0, $cellW, $cellPadHPt] = $this->paginationFooterCellMetrics($slot, $pageW, $ml, $mr, $pxToPt);
                $y = $this->paginationFooterBaselineY($slot, $pageH, $fontSize, $pageNumber, $mb, $mmToPt);
                $align = (string) ($slot['align'] ?? 'left');
                $x = match ($align) {
                    'right'  => $cellX0 + max($cellPadHPt, $cellW - $cellPadHPt - $textWidth),
                    'center' => $cellX0 + max($cellPadHPt, ($cellW - $textWidth) / 2),
                    default  => $cellX0 + $cellPadHPt,
                };
            } else {
                $x = match ((string) ($slot['align'] ?? 'left')) {
                    'right'  => max($ml, $pageW - $mr - $textWidth),
                    'center' => max($ml, ($pageW - $textWidth) / 2),
                    default  => $ml,
                };

                if (($slot['zone'] ?? '') === 'footer') {
                    $y = $this->paginationFooterBaselineY($slot, $pageH, $fontSize, $pageNumber, $mb, $mmToPt);
                } else {
                    $y = $mt + ($fontSize * 0.85);
                }
            }

            $canvas->text($x, $y, $text, $font, $fontSize, $this->hexColorToRgb((string) ($slot['color'] ?? '#333333')));
        } catch (\Throwable $e) {
            // Sin paginación si la fuente o el canvas no están disponibles.
        }
    }

    /**
     * «Página N de M» inline en el pie (label + número como bloque único).
     *
     * @param array<string, mixed> $slot
     */
    protected function paintInlineFooterPaginationOnPage(
        $canvas,
        FontMetrics $fontMetrics,
        array $slot,
        int $pageNumber,
        int $pageCount,
    ): void {
        try {
            $pageW  = (float) $canvas->get_width();
            $pageH  = (float) $canvas->get_height();
            $mmToPt = 72 / 25.4;
            $ml     = (float) $slot['ml'] * $mmToPt;
            $mr     = (float) $slot['mr'] * $mmToPt;
            $pxToPt = 72.0 / 96.0;

            $labelText = $this->applyPaginationTextTransform(
                (string) ($slot['inlineLabelText'] ?? ''),
                (string) ($slot['inlineLabelTextTransform'] ?? 'none'),
            );
            $numText = $this->applyPaginationTextTransform(
                $pageNumber . ' de ' . $pageCount,
                (string) ($slot['textTransform'] ?? 'none'),
            );
            if (trim($labelText) === '' && $numText === '') {
                return;
            }

            $labelSize = (float) ($slot['inlineLabelFontSize'] ?? $slot['fontSize'] ?? 10);
            $valueSize = (float) ($slot['fontSize'] ?? 10);
            $labelFont = $fontMetrics->getFont(
                (string) ($slot['inlineLabelFontFamily'] ?? 'DejaVu Sans'),
                $this->dompdfFontVariant(
                    (string) ($slot['inlineLabelFontWeight'] ?? 'normal'),
                    (string) ($slot['inlineLabelFontStyle'] ?? 'normal')
                )
            );
            $valueFont = $fontMetrics->getFont(
                (string) ($slot['fontFamily'] ?? 'DejaVu Sans'),
                $this->dompdfFontVariant(
                    (string) ($slot['fontWeight'] ?? 'normal'),
                    (string) ($slot['fontStyle'] ?? 'normal')
                )
            );

            $labelW = $labelText !== '' ? (float) $canvas->get_text_width($labelText, $labelFont, $labelSize) : 0.0;
            $numW   = (float) $canvas->get_text_width($numText, $valueFont, $valueSize);
            $groupW = $labelW + $numW;
            $y      = $this->paginationFooterBaselineY(
                $slot,
                $pageH,
                max($labelSize, $valueSize),
                $pageNumber,
                (float) $slot['mb'] * $mmToPt,
                $mmToPt
            );

            [$cellX0, $cellW, $cellPadHPt] = $this->paginationFooterCellMetrics($slot, $pageW, $ml, $mr, $pxToPt);
            $align = (string) ($slot['align'] ?? 'left');

            $x = match ($align) {
                'right'  => $cellX0 + $cellW - $cellPadHPt - $groupW,
                'center' => $cellX0 + max($cellPadHPt, ($cellW - $groupW) / 2),
                default  => $cellX0 + $cellPadHPt,
            };

            if ($labelText !== '') {
                $canvas->text($x, $y, $labelText, $labelFont, $labelSize, $this->hexColorToRgb((string) ($slot['inlineLabelColor'] ?? '#333333')));
            }
            $canvas->text($x + $labelW, $y, $numText, $valueFont, $valueSize, $this->hexColorToRgb((string) ($slot['color'] ?? '#333333')));
        } catch (\Throwable $e) {
            // Sin paginación inline en pie.
        }
    }

    /**
     * @param array<string, mixed> $slot
     *
     * @return array{0: float, 1: float, 2: float}
     */
    protected function paginationFooterCellMetrics(array $slot, float $pageW, float $ml, float $mr, float $pxToPt): array
    {
        $footerCols  = max(1, (int) ($slot['footerColumns'] ?? 1));
        $gridCol     = min(max(0, (int) ($slot['gridColumn'] ?? 0)), $footerCols - 1);
        $gridColSpan = max(1, min((int) ($slot['gridColumnSpan'] ?? 1), $footerCols - $gridCol));
        $contentW    = max(1.0, $pageW - $ml - $mr);
        $colW        = $contentW / $footerCols;
        $cellX0      = $ml + ($gridCol * $colW);
        $cellW       = $colW * $gridColSpan;
        $cellPadHPt  = max(0.0, (float) ($slot['cellPadHPx'] ?? 0)) * $pxToPt;

        return [$cellX0, $cellW, $cellPadHPt];
    }

    /**
     * @param array<string, mixed> $slot
     */
    protected function paginationFooterBaselineY(
        array $slot,
        float $pageH,
        float $fontSize,
        int $pageNumber,
        float $mbPt,
        float $mmToPt,
    ): float {
        $mt              = (float) ($slot['mt'] ?? 15) * $mmToPt;
        $footerReservePt = max(0.0, (float) ($slot['footerReserveMm'] ?? 0)) * $mmToPt;
        $prependedPt     = $pageNumber >= 2
            ? max(0.0, (float) ($slot['footerPrependedRowMm'] ?? 0)) * $mmToPt
            : 0.0;
        $pxToPt          = 72.0 / 96.0;
        $padTopPt        = max(0.0, (float) ($slot['footerPadTopPx'] ?? 6)) * $pxToPt;
        $lineHeight      = max(1.0, (float) ($slot['lineHeight'] ?? 1.35));
        $linePt          = $fontSize * $lineHeight;
        $rowOffset       = isset($slot['stackOffsetPt']) && is_numeric($slot['stackOffsetPt'])
            ? (float) $slot['stackOffsetPt']
            : (($max(0, (int) ($slot['gridRow'] ?? 0)) * ($linePt + max(0.0, (float) ($slot['footerRowGapPx'] ?? 0)) * $pxToPt))
                + (max(0, (int) ($slot['gridStack'] ?? 0)) * $linePt));
        if (! empty($slot['labelStacked'])) {
            $rowOffset += $linePt;
        }

        $footerTopY = $pageH - $mbPt - $footerReservePt;
        $valueFs    = (float) ($slot['fontSize'] ?? $fontSize);
        $y          = $footerTopY + $padTopPt + $prependedPt + $rowOffset + ($valueFs * 0.82);

        return max($mt + $valueFs, min($pageH - $mbPt - ($valueFs * 0.5), $y));
    }

    protected function applyPaginationTextTransform(string $text, string $transform): string
    {
        return match (strtolower(trim($transform))) {
            'uppercase'  => function_exists('mb_strtoupper') ? mb_strtoupper($text, 'UTF-8') : strtoupper($text),
            'lowercase'  => function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text),
            'capitalize' => function_exists('mb_convert_case') ? mb_convert_case($text, MB_CASE_TITLE, 'UTF-8') : ucwords(strtolower($text)),
            default      => $text,
        };
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
            'event' => 'end_document',
            'f'     => function (int $pageNumber, int $pageCount, $canvas, FontMetrics $fontMetrics) use ($slot): void {
                unset($pageCount);
                $this->paintOrderSheetHeaderOnPage($canvas, $fontMetrics, $slot, $pageNumber);
            },
        ]];
    }

    /**
     * Paciente / No. Orden en canvas (hojas 2+), alineado con márgenes @page.
     *
     * @param array{patient: string, order: string, ml: float, mr: float, mb: float, footerReserveMm: float, gapMm: float, footerBg: string, rowHeightMm: float} $slot
     */
    protected function paintOrderSheetHeaderOnPage(
        $canvas,
        FontMetrics $fontMetrics,
        array $slot,
        int $pageNumber,
    ): void {
        if ($pageNumber < 2) {
            return;
        }
        if (! method_exists($canvas, 'text') || ! method_exists($canvas, 'get_text_width')) {
            return;
        }

        try {
            $pageW    = (float) $canvas->get_width();
            $pageH    = (float) $canvas->get_height();
            $mmToPt   = 72 / 25.4;
            $ml       = (float) $slot['ml'] * $mmToPt;
            $mr       = (float) $slot['mr'] * $mmToPt;
            $mb       = (float) $slot['mb'] * $mmToPt;
            $fontSize = 9.0;
            $font     = $fontMetrics->getFont('DejaVu Sans', 'bold');
            $rgb      = $this->hexColorToRgb('#333333');

            $pxToPt    = 72.0 / 96.0;
            $reservePt = max(0.0, (float) ($slot['footerReserveMm'] ?? 0)) * $mmToPt;
            $footerTop = $pageH - $mb - $reservePt;
            $padTopPt  = 6.0 * $pxToPt;
            $oshPt     = max(1.0, (float) ($slot['rowHeightMm'] ?? 4.5)) * $mmToPt;
            $gapPt     = max(0.0, (float) ($slot['gapMm'] ?? 1.5)) * $mmToPt;
            // Banda Paciente/Orden en la zona superior reservada del pie (hojas 2+).
            $y         = $footerTop + $padTopPt + ($oshPt * 0.55) + ($fontSize * 0.35);

            $patient = (string) ($slot['patient'] ?? '');
            if ($patient !== '') {
                $canvas->text($ml, $y, $patient, $font, $fontSize, $rgb);
            }

            $order = (string) ($slot['order'] ?? '');
            if ($order !== '') {
                $orderW = (float) $canvas->get_text_width($order, $font, $fontSize);
                $x      = max($ml, $pageW - $mr - $orderW);
                $canvas->text($x, $y, $order, $font, $fontSize, $rgb);
            }

        } catch (\Throwable $e) {
            // Sin banda si el canvas o la fuente no están disponibles.
        }
    }

    /**
     * Opciones Dompdf compartidas (render principal y sondeo de p├íginas).
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
     * El token en data-total de pdf_pagination es solo para impresi├│n en navegador.
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

    public function renderHtml(string $html, PdfOptions $options): string
    {
        return $this->generateDompdf($html, $options->toLegacyPageSizeArray());
    }

    /**
     * Genera PDF desde HTML (Dompdf).
     *
     * @param array<string, mixed>|null $pageSize
     */
    public function generateDompdf(string $html, ?array $pageSize = null): string
    {

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

    public function engineName(): string
    {
        return 'dompdf';
    }
}
