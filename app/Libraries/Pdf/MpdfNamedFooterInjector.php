<?php



namespace App\Libraries\Pdf;



use Mpdf\Mpdf;



/**

 * Pie distinto en hoja 1 vs hojas 2+ (Paciente / No. Orden) vía @page :first + DefHTMLFooterByName.

 */

final class MpdfNamedFooterInjector

{

    public const NAME_PAGE_ONE = 'report-ft-p1';



    public const NAME_REST = 'report-ft-rest';



    /**

     * @param array<string, mixed>|null $orderSheetSlot

     */

    public static function shouldUseDualFooters(?array $orderSheetSlot, string $footerInner): bool

    {

        if ($footerInner === '' || $orderSheetSlot === null) {

            return false;

        }



        $patient = trim((string) ($orderSheetSlot['patient'] ?? ''));

        $order   = trim((string) ($orderSheetSlot['order'] ?? ''));



        return $patient !== '' || $order !== '';

    }



    /**
     * @param float $pageBottomGapMm Márgen inferior de la hoja (distancia borde → base del pie).
     */
    public static function injectPageCss(string $html, float $pageBottomGapMm): string

    {

        $mgf  = max(0.0, $pageBottomGapMm);

        $p1   = self::NAME_PAGE_ONE;

        $rest = self::NAME_REST;

        $css  = "<style>\n/* mPDF pies hoja 1 / resto */\n"

            . "@page { footer: html_{$rest}; margin-footer: {$mgf}mm; }\n"

            . "@page :first { footer: html_{$p1}; }\n"

            . "</style>\n";



        if (stripos($html, '</head>') !== false) {

            return str_ireplace('</head>', $css . '</head>', $html);

        }



        return $css . $html;

    }



    public static function registerFooters(Mpdf $mpdf, string $footerPageOne, string $footerRest): void

    {

        $footerPageOne = trim($footerPageOne);

        $footerRest    = trim($footerRest);

        if ($footerPageOne === '' && $footerRest === '') {

            return;

        }

        if ($footerRest === '') {

            $footerRest = $footerPageOne;

        }

        if ($footerPageOne === '') {

            $footerPageOne = $footerRest;

        }



        $mpdf->DefHTMLFooterByName(self::NAME_PAGE_ONE, $footerPageOne);

        $mpdf->DefHTMLFooterByName(self::NAME_REST, $footerRest);

    }



    /** @deprecated Usar injectPageCss() + registerFooters() */

    public static function inject(string $html, string $footerPageOne, string $footerRest): string

    {

        return self::injectPageCss($html, 2.0);

    }

}

