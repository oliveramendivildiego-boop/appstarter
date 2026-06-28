<?php



namespace App\Libraries\Pdf;



/**

 * Nombres de fuente CSS de plantilla → familias internas de mPDF.

 */

final class MpdfFontMapper

{

    /**

     * Valor seguro para font-family en CSS inline (con comillas si hace falta).

     */

    public static function quoteForCss(string $family): string

    {

        $family = trim(str_replace(['"', "'"], '', $family));

        if ($family === '') {

            return '"DejaVu Sans"';

        }

        if (str_contains($family, ' ')) {

            return '"' . $family . '"';

        }



        return $family;

    }



    /**

     * Familia reconocida por mPDF (DejaVu Sans → dejavusans).

     */

    public static function toMpdfFamily(string $family): string

    {

        $key = strtolower(trim(str_replace(['"', "'"], '', $family)));



        return match ($key) {

            'dejavu sans'     => 'dejavusans',

            'helvetica'       => 'sans-serif',

            'arial'           => 'sans-serif',

            'times new roman' => 'serif',

            'courier new'     => 'monospace',

            'sans-serif'      => 'sans-serif',

            'serif'           => 'serif',

            'monospace'       => 'monospace',

            default           => str_contains($key, 'dejavu') ? 'dejavusans' : 'dejavusans',

        };

    }



    public static function normalizeCssFontFamilies(string $css): string

    {

        $css = preg_replace_callback(

            '/font-family\s*:\s*([^;}{]+)/i',

            static function (array $m): string {

                $raw = trim($m[1]);

                $parts = preg_split('/\s*,\s*/', $raw) ?: [];

                $mapped = [];

                foreach ($parts as $part) {

                    $part = trim($part);

                    if ($part === '') {

                        continue;

                    }

                    $mapped[] = self::toMpdfFamily($part);

                }

                if ($mapped === []) {

                    $mapped[] = 'dejavusans';

                }



                return 'font-family:' . implode(', ', array_unique($mapped));

            },

            $css,

        ) ?? $css;



        return preg_replace('/"dejavusans"/i', 'dejavusans', $css) ?? $css;

    }



    public static function normalizeInlineStylesInHtml(string $html): string
    {
        return preg_replace_callback(
            '/\bstyle=(["\'])((?:\\\\.|(?!\1).)*)\1/s',
            static function (array $m): string {
                $style = self::decodeAttrValue($m[2]);
                $style = self::restoreHumanFontFamiliesInInlineStyle($style);

                return 'style=' . $m[1] . $style . $m[1];
            },
            $html,
        ) ?? $html;
    }

    /**
     * Pie SetHTMLFooter: sin comillas dobles en font-family (rompen style="...") y fuente mPDF nativa.
     */
    public static function sanitizeFooterFragmentHtml(string $html): string
    {
        $html = self::normalizeClassAttrsInHtml($html);

        return preg_replace_callback(
            '/\bstyle=(["\'])((?:\\\\.|(?!\1).)*)\1/s',
            static function (array $m): string {
                $style = self::decodeAttrValue($m[2]);
                $style = preg_replace('/font-family\s*:\s*[^;]+/i', 'font-family:dejavusans,sans-serif', $style) ?? $style;

                return 'style=' . $m[1] . $style . $m[1];
            },
            $html,
        ) ?? $html;
    }

    /**
     * mPDF pinta mal títulos en negrita cuando font-family inline queda como dejavusans (slug interno).
     * Comillas simples dentro de style="" para no cortar el atributo HTML.
     */
    private static function restoreHumanFontFamiliesInInlineStyle(string $style): string
    {
        return preg_replace_callback(
            '/font-family\s*:\s*([^;}{]+)/i',
            static function (array $m): string {
                $raw = trim($m[1]);
                $importantSuffix = '';
                if (preg_match('/\s*!important\s*$/i', $raw)) {
                    $raw = preg_replace('/\s*!important\s*$/i', '', $raw);
                    $importantSuffix = ' !important';
                }

                if (preg_match('/\bdejavusansb?\b/i', $raw)) {
                    return 'font-family:\'DejaVu Sans\',sans-serif' . $importantSuffix;
                }

                $parts = preg_split('/\s*,\s*/', $raw) ?: [];
                $normalized = [];
                foreach ($parts as $part) {
                    $part = trim(str_replace(['"', "'"], '', $part));
                    if ($part === '') {
                        continue;
                    }
                    $normalized[] = str_contains($part, ' ')
                        ? "'" . $part . "'"
                        : $part;
                }

                if ($normalized === []) {
                    return 'font-family:\'DejaVu Sans\',sans-serif' . $importantSuffix;
                }

                return 'font-family:' . implode(',', $normalized) . $importantSuffix;
            },
            $style,
        ) ?? $style;
    }

    public static function normalizeClassAttrsInHtml(string $html): string
    {
        return preg_replace_callback(
            '/\bclass=(["\'])([^"\']*)\1/i',
            static function (array $m): string {
                return 'class=' . $m[1] . self::decodeAttrValue($m[2]) . $m[1];
            },
            $html,
        ) ?? $html;
    }

    public static function decodeAttrValue(string $raw): string
    {
        $raw = str_ireplace(
            ['&#x3B;', '&#x3B', '&#X3B;', '&#X3B', '&#x3A;', '&#x3A', '&#X3A;', '&#X3A'],
            [';', ';', ';', ';', ':', ':', ':', ':'],
            $raw,
        );

        return html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

