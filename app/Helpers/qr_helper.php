<?php

/**
 * Helper para generar códigos QR como data URI (para PDF, email, etc.)
 */
if (!function_exists('qr_base64')) {
    /**
     * Genera un código QR y lo devuelve como data URI base64
     *
     * @param string $data Contenido a codificar (URL, texto, etc.)
     * @param int    $size Tamaño en píxeles (50-500)
     * @return string Data URI (data:image/png;base64,...) o string vacío si falla
     */
    function qr_base64(string $data, int $size = 120): string
    {
        if (trim($data) === '') {
            return '';
        }
        $size = max(50, min(500, $size));
        try {
            $builder = new \Endroid\QrCode\Builder\Builder(
                writer: new \Endroid\QrCode\Writer\PngWriter(),
                data: $data,
                encoding: new \Endroid\QrCode\Encoding\Encoding('UTF-8'),
                errorCorrectionLevel: \Endroid\QrCode\ErrorCorrectionLevel::Medium,
                size: $size,
                margin: 10
            );
            $result = $builder->build();
            $mime   = $result->getMimeType();
            $raw    = $result->getString();
            return 'data:' . $mime . ';base64,' . base64_encode($raw);
        } catch (\Throwable $e) {
            return '';
        }
    }
}
