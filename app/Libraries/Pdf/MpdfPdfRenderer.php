<?php



namespace App\Libraries\Pdf;



use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Mpdf\WatermarkImage;



/**

 * Genera PDF con mPDF a partir del HTML de plantilla.

 */

class MpdfPdfRenderer implements PdfRendererInterface

{

    public function renderHtml(string $html, PdfOptions $options): string

    {

        $layout         = MpdfLayoutSnapshot::extractFromHtml($html);

        $watermark      = HtmlMpdfAdapter::extractWatermarkData($html);

        $orderSheetSlot = MpdfOrderSheetFooterInjector::extractSlot($html);



        $html = MpdfLayoutSnapshot::stripMarker($html);

        $html = HtmlMpdfAdapter::stripWatermarkMarker($html);

        $html = MpdfOrderSheetFooterInjector::stripMarker($html);



        [$html, $footerInner] = MpdfFooterExtractor::extract($html);



        [$html, $headerHtml] = MpdfPaginationHtmlInjector::extractHeaderHtml($html);

        $html = MpdfFooterStyles::injectDocumentFooterCss($html, $layout);

        $html = HtmlMpdfAdapter::adapt($html, $options);
        $html = MpdfInlineImageResolver::materializeDataUriImages($html);

        $footerHtmlPageOne = null;
        $footerHtmlRest    = null;
        $useDualFooters    = false;

        if ($footerInner !== null && $footerInner !== '') {
            $footerHtmlPageOne = MpdfFooterStyles::wrapForSetHtmlFooter($footerInner, $layout);
            $footerHtmlRest    = $footerHtmlPageOne;

            if (MpdfNamedFooterInjector::shouldUseDualFooters($orderSheetSlot, $footerInner)) {
                $useDualFooters = true;
                $footerHtmlRest = MpdfOrderSheetFooterInjector::prependOrderSheetRowToFooter(
                    $footerHtmlPageOne,
                    $orderSheetSlot,
                    MpdfOrderSheetFooterInjector::countFooterColumns($footerHtmlPageOne),
                );
            }
        }

        $footerHtml = (! $useDualFooters && $footerHtmlPageOne !== null && $footerHtmlPageOne !== '')
            ? $footerHtmlPageOne
            : null;

        $metrics = $layout !== []

            ? MpdfFooterStyles::resolveLayoutMetrics($layout)

            : MpdfLayoutMetrics::fromHtml($html);

        if ($useDualFooters && $footerHtmlPageOne !== '' && $footerHtmlRest !== '') {
            $html = MpdfNamedFooterInjector::injectPageCss(
                $html,
                (float) ($metrics['bottom'] ?? 2.0),
            );
        }

        $mpdf = $this->createMpdf($options, $metrics);



        if ($headerHtml !== '') {

            $mpdf->SetHTMLHeader($headerHtml);

        }



        if ($useDualFooters && $footerHtmlPageOne !== '' && $footerHtmlRest !== '') {
            MpdfNamedFooterInjector::registerFooters($mpdf, $footerHtmlPageOne, $footerHtmlRest);
        } elseif ($footerHtml !== null && $footerHtml !== '') {

            $mpdf->SetHTMLFooter($footerHtml);

        }



        if ($watermark !== null) {

            $imagePath = $this->resolveImagePath($watermark);

            if ($imagePath !== null) {

                $sizePct = max(10, min(95, (int) ($watermark['size_percent'] ?? 45)));

                $opacity = max(0.05, min(0.9, (float) ($watermark['opacity'] ?? 0.12)));

                [$widthMm, $heightMm] = $this->computeWatermarkDimensionsMm($mpdf, $imagePath, $sizePct);

                $mpdf->SetWatermarkImage(new WatermarkImage(

                    $imagePath,

                    [$widthMm, $heightMm],

                    WatermarkImage::POSITION_CENTER_PAGE,

                    $opacity,

                    true,

                ));

                $mpdf->showWatermarkImage = true;

            }

        }



        $mpdf->WriteHTML($html);



        return $mpdf->Output('', Destination::STRING_RETURN);

    }



    public function engineName(): string

    {

        return 'mpdf';

    }



    /**

     * @param array{top: float, right: float, bottom: float, left: float, footer_reserve_mm: float} $metrics

     */

    private function createMpdf(PdfOptions $options, array $metrics): Mpdf

    {

        $format  = $this->resolveFormat($options);

        $tempDir = trim((string) (config('Pdf')->mpdfTempDir ?? ''));

        if ($tempDir === '') {

            $tempDir = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'mpdf';

        }

        if (! is_dir($tempDir)) {

            @mkdir($tempDir, 0755, true);

        }



        $footerReserve = max(0.0, (float) ($metrics['footer_reserve_mm'] ?? 0.0));

        $pageBottomGap = max(0.0, (float) ($metrics['bottom'] ?? 15));

        $marginBottom = $footerReserve > 0.0 ? ($pageBottomGap + $footerReserve) : $pageBottomGap;



        return new Mpdf([

            'mode'          => 'utf-8',

            'format'        => $format,

            'orientation'   => strtoupper(substr($options->orientation, 0, 1)) === 'L' ? 'L' : 'P',

            'tempDir'       => $tempDir,

            'margin_left'   => (float) ($metrics['left'] ?? 15),

            'margin_right'  => (float) ($metrics['right'] ?? 15),

            'margin_top'    => (float) ($metrics['top'] ?? 15),

            'margin_bottom' => $marginBottom,

            'margin_header' => 8,

            // margin_footer = hueco inferior de la hoja (mm), no la altura del pie.
            'margin_footer' => $pageBottomGap,

            'default_font'  => 'dejavusans',

            'dpi'           => 96,

            'img_dpi'       => 96,

            'use_kwt'       => true,

        ]);

    }



    /**

     * @return string|array{0: float, 1: float}

     */

    private function resolveFormat(PdfOptions $options): string|array

    {

        $key = strtolower((string) ($options->paperKey ?? 'letter'));



        if ($key === 'custom' && $options->widthMm !== null && $options->heightMm !== null) {

            return [(float) $options->widthMm, (float) $options->heightMm];

        }



        return match ($key) {

            'a4'    => 'A4',

            'legal' => 'Legal',

            default => 'Letter',

        };

    }



    /**
     * Ancho/alto en mm: size_percent es % del ancho de hoja (paridad con Dompdf).
     *
     * @return array{0: float, 1: float}
     */
    private function computeWatermarkDimensionsMm(Mpdf $mpdf, string $imagePath, int $sizePercent): array
    {
        $pageWidthMm = max(1.0, (float) ($mpdf->w ?? 210.0));
        $targetWidth = $pageWidthMm * ($sizePercent / 100);

        $info = @getimagesize($imagePath);
        if (is_array($info) && ($info[0] ?? 0) > 0 && ($info[1] ?? 0) > 0) {
            $targetHeight = $targetWidth * ((float) $info[1] / (float) $info[0]);
        } else {
            $targetHeight = $targetWidth;
        }

        return [round($targetWidth, 2), round($targetHeight, 2)];
    }



    /**

     * @param array<string, mixed> $watermark

     */

    private function resolveImagePath(array $watermark): ?string

    {

        $path = trim((string) ($watermark['path'] ?? ''));

        if ($path !== '' && is_file($path) && is_readable($path)) {

            return $path;

        }



        $uri = trim((string) ($watermark['uri'] ?? ''));

        if ($uri === '') {

            return null;

        }



        if (! str_starts_with($uri, 'data:') && is_file($uri) && is_readable($uri)) {

            return $uri;

        }



        if (! preg_match('#^data:image/(png|jpe?g|gif|webp);base64,(.+)$#i', $uri, $matches)) {

            return null;

        }



        $binary = base64_decode($matches[2], true);

        if ($binary === false || $binary === '') {

            return null;

        }



        $ext  = strtolower($matches[1]) === 'jpeg' ? 'jpg' : strtolower($matches[1]);

        $temp = $this->tempDir() . DIRECTORY_SEPARATOR . 'wm_' . bin2hex(random_bytes(8)) . '.' . $ext;

        if (@file_put_contents($temp, $binary) === false) {

            return null;

        }



        return $temp;

    }



    private function tempDir(): string

    {

        $dir = trim((string) (config('Pdf')->mpdfTempDir ?? ''));

        if ($dir === '') {

            $dir = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'mpdf';

        }

        if (! is_dir($dir)) {

            @mkdir($dir, 0755, true);

        }



        return $dir;

    }

}


