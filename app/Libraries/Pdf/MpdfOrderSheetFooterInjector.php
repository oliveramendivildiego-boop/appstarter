<?php

namespace App\Libraries\Pdf;

/**
 * Dompdf pinta Paciente/Orden en canvas (hojas 2+). mPDF lo replica en SetHTMLFooter.
 */
class MpdfOrderSheetFooterInjector
{
    /**
     * @param array<string, mixed>|null $orderSheetSlot
     */
    public static function shouldPrependOrderSheetBand(?array $orderSheetSlot, string $footerInner): bool
    {
        if ($footerInner === '' || $orderSheetSlot === null) {
            return false;
        }

        $patient = trim((string) ($orderSheetSlot['patient'] ?? ''));
        $order   = trim((string) ($orderSheetSlot['order'] ?? ''));

        return $patient !== '' || $order !== '';
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function extractSlot(string $html): ?array
    {
        if (! preg_match('/<!--\s*pdf-order-sheet-header:([A-Za-z0-9+\/=_-]+)\s*-->/', $html, $matches)) {
            return null;
        }

        $json = base64_decode($matches[1], true);
        if ($json === false) {
            return null;
        }

        $slot = json_decode($json, true);

        return is_array($slot) ? $slot : null;
    }

    public static function stripMarker(string $html): string
    {
        return preg_replace('/<!--\s*pdf-order-sheet-header:[A-Za-z0-9+\/=_-]+\s*-->/', '', $html) ?? $html;
    }

    public static function inject(string $html): string
    {
        return self::stripMarker($html);
    }

    /**
     * Bandera Paciente / No. Orden encima de la línea verde del pie (solo hojas 2+).
     *
     * @param array<string, mixed> $slot
     */
    public static function prependOrderSheetRowToFooter(string $footerHtml, array $slot, int $nColumns = 5): string
    {
        if (str_contains($footerHtml, 'pdf-order-sheet-table-row') || str_contains($footerHtml, 'mpdf-order-sheet-above')) {
            return $footerHtml;
        }

        $patient = trim((string) ($slot['patient'] ?? ''));
        $order   = trim((string) ($slot['order'] ?? ''));
        if ($patient === '' && $order === '') {
            return $footerHtml;
        }

        $nColumns = max(1, $nColumns);
        $bandCols = $nColumns === 3 ? 3 : max(1, $nColumns);

        $rowHtml = view('registers/partials/report_order_sheet_header_table_row', [
            'patient_line'           => $patient,
            'order_line'             => $order,
            'margin_left_mm'         => (float) ($slot['ml'] ?? 15),
            'margin_right_mm'        => (float) ($slot['mr'] ?? 15),
            'n_columns'              => $bandCols,
            'line_height'            => 1.35,
            'dompdf_from_page_two'   => false,
            'mpdf_from_page_two'     => false,
            'mpdf_footer_row'        => true,
            'mpdf_footer_above_table' => true,
        ]);

        if (! is_string($rowHtml) || trim($rowHtml) === '') {
            return $footerHtml;
        }

        $bandHtml = '<table class="mpdf-order-sheet-above" width="100%" cellpadding="0" cellspacing="0" '
            . 'style="table-layout:fixed;width:100%;border:0;border-collapse:collapse;margin:0 0 2px 0;">'
            . '<colgroup><col style="width:40%"><col style="width:20%"><col style="width:40%"></colgroup>'
            . $rowHtml
            . '</table>';

        if (preg_match('/<table\b[^>]*\bmpdf-ft-table\b/i', $footerHtml, $tableMatch, PREG_OFFSET_CAPTURE)) {
            $insertAt = $tableMatch[0][1];

            return substr($footerHtml, 0, $insertAt) . $bandHtml . substr($footerHtml, $insertAt);
        }

        if (preg_match('/<div\b[^>]*\bmpdf-ft-root\b/i', $footerHtml, $rootMatch, PREG_OFFSET_CAPTURE)) {
            $gt = strpos($footerHtml, '>', $rootMatch[0][1]);
            if ($gt !== false) {
                $insertAt = $gt + 1;

                return substr($footerHtml, 0, $insertAt) . $bandHtml . substr($footerHtml, $insertAt);
            }
        }

        return $bandHtml . $footerHtml;
    }

    public static function countFooterColumns(string $footerHtml): int
    {
        if (preg_match('/<table\b[^>]*\bmpdf-ft-table\b[^>]*\bdata-pdf-cols="(\d+)"/i', $footerHtml, $m)) {
            return max(1, (int) $m[1]);
        }

        if (preg_match('/<table\b[^>]*\bdata-pdf-cols="(\d+)"/i', $footerHtml, $m)) {
            return max(1, (int) $m[1]);
        }

        if (preg_match('/data-pdf-lh="1"/', $footerHtml)) {
            if (preg_match('/<table\b[^>]*\bmpdf-ft-table\b[\s\S]*?<colgroup>[\s\S]*?<\/colgroup>/i', $footerHtml, $cg)) {
                $colCount = preg_match_all('/<col\b/i', $cg[0], $cols);
                if ($colCount > 0) {
                    return $colCount;
                }
            }
        }

        return 5;
    }
}

