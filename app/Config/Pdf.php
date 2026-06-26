<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Pdf extends BaseConfig
{
    /**
     * Motor de renderizado: chromium | dompdf
     * Variable de entorno: PDF_RENDERER
     */
    public string $renderer = 'dompdf';

    /**
     * Si Chromium falla, usar Dompdf automáticamente.
     */
    public bool $fallbackToDompdf = true;

    /**
     * Ruta al ejecutable de Chrome/Chromium.
     * Variable de entorno: CHROME_EXECUTABLE_PATH
     * Vacío = autodetección (Windows / Linux).
     */
    public string $executablePath = '';

    /**
     * Timeout en segundos para el proceso Chromium.
     */
    public int $timeoutSeconds = 120;

    /**
     * Tamaño de papel por defecto si no viene del layout: letter | a4 | legal
     */
    public string $paperSize = 'letter';

    /**
     * portrait | landscape
     */
    public string $orientation = 'portrait';

    /**
     * Imprimir fondos y colores (printBackground).
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

    /**
     * Plantillas HTML/PDF de cabecera y pie para Chromium (vacío = desactivado).
     */
    public string $headerTemplate = '';

    public string $footerTemplate = '';

    /**
     * Directorio temporal para HTML/PDF de Chromium (vacío = writable/cache/chromium_pdf).
     */
    public string $tempDir = '';

    public function __construct()
    {
        parent::__construct();

        $envRenderer = getenv('PDF_RENDERER');
        if (is_string($envRenderer) && $envRenderer !== '') {
            $this->renderer = strtolower(trim($envRenderer));
        }

        $envChrome = getenv('CHROME_EXECUTABLE_PATH');
        if (is_string($envChrome) && $envChrome !== '') {
            $this->executablePath = trim($envChrome);
        }

        $envTimeout = getenv('PDF_CHROMIUM_TIMEOUT');
        if (is_string($envTimeout) && is_numeric($envTimeout)) {
            $this->timeoutSeconds = max(10, (int) $envTimeout);
        }
    }
}
