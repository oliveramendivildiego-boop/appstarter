<?php

namespace App\Libraries;

/**
 * Envuelve HTML de reportes en documento listo para Dompdf.
 */
final class ReportPdfDocument
{
    /**
     * @param array<string, mixed> $viewData Datos pasados a la vista de contenido
     */
    public static function download(string $filename, string $title, string $subtitle, string $contentView, array $viewData): void
    {
        helper('layout');
        $layout = layout_config();
        $html = view('reports/pdf/document_shell', [
            'pdf_title'     => $title,
            'pdf_subtitle'  => $subtitle,
            'pdf_company'   => (string) ($layout['company'] ?? 'Laboratorio'),
            'pdf_generated' => \App\Services\RegisterService::formatNowForReportShort(),
            'pdf_content'   => view($contentView, $viewData),
        ]);
        (new PdfService())->download($html, $filename);
    }
}
