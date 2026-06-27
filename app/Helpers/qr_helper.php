<?php

/**
 * Helper para generar códigos QR como data URI (para PDF, email, etc.)
 */
if (!function_exists('qr_trim_png_to_black_bounds')) {
    /**
     * Recorta bordes transparentes/blancos externos hasta el bounding box de píxeles oscuros.
     */
    function qr_trim_png_to_black_bounds(string $pngBinary): string
    {
        if (!function_exists('imagecreatefromstring')) {
            return $pngBinary;
        }
        $img = @imagecreatefromstring($pngBinary);
        if ($img === false) {
            return $pngBinary;
        }

        $w = imagesx($img);
        $h = imagesy($img);
        $minX = $w;
        $minY = $h;
        $maxX = 0;
        $maxY = 0;

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $c = imagecolorat($img, $x, $y);
                $a = ($c >> 24) & 0x7F;
                if ($a >= 120) {
                    continue;
                }
                $r = ($c >> 16) & 0xFF;
                $g = ($c >> 8) & 0xFF;
                $b = $c & 0xFF;
                if ($r >= 128 || $g >= 128 || $b >= 128) {
                    continue;
                }
                $minX = min($minX, $x);
                $maxX = max($maxX, $x);
                $minY = min($minY, $y);
                $maxY = max($maxY, $y);
            }
        }

        if ($maxX < $minX || $maxY < $minY) {
            imagedestroy($img);

            return $pngBinary;
        }

        $cropW = $maxX - $minX + 1;
        $cropH = $maxY - $minY + 1;
        if ($cropW === $w && $cropH === $h) {
            imagedestroy($img);

            return $pngBinary;
        }

        $cropped = imagecreatetruecolor($cropW, $cropH);
        imagesavealpha($cropped, true);
        $trans = imagecolorallocatealpha($cropped, 0, 0, 0, 127);
        imagefill($cropped, 0, 0, $trans);
        imagecopy($cropped, $img, 0, 0, $minX, $minY, $cropW, $cropH);
        imagedestroy($img);

        ob_start();
        imagepng($cropped);
        $out = ob_get_clean() ?: '';
        imagedestroy($cropped);

        return $out !== '' ? $out : $pngBinary;
    }
}

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
                margin: 0,
                foregroundColor: new \Endroid\QrCode\Color\Color(0, 0, 0, 0),
                backgroundColor: new \Endroid\QrCode\Color\Color(255, 255, 255, 127),
            );
            $result = $builder->build();
            $mime   = $result->getMimeType();
            $raw    = qr_trim_png_to_black_bounds($result->getString());

            return 'data:' . $mime . ';base64,' . base64_encode($raw);
        } catch (\Throwable $e) {
            return '';
        }
    }
}
