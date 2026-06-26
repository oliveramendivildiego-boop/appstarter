<?php

namespace App\Libraries\Pdf;

use App\Services\ReportPdfLayoutService;
use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * Paciente / No. Orden en pie Chromium: HTML reserva espacio (invisible); texto visible desde hoja 2.
 */
class ChromiumPdfOrderSheetStamper
{
    private const MARKER = 'pdf-order-sheet-header';

    public static function stampFromPageTwo(string $pdfBinary, string $sourceHtml, string $tempDir): string
    {
        if (! str_contains($sourceHtml, 'data-order-sheet-from-page-two="1"')) {
            return $pdfBinary;
        }

        $slot = self::extractSlot($sourceHtml);
        if ($slot === null) {
            return $pdfBinary;
        }

        $stamped = self::stampBinary($pdfBinary, $slot, $tempDir);

        return is_string($stamped) && $stamped !== '' ? $stamped : $pdfBinary;
    }

    /**
     * @return array{patient: string, order: string, ml: float, mr: float, mb: float, footerReserveMm: float, gapMm: float, footerBg: string, rowHeightMm: float}|null
     */
    public static function extractSlot(string $html): ?array
    {
        $pattern = '/<!--\s*' . preg_quote(self::MARKER, '/') . ':([A-Za-z0-9+\/=_-]+)\s*-->/';
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
     * @param array{patient: string, order: string, ml: float, mr: float, mb: float, footerReserveMm: float, gapMm: float, footerBg: string, rowHeightMm: float} $slot
     */
    private static function stampBinary(string $pdfBinary, array $slot, string $tempDir): ?string
    {
        $inputPath  = $tempDir . DIRECTORY_SEPARATOR . 'osh_in_' . bin2hex(random_bytes(6)) . '.pdf';
        $outputPath = $tempDir . DIRECTORY_SEPARATOR . 'osh_out_' . bin2hex(random_bytes(6)) . '.pdf';

        try {
            if (file_put_contents($inputPath, $pdfBinary) === false) {
                return null;
            }

            $pdf = new Fpdi('P', 'pt');
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->setAutoPageBreak(false);
            $pdf->setMargins(0, 0, 0);
            ChromiumPdfPaginationPhpStamper::disableTcpdfLink($pdf);

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
                $orientation = ($pageW > $pageH) ? 'L' : 'P';

                $pdf->AddPage($orientation, [$pageW, $pageH]);
                $pdf->useTemplate($tplId);

                if ($pageNumber >= 2) {
                    self::paintOrderSheetRow($pdf, $slot, $pageW, $pageH);
                }
            }

            $pdf->Output($outputPath, 'F');

            if (! is_file($outputPath)) {
                return null;
            }

            $stamped = file_get_contents($outputPath);

            return is_string($stamped) && $stamped !== '' ? $stamped : null;
        } catch (\Throwable $e) {
            log_message('warning', 'ChromiumPdfOrderSheetStamper: {msg}', ['msg' => $e->getMessage()]);

            return null;
        } finally {
            @unlink($inputPath);
            @unlink($outputPath);
        }
    }

    /**
     * @param array{patient: string, order: string, ml: float, mr: float, mb: float, footerReserveMm: float, gapMm: float, footerBg: string, rowHeightMm: float} $slot
     */
    public static function paintOrderSheetRow(Fpdi $pdf, array $slot, float $pageW, float $pageH): void
    {
        $fontSize = 9.0;
        $y        = self::resolveOrderSheetBaselineY($slot, $pageH, $fontSize);

        $pdf->SetFont('dejavusans', 'B', $fontSize);
        $pdf->SetTextColor(51, 51, 51);

        $patient = (string) $slot['patient'];
        if ($patient !== '') {
            $pdf->Text(self::mmToPt((float) $slot['ml']), $y, $patient);
        }

        $order = (string) $slot['order'];
        if ($order !== '') {
            $orderW = $pdf->GetStringWidth($order);
            $x      = max(self::mmToPt((float) $slot['ml']), $pageW - self::mmToPt((float) $slot['mr']) - $orderW);
            $pdf->Text($x, $y, $order);
        }
    }

    /**
     * @param array{patient: string, order: string, ml: float, mr: float, mb: float, footerReserveMm: float, gapMm: float, footerBg: string, rowHeightMm: float} $slot
     */
    private static function resolveOrderSheetBaselineY(array $slot, float $pageH, float $fontSize): float
    {
        $pxToPt    = 72.0 / 96.0;
        $mbPt      = self::mmToPt((float) $slot['mb']);
        $reservePt = self::mmToPt((float) ($slot['footerReserveMm']));
        $footerTop = $pageH - $mbPt - $reservePt;
        $padTopPt  = (float) ReportPdfLayoutService::FOOTER_BLOCK_PAD_TOP_PX * $pxToPt;
        $rowPt     = self::mmToPt((float) $slot['rowHeightMm']);

        return $footerTop + $padTopPt + ($rowPt * 0.55) + ($fontSize * 0.35);
    }

    private static function mmToPt(float $mm): float
    {
        return $mm * (72.0 / 25.4);
    }
}
