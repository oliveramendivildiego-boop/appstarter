<?php

namespace App\Libraries\Pdf;

use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * Estampa «N de M» en el pie de PDF Chromium (sin depender de Python).
 */
class ChromiumPdfPaginationPhpStamper
{
    /**
     * @param array<int, array<string, mixed>> $slots
     * @param array{patient: string, order: string, ml: float, mr: float, mb: float, footerReserveMm: float, gapMm: float, footerBg: string, rowHeightMm: float}|null $orderSheetSlot
     */
    public static function stampBinary(string $pdfBinary, array $slots, string $tempDir, ?array $orderSheetSlot = null): ?string
    {
        if ($slots === [] && $orderSheetSlot === null) {
            return $pdfBinary;
        }

        $inputPath  = $tempDir . DIRECTORY_SEPARATOR . 'stamp_in_' . bin2hex(random_bytes(6)) . '.pdf';
        $outputPath = $tempDir . DIRECTORY_SEPARATOR . 'stamp_out_' . bin2hex(random_bytes(6)) . '.pdf';

        try {
            if (file_put_contents($inputPath, $pdfBinary) === false) {
                return null;
            }

            $pdf = new Fpdi('P', 'pt');
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->setAutoPageBreak(false);
            $pdf->setMargins(0, 0, 0);
            self::disableTcpdfLink($pdf);

            $pageCount = $pdf->setSourceFile($inputPath);
            if ($pageCount < 1) {
                return null;
            }

            for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                $tplId = $pdf->importPage($pageNumber);
                $size  = $pdf->getTemplateSize($tplId);
                if (! is_array($size)) {
                    continue;
                }

                $pageW = (float) ($size['width'] ?? 612);
                $pageH = (float) ($size['height'] ?? 792);
                $orientation = ($size['width'] ?? 0) > ($size['height'] ?? 0) ? 'L' : 'P';

                $pdf->AddPage($orientation, [$pageW, $pageH]);
                $pdf->useTemplate($tplId);

                foreach ($slots as $slot) {
                    if (! is_array($slot)) {
                        continue;
                    }
                    self::paintSlot($pdf, $slot, $pageNumber, $pageCount, $pageW, $pageH);
                }

                if ($orderSheetSlot !== null && $pageNumber >= 2) {
                    ChromiumPdfOrderSheetStamper::paintOrderSheetRow($pdf, $orderSheetSlot, $pageW, $pageH);
                }
            }

            $pdf->Output($outputPath, 'F');

            if (! is_file($outputPath)) {
                return null;
            }

            $stamped = file_get_contents($outputPath);

            return is_string($stamped) && $stamped !== '' ? $stamped : null;
        } catch (\Throwable $e) {
            log_message('warning', 'ChromiumPdfPaginationPhpStamper: {msg}', ['msg' => $e->getMessage()]);

            return null;
        } finally {
            @unlink($inputPath);
            @unlink($outputPath);
        }
    }

    /**
     * @param array<string, mixed> $slot
     */
    private static function paintSlot(
        Fpdi $pdf,
        array $slot,
        int $pageNumber,
        int $pageCount,
        float $pageW,
        float $pageH,
    ): void {
        if (! empty($slot['inlineAfterLabel']) && strtolower((string) ($slot['zone'] ?? '')) === 'footer') {
            self::paintInlineFooterPagination($pdf, $slot, $pageNumber, $pageCount, $pageW, $pageH);

            return;
        }

        $text = self::buildText($slot, $pageNumber, $pageCount);
        if ($text === '') {
            return;
        }

        $text = self::applyTextTransform($text, (string) ($slot['textTransform'] ?? 'none'));

        $valueStyle = self::valueStyleFromSlot($slot);
        $textWidth  = self::measureStyledTextWidth($pdf, $text, $valueStyle);
        [$x, $y]    = self::computePosition($slot, $pageW, $pageH, $textWidth, $pdf);

        self::paintStyledText($pdf, $x, $y, $text, $valueStyle);
    }

    /**
     * «Página N de M» como bloque único alineado (igual que el flujo HTML / Dompdf).
     *
     * @param array<string, mixed> $slot
     */
    private static function paintInlineFooterPagination(
        Fpdi $pdf,
        array $slot,
        int $pageNumber,
        int $pageCount,
        float $pageW,
        float $pageH,
    ): void {
        $labelText = self::applyTextTransform(
            (string) ($slot['inlineLabelText'] ?? ''),
            (string) ($slot['inlineLabelTextTransform'] ?? 'none'),
        );
        $numText = self::applyTextTransform(
            $pageNumber . ' de ' . $pageCount,
            (string) ($slot['textTransform'] ?? 'none'),
        );
        if (trim($labelText) === '' && $numText === '') {
            return;
        }

        $labelStyle = self::labelStyleFromSlot($slot);
        $valueStyle = self::valueStyleFromSlot($slot);

        $labelW = $labelText !== '' ? self::measureStyledTextWidth($pdf, $labelText, $labelStyle) : 0.0;
        $numW   = self::measureStyledTextWidth($pdf, $numText, $valueStyle);

        $groupW = $labelW + $numW;
        [$xLabel, $y] = self::computeInlineGroupPosition($slot, $pageW, $pageH, $groupW);

        if ($labelText !== '') {
            self::paintStyledText($pdf, $xLabel, $y, $labelText, $labelStyle);
        }

        self::paintStyledText($pdf, $xLabel + $labelW, $y, $numText, $valueStyle);
    }

    /**
     * @param array<string, mixed> $slot
     *
     * @return array{fontFamily: string, fontSize: float, fontWeight: string, fontStyle: string, color: string, textTransform: string, letterSpacingEm: float}
     */
    private static function valueStyleFromSlot(array $slot): array
    {
        return [
            'fontFamily'       => (string) ($slot['fontFamily'] ?? 'DejaVu Sans'),
            'fontSize'         => (float) ($slot['fontSize'] ?? 10),
            'fontWeight'       => (string) ($slot['fontWeight'] ?? 'normal'),
            'fontStyle'        => (string) ($slot['fontStyle'] ?? 'normal'),
            'color'            => (string) ($slot['color'] ?? '#333333'),
            'textTransform'    => (string) ($slot['textTransform'] ?? 'none'),
            'letterSpacingEm'  => (float) ($slot['letterSpacingEm'] ?? 0),
        ];
    }

    /**
     * @param array<string, mixed> $slot
     *
     * @return array{fontFamily: string, fontSize: float, fontWeight: string, fontStyle: string, color: string, textTransform: string, letterSpacingEm: float}
     */
    private static function labelStyleFromSlot(array $slot): array
    {
        return [
            'fontFamily'      => (string) ($slot['inlineLabelFontFamily'] ?? 'DejaVu Sans'),
            'fontSize'        => (float) ($slot['inlineLabelFontSize'] ?? $slot['fontSize'] ?? 10),
            'fontWeight'      => (string) ($slot['inlineLabelFontWeight'] ?? 'normal'),
            'fontStyle'       => (string) ($slot['inlineLabelFontStyle'] ?? 'normal'),
            'color'           => (string) ($slot['inlineLabelColor'] ?? '#333333'),
            'textTransform'   => (string) ($slot['inlineLabelTextTransform'] ?? 'none'),
            'letterSpacingEm' => (float) ($slot['inlineLabelLetterSpacingEm'] ?? 0),
        ];
    }

    /**
     * @param array{fontFamily: string, fontSize: float, fontWeight: string, fontStyle: string, color: string, textTransform: string, letterSpacingEm: float} $style
     */
    private static function measureStyledTextWidth(Fpdi $pdf, string $text, array $style): float
    {
        $prev = self::preparePdfFont($pdf, $style);
        $width = $pdf->GetStringWidth($text);
        self::restorePdfFont($pdf, $prev);

        return $width;
    }

    /**
     * @param array{fontFamily: string, fontSize: float, fontWeight: string, fontStyle: string, color: string, textTransform: string, letterSpacingEm: float} $style
     */
    private static function paintStyledText(Fpdi $pdf, float $x, float $y, string $text, array $style): void
    {
        $prev = self::preparePdfFont($pdf, $style);
        $pdf->Text($x, $y, $text);
        self::restorePdfFont($pdf, $prev);
    }

    /**
     * @param array{fontFamily: string, fontSize: float, fontWeight: string, fontStyle: string, color: string, textTransform: string, letterSpacingEm: float} $style
     *
     * @return array{family: string, style: string, size: float, spacing: float}
     */
    private static function preparePdfFont(Fpdi $pdf, array $style): array
    {
        $prev = [
            'family'  => (string) $pdf->getFontFamily(),
            'style'   => (string) $pdf->getFontStyle(),
            'size'    => (float) $pdf->getFontSizePt(),
            'spacing' => method_exists($pdf, 'getFontSpacing') ? (float) $pdf->getFontSpacing() : 0.0,
        ];

        $fontSize = max(6.0, min(24.0, (float) ($style['fontSize'] ?? 10)));
        $pdf->SetFont(
            self::resolveTcpdfFontFamily((string) ($style['fontFamily'] ?? 'DejaVu Sans')),
            self::tcpdfFontStyle((string) ($style['fontWeight'] ?? 'normal'), (string) ($style['fontStyle'] ?? 'normal')),
            $fontSize,
        );

        if (method_exists($pdf, 'setFontSpacing')) {
            $pdf->setFontSpacing(max(0.0, (float) ($style['letterSpacingEm'] ?? 0)) * $fontSize);
        }

        [$r, $g, $b] = self::hexToRgb((string) ($style['color'] ?? '#333333'));
        $pdf->SetTextColor($r, $g, $b);

        return $prev;
    }

    /**
     * @param array{family: string, style: string, size: float, spacing: float} $prev
     */
    private static function restorePdfFont(Fpdi $pdf, array $prev): void
    {
        $pdf->SetFont((string) $prev['family'], (string) $prev['style'], (float) $prev['size']);
        if (method_exists($pdf, 'setFontSpacing')) {
            $pdf->setFontSpacing((float) $prev['spacing']);
        }
    }

    private static function resolveTcpdfFontFamily(string $family): string
    {
        return match (trim($family)) {
            'Times New Roman' => 'times',
            'Courier New'     => 'courier',
            'Helvetica', 'Arial' => 'helvetica',
            default           => 'dejavusans',
        };
    }

    private static function applyTextTransform(string $text, string $transform): string
    {
        return match (strtolower(trim($transform))) {
            'uppercase'  => function_exists('mb_strtoupper') ? mb_strtoupper($text, 'UTF-8') : strtoupper($text),
            'lowercase'  => function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text),
            'capitalize' => function_exists('mb_convert_case') ? mb_convert_case($text, MB_CASE_TITLE, 'UTF-8') : ucwords(strtolower($text)),
            default      => $text,
        };
    }

    /**
     * @param array<string, mixed> $slot
     *
     * @return array{0: float, 1: float}
     */
    private static function computeInlineGroupPosition(
        array $slot,
        float $pageW,
        float $pageH,
        float $groupW,
    ): array {
        $fontSize = (float) ($slot['fontSize'] ?? 10);
        [, $y] = self::computeFooterGridY($slot, $pageW, $pageH, $fontSize);

        $mr = self::mmToPt((float) ($slot['mr'] ?? 15));
        $ml = self::mmToPt((float) ($slot['ml'] ?? 15));
        $pxToPt = 72.0 / 96.0;
        $footerCols = max(1, (int) ($slot['footerColumns'] ?? 1));
        $gridCol = min(max(0, (int) ($slot['gridColumn'] ?? 0)), $footerCols - 1);
        $gridColSpan = max(1, min((int) ($slot['gridColumnSpan'] ?? 1), $footerCols - $gridCol));
        $contentW = max(1.0, $pageW - $ml - $mr);
        $colW = $contentW / $footerCols;
        $cellX0 = $ml + ($gridCol * $colW);
        $cellW = $colW * $gridColSpan;
        $cellPadHPt = max(0.0, (float) ($slot['cellPadHPx'] ?? 0)) * $pxToPt;
        $cellRight = $cellX0 + $cellW - $cellPadHPt;
        $contentRight = $pageW - $mr - 4.0;
        $groupRight = min($cellRight, $contentRight) - 1.5;

        $align = strtolower((string) ($slot['align'] ?? 'left'));
        $x = match ($align) {
            'right'  => $groupRight - $groupW,
            'center' => $cellX0 + max($cellPadHPt, ($cellW - $groupW) / 2),
            default  => $cellX0 + $cellPadHPt,
        };

        $x = max($cellX0 + $cellPadHPt, min($x, $groupRight - $groupW));

        return [$x, $y];
    }

    /**
     * @param array<string, mixed> $slot
     *
     * @return array{0: float, 1: float}
     */
    private static function computeFooterGridY(
        array $slot,
        float $pageW,
        float $pageH,
        float $fontSize,
    ): array {
        $lineHeight = max(1.0, (float) ($slot['lineHeight'] ?? 1.35));
        $linePt     = $fontSize * $lineHeight;
        $rowOffset  = self::resolveFooterRowOffsetPt($slot, $linePt);
        $yTop       = self::resolveFooterBaselineY($slot, $pageH, $fontSize, $rowOffset);

        return [0.0, $yTop];
    }

    private static function resolveFooterRowOffsetPt(array $slot, float $linePt): float
    {
        $gridRow   = max(0, (int) ($slot['gridRow'] ?? 0));
        $gridStack = max(0, (int) ($slot['gridStack'] ?? 0));
        $pxToPt    = 72.0 / 96.0;
        $rowGapPt  = max(0.0, (float) ($slot['footerRowGapPx'] ?? 0)) * $pxToPt;

        if (isset($slot['stackOffsetPt']) && is_numeric($slot['stackOffsetPt'])) {
            $rowOffset = (float) $slot['stackOffsetPt'];
        } else {
            $rowOffset = ($gridRow * ($linePt + $rowGapPt)) + ($gridStack * $linePt);
        }

        if (! empty($slot['labelStacked'])) {
            $rowOffset += $linePt;
        }

        return $rowOffset;
    }

    private static function resolveFooterPadTopPt(array $slot): float
    {
        $pxToPt = 72.0 / 96.0;
        $padPx  = isset($slot['footerPadTopPx']) && is_numeric($slot['footerPadTopPx'])
            ? (float) $slot['footerPadTopPx']
            : 8.0;

        return max(0.0, $padPx) * $pxToPt;
    }

    private static function resolveFooterBaselineY(
        array $slot,
        float $pageH,
        float $fontSize,
        float $rowOffset,
    ): float {
        $mt              = self::mmToPt((float) ($slot['mt'] ?? 15));
        $mb              = self::mmToPt((float) ($slot['mb'] ?? 15));
        $footerReservePt = max(0.0, self::mmToPt((float) ($slot['footerReserveMm'] ?? 0)));
        $prependedPt     = max(0.0, self::mmToPt((float) ($slot['footerPrependedRowMm'] ?? 0)));
        $footerTopY      = $pageH - $mb - $footerReservePt;
        $valueFs         = (float) ($slot['fontSize'] ?? $fontSize);
        $yTop = $footerTopY + self::resolveFooterPadTopPt($slot) + $prependedPt + $rowOffset + ($valueFs * 0.82);

        return max($mt + $valueFs, min($pageH - $mb - ($valueFs * 0.5), $yTop));
    }

    /**
     * @param array<string, mixed> $slot
     */
    private static function buildText(array $slot, int $pageNumber, int $pageCount): string
    {
        $format = (string) ($slot['format'] ?? 'page_of_total');
        $prefix = (string) ($slot['prefix'] ?? '');

        if ($format === 'total_only') {
            return (string) $pageCount;
        }

        return $prefix . $pageNumber . ' de ' . $pageCount;
    }

    /**
     * @param array<string, mixed> $slot
     *
     * @return array{0: float, 1: float}
     */
    private static function computePosition(
        array $slot,
        float $pageW,
        float $pageH,
        float $textWidth,
        Fpdi $pdf,
    ): array {
        $mt = self::mmToPt((float) ($slot['mt'] ?? 15));
        $mr = self::mmToPt((float) ($slot['mr'] ?? 15));
        $mb = self::mmToPt((float) ($slot['mb'] ?? 15));
        $ml = self::mmToPt((float) ($slot['ml'] ?? 15));

        $fontSize    = (float) ($slot['fontSize'] ?? 10);
        $lineHeight  = max(1.0, (float) ($slot['lineHeight'] ?? 1.35));
        $linePt      = $fontSize * $lineHeight;
        $pxToPt      = 72.0 / 96.0;
        $zone        = strtolower((string) ($slot['zone'] ?? 'header'));
        $footerCols  = (int) ($slot['footerColumns'] ?? 0);

        if ($zone === 'footer' && $footerCols > 0) {
            $footerCols   = max(1, $footerCols);
            $gridCol      = min(max(0, (int) ($slot['gridColumn'] ?? 0)), $footerCols - 1);
            $gridColSpan  = max(1, min((int) ($slot['gridColumnSpan'] ?? 1), $footerCols - $gridCol));
            $contentW     = max(1.0, $pageW - $ml - $mr);
            $colW         = $contentW / $footerCols;
            $cellX0       = $ml + ($gridCol * $colW);
            $cellW        = $colW * $gridColSpan;
            $rowOffset    = self::resolveFooterRowOffsetPt($slot, $linePt);
            $yTop         = self::resolveFooterBaselineY($slot, $pageH, $fontSize, $rowOffset);

            $align      = strtolower((string) ($slot['align'] ?? 'left'));
            $cellPadHPt = max(0.0, (float) ($slot['cellPadHPx'] ?? 0)) * $pxToPt;
            $x          = self::computeFooterGridX($slot, $align, $cellX0, $cellW, $cellPadHPt, $textWidth, $pdf);

            $mrPt         = self::mmToPt((float) ($slot['mr'] ?? 15));
            $contentRight = $pageW - $mrPt - 2.0;
            $cellRight    = $cellX0 + $cellW - $cellPadHPt;
            $maxRight     = min($cellRight, $contentRight);
            $safeWidth    = $textWidth * 1.12;
            if ($x + $safeWidth > $maxRight) {
                $x = max($cellX0 + $cellPadHPt, $maxRight - $safeWidth);
            }
        } else {
            $align = strtolower((string) ($slot['align'] ?? 'left'));
            $x     = match ($align) {
                'right'  => max($ml, $pageW - $mr - $textWidth),
                'center' => max($ml, ($pageW - $textWidth) / 2),
                default  => $ml,
            };

            if ($zone === 'footer') {
                $footerReservePt = max(0.0, self::mmToPt((float) ($slot['footerReserveMm'] ?? 0)));
                $bandPt          = $footerReservePt > 0 ? $footerReservePt : ($fontSize * 2.4);
                $yTop            = $pageH - $mb - ($bandPt * 0.42) - ($fontSize * 0.15);
                $yTop            = max($mt + $fontSize, min($pageH - $mb - ($fontSize * 0.5), $yTop));
            } else {
                $yTop = $mt + ($fontSize * 0.85);
            }

            if (! empty($slot['inlineAfterLabel']) && $align === 'left') {
                $x += self::measureInlineLabelOffsetPt($slot, $pdf);
            }
        }

        return [$x, $yTop];
    }

    /**
     * Posición X del número dentro de la celda del pie (respeta label inline de la plantilla).
     *
     * @param array<string, mixed> $slot
     */
    private static function computeFooterGridX(
        array $slot,
        string $align,
        float $cellX0,
        float $cellW,
        float $cellPadHPt,
        float $textWidth,
        Fpdi $pdf,
    ): float {
        if (! empty($slot['inlineAfterLabel'])) {
            $labelW     = self::measureInlineLabelOffsetPt($slot, $pdf);
            $labelOnlyW = self::measureInlineLabelOnlyPt($slot, $pdf);

            return match ($align) {
                // Label alineado a la derecha: número justo después de «Página » (ancho real del label en HTML).
                'right' => $cellX0 + $cellW - $cellPadHPt - $labelOnlyW + $labelW,
                'center' => $cellX0 + max($cellPadHPt, ($cellW - $labelW - $textWidth) / 2) + $labelW,
                default => $cellX0 + $cellPadHPt + $labelW,
            };
        }

        return match ($align) {
            'right'  => $cellX0 + max($cellPadHPt, $cellW - $cellPadHPt - $textWidth),
            'center' => $cellX0 + max($cellPadHPt, ($cellW - $textWidth) / 2),
            default  => $cellX0 + $cellPadHPt,
        };
    }

    private static function mmToPt(float $mm): float
    {
        return $mm * 72.0 / 25.4;
    }

    /**
     * @param array<string, mixed> $slot
     */
    private static function measureInlineLabelOnlyPt(array $slot, Fpdi $pdf): float
    {
        $label = rtrim((string) ($slot['inlineLabelText'] ?? ''));
        if ($label === '') {
            return 0.0;
        }

        $labelSize  = max(7.0, min(20.0, (float) ($slot['inlineLabelFontSize'] ?? ($slot['fontSize'] ?? 10))));
        $labelStyle = self::tcpdfFontStyle(
            (string) ($slot['inlineLabelFontWeight'] ?? 'normal'),
            (string) ($slot['inlineLabelFontStyle'] ?? 'normal'),
        );

        $prevFamily = $pdf->getFontFamily();
        $prevStyle  = $pdf->getFontStyle();
        $prevSize   = $pdf->getFontSizePt();

        $pdf->SetFont('helvetica', $labelStyle, $labelSize);
        $width = $pdf->GetStringWidth($label);
        $pdf->SetFont((string) $prevFamily, (string) $prevStyle, (float) $prevSize);

        // Helvetica (TCPDF) ≈ más estrecha que DejaVu Sans del HTML del reporte.
        return $width * 1.18;
    }

    /**
     * @param array<string, mixed> $slot
     */
    private static function measureInlineLabelOffsetPt(array $slot, Fpdi $pdf): float
    {
        $label = (string) ($slot['inlineLabelText'] ?? '');
        if ($label === '') {
            return 0.0;
        }

        $labelSize  = max(7.0, min(20.0, (float) ($slot['inlineLabelFontSize'] ?? ($slot['fontSize'] ?? 10))));
        $labelStyle = self::tcpdfFontStyle(
            (string) ($slot['inlineLabelFontWeight'] ?? 'normal'),
            (string) ($slot['inlineLabelFontStyle'] ?? 'normal'),
        );

        $prevFamily = $pdf->getFontFamily();
        $prevStyle  = $pdf->getFontStyle();
        $prevSize   = $pdf->getFontSizePt();

        $pdf->SetFont('helvetica', $labelStyle, $labelSize);
        $width = $pdf->GetStringWidth($label);
        $pdf->SetFont((string) $prevFamily, (string) $prevStyle, (float) $prevSize);

        return $width;
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function hexToRgb(string $color): array
    {
        $color = ltrim(trim($color), '#');
        if (strlen($color) !== 6 || ! ctype_xdigit($color)) {
            return [51, 51, 51];
        }

        return [
            (int) hexdec(substr($color, 0, 2)),
            (int) hexdec(substr($color, 2, 2)),
            (int) hexdec(substr($color, 4, 2)),
        ];
    }

    private static function tcpdfFontStyle(string $fontWeight, string $fontStyle): string
    {
        $style = '';
        $weight = strtolower(trim($fontWeight));
        $slant  = strtolower(trim($fontStyle));

        if (in_array($weight, ['bold', '600', '700', '800'], true)) {
            $style .= 'B';
        }
        if (in_array($slant, ['italic', 'oblique'], true)) {
            $style .= 'I';
        }

        return $style;
    }

    public static function disableTcpdfLink(Fpdi $pdf): void
    {
        if (! property_exists($pdf, 'tcpdflink')) {
            return;
        }

        $prop = new \ReflectionProperty($pdf, 'tcpdflink');
        $prop->setAccessible(true);
        $prop->setValue($pdf, false);
    }
}
