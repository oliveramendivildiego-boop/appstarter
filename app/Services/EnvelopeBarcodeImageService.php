<?php

namespace App\Services;

use Picqer\Barcode\BarcodeGeneratorPNG;

/**
 * Genera código de barras CODE128 para sobres (sin JavaScript).
 */
class EnvelopeBarcodeImageService
{
    private const MM_TO_PX = 96 / 25.4;

    /**
     * @return string HTML <img> + texto del valor
     */
    public function renderPrintHtml(string $orden, int $widthMm, int $heightMm, int $sizePct, string $textAlign = 'left'): string
    {
        $orden = trim($orden);
        if ($orden === '') {
            return '';
        }

        $textAlign = in_array($textAlign, ['left', 'center', 'right'], true) ? $textAlign : 'left';

        if (! extension_loaded('gd')) {
            return $this->renderTextFallback($orden, $textAlign);
        }

        $sizePct = max(30, min(250, $sizePct));
        $scale   = $sizePct / 100.0;
        $wMm     = max(25.0, min(120.0, $widthMm * $scale));
        $hMm     = max(8.0, min(40.0, $heightMm * $scale));

        $barHeightPx = max(28, (int) round($hMm * self::MM_TO_PX));
        $widthFactor = 2;

        try {
            $generator = new BarcodeGeneratorPNG();
            $png       = $generator->getBarcode(
                $orden,
                $generator::TYPE_CODE_128,
                $widthFactor,
                $barHeightPx
            );
        } catch (\Throwable $e) {
            log_message('warning', 'Envelope barcode PNG: {msg}', ['msg' => $e->getMessage()]);

            return $this->renderTextFallback($orden, $textAlign);
        }

        $img = @imagecreatefromstring($png);
        if ($img === false) {
            return $this->renderTextFallback($orden, $textAlign);
        }

        $srcW = imagesx($img);
        $srcH = imagesy($img);
        if ($srcW < 1 || $srcH < 1) {
            imagedestroy($img);

            return $this->renderTextFallback($orden, $textAlign);
        }

        $targetW = max(80, (int) round($wMm * self::MM_TO_PX));
        if ($srcW !== $targetW) {
            $targetH = max(20, (int) round($srcH * ($targetW / $srcW)));
            $scaled  = imagescale($img, $targetW, $targetH, IMG_NEAREST_NEIGHBOUR);
            imagedestroy($img);
            if ($scaled === false) {
                return $this->renderTextFallback($orden, $textAlign);
            }
            $img = $scaled;
        }

        $w = imagesx($img);
        $h = imagesy($img);
        $canvas = imagecreatetruecolor($w, $h);
        if ($canvas === false) {
            imagedestroy($img);

            return $this->renderTextFallback($orden, $textAlign);
        }
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $img, 0, 0, 0, 0, $w, $h);
        imagedestroy($img);

        ob_start();
        imagepng($canvas);
        $pngOut = (string) ob_get_clean();
        imagedestroy($canvas);

        if ($pngOut === '') {
            return $this->renderTextFallback($orden, $textAlign);
        }

        $uri    = 'data:image/png;base64,' . base64_encode($pngOut);
        $wMmStr = rtrim(rtrim(sprintf('%.2f', $wMm), '0'), '.');

        $html = '<img class="envelope-barcode-raster orden-barcode-label-img" src="' . $this->attr($uri) . '"';
        $html .= ' alt="' . $this->attr($orden) . '"';
        $html .= ' style="display:block;width:' . $this->attr($wMmStr) . 'mm;max-width:100%;height:auto;">';

        return $html . $this->renderValueText($orden, $textAlign);
    }

    private function attr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function renderValueText(string $orden, string $textAlign): string
    {
        return '<div class="envelope-barcode-value-text" style="font-family:Arial,Helvetica,sans-serif;font-size:9pt;font-weight:600;letter-spacing:0.05em;margin-top:1mm;text-align:'
            . $this->attr($textAlign) . ';">' . $this->attr($orden) . '</div>';
    }

    private function renderTextFallback(string $orden, string $textAlign): string
    {
        return '<div class="envelope-barcode-fallback" style="font-family:Arial,Helvetica,sans-serif;font-size:11pt;font-weight:600;letter-spacing:0.05em;padding:2mm 0;text-align:'
            . $this->attr($textAlign) . ';">' . $this->attr($orden) . '</div>';
    }
}
