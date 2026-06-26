<?php

namespace App\Libraries\Pdf;

/**
 * Márgenes y reserva de pie para mPDF a partir del HTML de plantilla (@page / marcadores).
 */
final class MpdfLayoutMetrics
{
    /**
     * @return array{top: float, right: float, bottom: float, left: float, footer_reserve_mm: float}
     */
    public static function fromHtml(string $html): array
    {
        $defaults = ['top' => 15.0, 'right' => 15.0, 'bottom' => 15.0, 'left' => 15.0, 'footer_reserve_mm' => 0.0];

        if (preg_match('/@page\s*\{([^}]+)\}/is', $html, $pageMatch)) {
            $block = $pageMatch[1];
            foreach (['top', 'right', 'bottom', 'left'] as $side) {
                if (preg_match('/margin-' . $side . '\s*:\s*([\d.]+)\s*mm/i', $block, $m)) {
                    $defaults[$side] = max(0.0, (float) $m[1]);
                }
            }
        }

        if (preg_match('/<!--\s*pdf-footer-reserve-mm:([\d.]+)\s*-->/', $html, $fm)) {
            $defaults['footer_reserve_mm'] = max(0.0, (float) $fm[1]);
        }

        // @page margin-bottom ya incluye reserva de pie (mb + reserve); separar gap de página y reserva de pie.
        if ($defaults['footer_reserve_mm'] > 0.0 && $defaults['bottom'] > $defaults['footer_reserve_mm']) {
            $defaults['bottom'] = max(0.0, $defaults['bottom'] - $defaults['footer_reserve_mm']);
        }

        return $defaults;
    }
}
