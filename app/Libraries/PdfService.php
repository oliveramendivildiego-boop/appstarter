<?php

namespace App\Libraries;

use App\Libraries\Pdf\PdfOptions;
use App\Libraries\Pdf\PdfRendererFactory;

/**
 * Fachada de generación PDF. Delega a Chromium headless.
 */
class PdfService
{
    /**
     * @param array<string, mixed>|null $pageSize
     */
    public function generate(string $html, string $filename = 'resultados.pdf', ?array $pageSize = null): string
    {
        unset($filename);

        return PdfRendererFactory::renderWithFallback(
            $html,
            PdfOptions::fromLegacyPageSize($pageSize),
        );
    }

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
