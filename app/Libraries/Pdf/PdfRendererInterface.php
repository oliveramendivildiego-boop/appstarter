<?php

namespace App\Libraries\Pdf;

interface PdfRendererInterface
{
    /**
     * Genera el binario PDF a partir de HTML completo.
     */
    public function renderHtml(string $html, PdfOptions $options): string;

    /**
     * Identificador del motor (chromium, dompdf, …).
     */
    public function engineName(): string;
}
