<?php

namespace App\Services;

use Picqer\Barcode\BarcodeGeneratorPNG;

/**
 * Genera imágenes PNG de código de barras CODE128 para impresión de sobres (sin JavaScript).
 */
class EnvelopeBarcodeImageService
{
    private const MM_TO_PX = 96 / 25.4;

    /**
     * @return string HTML <img> + texto del valor, o cadena vacía si falla
     */
    public function renderPrintHtml(string $orden, int $widthMm, int $heightMm, int $sizePct): string
    {
        $orden = trim($orden);
        if ($orden === '') {
            return '';
        }

        if (! extension_loaded('gd')) {
            return $this->renderTextFallback($orden);
        }

        $sizePct = max(30, min(250, $sizePct));
        $scale   = $sizePct / 100.0;
        $wMm     = max(25.0, min(120.0, $widthMm * $scale));
        $hMm     = max(8.0, min(40.0, $heightMm * $scale));

        try {
            $generator = new BarcodeGeneratorPNG();
            $barHeightPx = max(24, (int) round($hMm * self::MM_TO_PX * 2.2));
            $png         = $generator->getBarcode($orden, $generator::TYPE_CODE_128, 2, $barHeightPx);
        } catch (\Throwable $e) {
            log_message('warning', 'Envelope barcode PNG: {msg}', ['msg' => $e->getMessage()]);

            return $this->renderTextFallback($orden);
        }

        $img = @imagecreatefromstring($png);
        if ($img === false) {
            return $this->renderTextFallback($orden);
        }

        $srcW    = imagesx($img);
        $srcH    = imagesy($img);
        $targetW = max(120, (int) round($wMm * self::MM_TO_PX * 2));
        $targetH = max(24, (int) round($targetW * ($srcH / max(1, $srcW))));

        $scaled = imagescale($img, $targetW, $targetH, IMG_BILINEAR_FIXED);
        imagedestroy($img);
        if ($scaled === false) {
            return $this->renderTextFallback($orden);
        }

        ob_start();
        imagepng($scaled);
        $pngOut = (string) ob_get_clean();
        imagedestroy($scaled);

        if ($pngOut === '') {
            return $this->renderTextFallback($orden);
        }

        $uri = 'data:image/png;base64,' . base64_encode($pngOut);
        $wMmStr = rtrim(rtrim(sprintf('%.2f', $wMm), '0'), '.');

        $html = '<img class="envelope-barcode-raster orden-barcode-label-img" src="' . esc($uri, 'attr') . '"';
        $html .= ' alt="' . esc($orden, 'attr') . '"';
        $html .= ' style="display:block;width:' . esc($wMmStr, 'attr') . 'mm;max-width:100%;height:auto;margin:0 auto;">';
        $html .= '<div class="envelope-barcode-value-text" style="font-family:Arial,Helvetica,sans-serif;font-size:9pt;font-weight:600;letter-spacing:0.05em;margin-top:1mm;text-align:center;">'
            . esc($orden) . '</div>';

        return $html;
    }

    private function renderTextFallback(string $orden): string
    {
        return '<div class="envelope-barcode-fallback" style="font-family:Arial,Helvetica,sans-serif;font-size:11pt;font-weight:600;letter-spacing:0.05em;padding:2mm 0;text-align:center;">'
            . esc($orden) . '</div>';
    }
}
