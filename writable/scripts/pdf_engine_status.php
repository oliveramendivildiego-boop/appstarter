<?php

/**
 * Muestra el motor PDF configurado. Uso: php writable/scripts/pdf_engine_status.php
 */
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$pathsConfig = dirname(__DIR__, 2) . '/app/Config/Paths.php';
require $pathsConfig;

$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/') . '/Boot.php';

CodeIgniter\Boot::bootSpark($paths);

$pdf = config('Pdf');

echo "Motor PDF (config): " . ($pdf->renderer ?? 'mpdf') . PHP_EOL;
echo "Recomendado para la plantilla actual: mpdf" . PHP_EOL;
echo "Caché HTML habilitada: " . (($pdf->htmlCacheEnabled ?? true) ? 'sí' : 'no') . PHP_EOL;
echo "env('PDF_RENDERER'): " . var_export(env('PDF_RENDERER'), true) . PHP_EOL;
