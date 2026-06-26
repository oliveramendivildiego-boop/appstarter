<?php

namespace App\Libraries\Pdf;

/**
 * Fuentes DejaVu embebidas para Chromium (@font-face) y TCPDF (dejavusans).
 * Copiadas en public/assets/fonts/dejavu (antes se leían desde vendor/dompdf).
 */
class PdfDejaVuFonts
{
    public static function directory(): string
    {
        $dir = realpath(ROOTPATH . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR . 'dejavu');

        return $dir !== false ? $dir : '';
    }

    public static function filePath(string $fileName): ?string
    {
        $dir = self::directory();
        if ($dir === '') {
            return null;
        }

        $real = realpath($dir . DIRECTORY_SEPARATOR . $fileName);

        return ($real !== false && is_file($real)) ? $real : null;
    }

    public static function fileUrl(string $fileName): ?string
    {
        $path = self::filePath($fileName);
        if ($path === null) {
            return null;
        }

        $normalized = str_replace('\\', '/', $path);

        return PHP_OS_FAMILY === 'Windows' ? 'file:///' . $normalized : 'file://' . $normalized;
    }
}
