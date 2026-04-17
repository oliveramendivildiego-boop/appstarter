<?php

namespace App\Libraries;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Servicio para generar PDF de resultados de laboratorio
 */
class PdfService
{
    private const TOTAL_PAGES_TOKEN = '__PDF_TOTAL_PAGES__';

    protected function makeDompdf(Options $options): Dompdf
    {
        $dompdf = new Dompdf($options);
        $dompdf->setPaper('letter', 'portrait');

        return $dompdf;
    }

    protected function renderHtmlToDompdf(Dompdf $dompdf, string $html): void
    {
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();
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

        // Dompdf no garantiza counter(pages) correcto dentro del flujo (puede dar 0 en PDFs de 1 página).
        // Para el elemento "total de páginas" hacemos doble render solo si existe el token.
        if (strpos($html, self::TOTAL_PAGES_TOKEN) !== false) {
            $probe = $this->makeDompdf($options);
            $this->renderHtmlToDompdf($probe, $html);
            $pageCount = (int) $probe->getCanvas()->get_page_count();
            if ($pageCount < 1) {
                $pageCount = 1;
            }
            $html = str_replace(self::TOTAL_PAGES_TOKEN, (string) $pageCount, $html);
        }

        $dompdf = $this->makeDompdf($options);
        $this->renderHtmlToDompdf($dompdf, $html);

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
