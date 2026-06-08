<?php

namespace App\Libraries;

use Dompdf\Dompdf;
use Dompdf\FontMetrics;
use Dompdf\Options;

/**
 * Servicio para generar PDF de resultados de laboratorio
 */
class PdfService
{
    private const TOTAL_PAGES_TOKEN = '__PDF_TOTAL_PAGES__';

    private const ORDER_SHEET_HEADER_MARKER = 'pdf-order-sheet-header-dompdf';

    protected function makeDompdf(Options $options): Dompdf
    {
        $dompdf = new Dompdf($options);
        $dompdf->setPaper('letter', 'portrait');

        return $dompdf;
    }

    /**
     * @param array<string, mixed>|null $orderSheetHeaderData
     */
    protected function renderHtmlToDompdf(Dompdf $dompdf, string $html, ?array $orderSheetHeaderData = null): void
    {
        if ($orderSheetHeaderData !== null) {
            $this->registerOrderSheetHeaderCallback($dompdf, $orderSheetHeaderData);
        }
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
     * @param array<string, mixed> $data
     */
    protected function registerOrderSheetHeaderCallback(Dompdf $dompdf, array $data): void
    {
        $patientLine = trim((string) ($data['patient'] ?? ''));
        $orderLine   = trim((string) ($data['order'] ?? ''));
        if ($patientLine === '' && $orderLine === '') {
            return;
        }

        $marginBottomMm  = (float) ($data['margin_bottom_mm'] ?? 15);
        $marginLeftMm    = (float) ($data['margin_left_mm'] ?? 15);
        $marginRightMm   = (float) ($data['margin_right_mm'] ?? 15);
        $footerReserveMm = ! empty($data['footer_enabled'])
            ? (float) ($data['footer_reserve_mm'] ?? 22)
            : 0.0;
        $gapAboveFooterMm = (float) ($data['gap_above_footer_mm'] ?? 1.5);
        $mmToPt           = 72 / 25.4;

        $dompdf->setCallbacks([
            [
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
                    if ($pageNumber <= 1) {
                        return;
                    }

                    try {
                        $font = $fontMetrics->getFont('DejaVu Sans', 'bold');
                    } catch (\Throwable $e) {
                        $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
                    }

                    $size  = 9.0;
                    $color = [0.2, 0.2, 0.2];
                    $offsetFromBottomMm = $marginBottomMm + $footerReserveMm + $gapAboveFooterMm;
                    $y                  = $pdf->get_height() - ($offsetFromBottomMm * $mmToPt) - ($size * 0.85);
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
            ],
        ]);
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

        // Dompdf no garantiza counter(pages) correcto dentro del flujo (puede dar 0 en PDFs de 1 página).
        // Para el elemento "total de páginas" hacemos doble render solo si existe el token.
        if (strpos($html, self::TOTAL_PAGES_TOKEN) !== false) {
            $probe = $this->makeDompdf($options);
            $this->renderHtmlToDompdf($probe, $html, null);
            $pageCount = (int) $probe->getCanvas()->get_page_count();
            if ($pageCount < 1) {
                $pageCount = 1;
            }
            $html = str_replace(self::TOTAL_PAGES_TOKEN, (string) $pageCount, $html);
        }

        $dompdf = $this->makeDompdf($options);
        $this->renderHtmlToDompdf($dompdf, $html, $orderSheetHeaderData);

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
