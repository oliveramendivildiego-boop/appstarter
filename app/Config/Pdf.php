<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Pdf extends BaseConfig
{
    /**
     * Motor de renderizado: mpdf (recomendado) | dompdf (legacy)
     * Variable de entorno: PDF_RENDERER
     */
    public string $renderer = 'mpdf';

    /**
     * Caché de HTML entre prepareReportData y el motor PDF (writable/cache/report_pdf_html).
     */
    public bool $htmlCacheEnabled = true;

    /**
     * Directorio temporal mPDF (vacío = writable/cache/mpdf).
     */
    public string $mpdfTempDir = '';

    /**
     * Tamaño de papel por defecto si no viene del layout: letter | a4 | legal
     */
    public string $paperSize = 'letter';

    /**
     * portrait | landscape
     */
    public string $orientation = 'portrait';

    /**
     * Imprimir fondos y colores.
     */
    public bool $printBackground = true;

    /**
     * Escala de renderizado (1.0 = 100%).
     */
    public float $scale = 1.0;

    /**
     * Márgenes opcionales en mm (null = usar @page del CSS del reporte).
     *
     * @var array{top: ?float, right: ?float, bottom: ?float, left: ?float}
     */
    public array $marginsMm = [
        'top'    => null,
        'right'  => null,
        'bottom' => null,
        'left'   => null,
    ];

    public function __construct()
    {
        parent::__construct();

        $envRenderer = env('PDF_RENDERER');
        if (is_string($envRenderer) && trim($envRenderer) !== '') {
            $this->renderer = strtolower(trim($envRenderer));
        }

        if (! in_array($this->renderer, ['dompdf', 'mpdf'], true)) {
            log_message(
                'warning',
                'PDF_RENDERER={renderer} no soportado; usando mpdf.',
                ['renderer' => $this->renderer],
            );
            $this->renderer = 'mpdf';
        }
    }
}
