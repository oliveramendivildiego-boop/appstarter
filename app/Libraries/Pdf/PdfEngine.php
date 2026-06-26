<?php

namespace App\Libraries\Pdf;

/**
 * Motor PDF activo (mPDF por defecto; Dompdf solo legacy).
 */
final class PdfEngine
{
    public static function renderer(): string
    {
        return strtolower(trim((string) (config('Pdf')->renderer ?? 'mpdf')));
    }

    public static function isMpdf(): bool
    {
        return self::renderer() === 'mpdf';
    }

    public static function isDompdf(): bool
    {
        return self::renderer() === 'dompdf';
    }

    /** Variante HTML del reporte descargable (registers/report_pdf). */
    public static function isPdfDownloadVariant(string $variant): bool
    {
        return $variant === 'pdf';
    }
}
