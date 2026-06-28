<?php
declare(strict_types=1);
/**
 * Mide alturas de fila 1 vs fila 2 en pilas del pie mPDF (Celular/Correo vs Tarija/Página).
 * Uso: php writable/scripts/measure_footer_stack_rows.php [register_id]
 */
putenv('PDF_RENDERER=mpdf');
$_ENV['PDF_RENDERER'] = 'mpdf';
define('ENVIRONMENT', 'development');
define('FCPATH', dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);

$id = (int) ($argv[1] ?? 143);

$buildFooter = static function (int $registerId): array {
    $rs = new \App\Services\RegisterService(new \App\Models\RegisterModel(), new \App\Models\AppConfigModel());
    $data = $rs->prepareReportData($registerId);
    helper('qr');
    $layout = (new \App\Services\ReportPdfLayoutService())->getActiveLayoutForRender();
    $url = $rs->publicReportViewerUrlForQr($registerId);
    $emitido = $rs->lockReportEmitidoEnForPrintOrPdf($registerId);
    $qr = qr_base64($url, \App\Services\ReportPdfLayoutService::qrImagePixelSizeFromLayout($layout));
    $html = $rs->getOrBuildReportPdfHtml($registerId, $data, $url, $qr, $emitido, $layout);

    $layoutSnap = \App\Libraries\Pdf\MpdfLayoutSnapshot::extractFromHtml($html);
    $h = \App\Libraries\Pdf\MpdfLayoutSnapshot::stripMarker($html);
    $h = \App\Libraries\Pdf\HtmlMpdfAdapter::stripWatermarkMarker($h);
    $orderSheetSlot = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::extractSlot($html);
    $h = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::stripMarker($h);
    [, $fi] = \App\Libraries\Pdf\MpdfFooterExtractor::extract($h);

    $wrapped = \App\Libraries\Pdf\MpdfFooterStyles::wrapForSetHtmlFooter((string) $fi, $layoutSnap);
    if (\App\Libraries\Pdf\MpdfOrderSheetFooterInjector::shouldPrependOrderSheetBand($orderSheetSlot, (string) $fi)) {
        $wrapped = \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::prependOrderSheetRowToFooter(
            $wrapped,
            $orderSheetSlot,
            \App\Libraries\Pdf\MpdfOrderSheetFooterInjector::countFooterColumns($wrapped),
        );
    }
    $footer = \App\Libraries\Pdf\MpdfFooterStyles::finalizeSetHtmlFooterFragment($wrapped, $layoutSnap);

    return [$footer, $layout, $layoutSnap];
};

$parseStyle = static function (string $style): array {
    $out = [];
    foreach (explode(';', $style) as $decl) {
        $decl = trim($decl);
        if ($decl === '' || ! str_contains($decl, ':')) {
            continue;
        }
        [$k, $v] = array_map('trim', explode(':', $decl, 2));
        $out[strtolower($k)] = $v;
    }

    return $out;
};

$parsePx = static function (?string $val): float {
    if ($val === null || $val === '') {
        return 0.0;
    }
    if (preg_match('/^([\d.]+)\s*px$/i', $val, $m)) {
        return (float) $m[1];
    }

    return 0.0;
};

$parsePt = static function (?string $val): float {
    if ($val === null || $val === '') {
        return 0.0;
    }
    if (preg_match('/^([\d.]+)\s*pt$/i', $val, $m)) {
        return (float) $m[1];
    }

    return 0.0;
};

$stripTagsText = static function (string $html): string {
    return trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($html))) ?? '');
};

$estimateWrappedLines = static function (string $text, float $colWidthMm, float $fontSizePt, float $avgCharWidthFactor = 0.52): int {
    if ($text === '' || $colWidthMm <= 0) {
        return 1;
    }
    // pt → mm (1pt ≈ 0.3528mm); ancho aproximado por carácter en DejaVu Sans
    $charWidthMm = $fontSizePt * 0.3528 * $avgCharWidthFactor;
    $charsPerLine = max(1, (int) floor($colWidthMm / max(0.01, $charWidthMm)));

    return max(1, (int) ceil(mb_strlen($text) / $charsPerLine));
};

$lineHeightPx = static function (float $fontSizePt, float $lineHeightFactor): float {
    // 1pt = 96/72 px at 96dpi reference used in CSS px conversions in layout
    return $fontSizePt * (96.0 / 72.0) * $lineHeightFactor;
};

$analyzeStack = static function (
    \DOMElement $stackTable,
    string $columnLabel,
    float $colWidthPct,
    float $contentWidthMm,
) use ($parseStyle, $parsePx, $parsePt, $stripTagsText, $estimateWrappedLines, $lineHeightPx): array {
    $colWidthMm = $contentWidthMm * ($colWidthPct / 100.0);
    $rows = [];
    foreach ($stackTable->childNodes as $child) {
        if ($child instanceof \DOMElement && $child->nodeName === 'tr') {
            $rows[] = $child;
        }
    }

    $report = ['column' => $columnLabel, 'col_width_pct' => $colWidthPct, 'col_width_mm' => round($colWidthMm, 2), 'rows' => []];
    $cumulativeTopMm = 0.0;

    foreach ($rows as $idx => $tr) {
        $td = null;
        foreach ($tr->childNodes as $c) {
            if ($c instanceof \DOMElement && $c->nodeName === 'td') {
                $td = $c;
                break;
            }
        }
        if ($td === null) {
            continue;
        }

        $style = $parseStyle($td->getAttribute('style'));
        $padTopPx = $parsePx($style['padding-top'] ?? '0');
        $padTopMm = $padTopPx * 0.264583; // css px → mm @96dpi

        $innerHtml = '';
        foreach ($td->childNodes as $n) {
            $innerHtml .= $td->ownerDocument?->saveHTML($n) ?? '';
        }
        $text = $stripTagsText($innerHtml);

        // Tipografía dominante del <p> / span
        $fontSizePt = 8.0;
        $lhFactor   = 1.1;
        if (preg_match('/font-size\s*:\s*([\d.]+)\s*pt/i', $innerHtml, $fm)) {
            $fontSizePt = (float) $fm[1];
        }
        if (preg_match('/line-height\s*:\s*([\d.]+)/i', $innerHtml, $lm)) {
            $lhFactor = (float) $lm[1];
        }

        $lines = $estimateWrappedLines($text, $colWidthMm, $fontSizePt);
        $linePx = $lineHeightPx($fontSizePt, $lhFactor);
        $contentHeightMm = ($linePx * $lines) * 0.264583;
        $rowStartMm = $cumulativeTopMm + $padTopMm;
        $rowEndMm = $rowStartMm + $contentHeightMm;

        $wrapperTags = [];
        if (str_contains($innerHtml, '<center')) {
            $wrapperTags[] = 'center';
        }
        if (preg_match('/<div[^>]*mpdf-ft-align-wrap/', $innerHtml)) {
            $wrapperTags[] = 'div.mpdf-ft-align-wrap';
        }
        if (str_contains($innerHtml, 'pdf-ft-piece')) {
            $wrapperTags[] = 'pdf-ft-piece';
        }
        if (str_contains($innerHtml, 'pdf-ft-custom-text')) {
            $wrapperTags[] = 'pdf-ft-custom-text';
        }
        if (str_contains($innerHtml, 'pdf-ft-pagination')) {
            $wrapperTags[] = 'pdf-ft-pagination';
        }

        $report['rows'][] = [
            'stack_index'        => $idx,
            'label'              => $idx === 0 ? 'fila_1' : 'fila_2',
            'text'               => $text,
            'padding_top_px'     => $padTopPx,
            'padding_top_mm'     => round($padTopMm, 3),
            'font_size_pt'       => $fontSizePt,
            'line_height'        => $lhFactor,
            'estimated_lines'    => $lines,
            'estimated_line_mm'  => round($linePx * 0.264583, 3),
            'estimated_content_mm' => round($contentHeightMm, 3),
            'estimated_row_start_mm' => round($rowStartMm, 3),
            'estimated_row_baseline_mm' => round($rowStartMm + ($linePx * 0.264583 * 0.8), 3),
            'estimated_row_end_mm' => round($rowEndMm, 3),
            'wrappers'           => $wrapperTags,
        ];

        $cumulativeTopMm = $rowEndMm;
    }

    return $report;
};

[$footer, $layout, $layoutSnap] = $buildFooter($id);

$mm = is_array($layoutSnap['margins_mm'] ?? null)
    ? $layoutSnap['margins_mm']
    : \App\Services\ReportPdfLayoutService::defaultMarginsMmStatic();
$pageW = 215.9; // Letter mm
$contentW = $pageW - (float) ($mm['left'] ?? 15) - (float) ($mm['right'] ?? 15);

$sec = is_array($layout['section_layouts']['footer'] ?? null) ? $layout['section_layouts']['footer'] : [];
$rowGapPx = max(0, (int) ($sec['row_gap_px'] ?? 0));

echo "=== Medición pie mPDF (registro {$id}) ===\n\n";
echo 'Ancho útil página: ' . round($contentW, 2) . " mm\n";
echo 'Márgenes L/R: ' . ($mm['left'] ?? '?') . ' / ' . ($mm['right'] ?? '?') . " mm\n";
echo "row_gap_px (plantilla): {$rowGapPx}\n\n";

$prev = libxml_use_internal_errors(true);
$dom = new DOMDocument('1.0', 'UTF-8');
$dom->loadHTML('<?xml encoding="utf-8"><div id="root">' . $footer . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
libxml_clear_errors();
libxml_use_internal_errors($prev);

$xpath = new DOMXPath($dom);
$stacks = $xpath->query('//table[contains(@class,"mpdf-ft-stack")]');
$stackReports = [];

if ($stacks !== false) {
    $i = 0;
    foreach ($stacks as $stack) {
        if (! $stack instanceof DOMElement) {
            continue;
        }
        $i++;
        // Detectar columna por ancestro mpdf-ft-cell width
        $outerTd = $stack;
        while ($outerTd !== null && ! ($outerTd instanceof DOMElement && $outerTd->nodeName === 'td' && str_contains($outerTd->getAttribute('class'), 'mpdf-ft-cell'))) {
            $outerTd = $outerTd->parentNode;
        }
        $outerStyle = $outerTd instanceof DOMElement ? $parseStyle($outerTd->getAttribute('style')) : [];
        $widthPct = 20.0;
        if (preg_match('/([\d.]+)\s*%/', $outerStyle['width'] ?? ($outerTd?->getAttribute('width') ?? '20%'), $wm)) {
            $widthPct = (float) $wm[1];
        }
        if ($outerTd instanceof DOMElement && (int) $outerTd->getAttribute('colspan') === 2) {
            $widthPct = 40.0;
        }

        $label = $i === 1 ? 'CENTRO (Celular / Tarija)' : 'DERECHA (Correo / Página)';
        $stackReports[] = $analyzeStack($stack, $label, $widthPct, $contentW);
    }
}

foreach ($stackReports as $rep) {
    echo "--- {$rep['column']} — ancho col ~{$rep['col_width_mm']} mm ({$rep['col_width_pct']}%) ---\n";
    foreach ($rep['rows'] as $row) {
        echo "  [{$row['label']}] \"{$row['text']}\"\n";
        echo "    padding-top: {$row['padding_top_px']} px ({$row['padding_top_mm']} mm)\n";
        echo "    tipografía: {$row['font_size_pt']} pt, line-height {$row['line_height']}\n";
        echo "    líneas estimadas: {$row['estimated_lines']} × {$row['estimated_line_mm']} mm = {$row['estimated_content_mm']} mm contenido\n";
        echo "    inicio fila estimado (Y relativo pila): {$row['estimated_row_start_mm']} mm\n";
        echo "    baseline texto estimada: {$row['estimated_row_baseline_mm']} mm\n";
        echo '    wrappers: ' . implode(', ', $row['wrappers']) . "\n";
    }
    echo "\n";
}

if (count($stackReports) >= 2) {
    $r1c = $stackReports[0]['rows'][0] ?? null;
    $r2c = $stackReports[0]['rows'][1] ?? null;
    $r1r = $stackReports[1]['rows'][0] ?? null;
    $r2r = $stackReports[1]['rows'][1] ?? null;

    if ($r1c && $r2c && $r1r && $r2r) {
        $fila1Diff = round($r1r['estimated_row_end_mm'] - $r1c['estimated_row_end_mm'], 3);
        $fila2StartDiff = round($r2r['estimated_row_start_mm'] - $r2c['estimated_row_start_mm'], 3);
        $baselineDiff = round($r2r['estimated_row_baseline_mm'] - $r2c['estimated_row_baseline_mm'], 3);

        echo "=== COMPARACIÓN FILA 1 (Celular vs Correo) ===\n";
        echo "  Altura fin fila 1 centro: {$r1c['estimated_row_end_mm']} mm\n";
        echo "  Altura fin fila 1 derecha: {$r1r['estimated_row_end_mm']} mm\n";
        echo "  Diferencia altura fila 1: {$fila1Diff} mm " . ($fila1Diff > 0.1 ? '(derecha más alta → empuja Página abajo)' : '(casi iguales)') . "\n\n";

        echo "=== COMPARACIÓN FILA 2 (Tarija vs Página) — inicio de caja ===\n";
        echo "  Inicio fila 2 centro (Tarija): {$r2c['estimated_row_start_mm']} mm\n";
        echo "  Inicio fila 2 derecha (Página): {$r2r['estimated_row_start_mm']} mm\n";
        echo "  Desfase inicio fila 2: {$fila2StartDiff} mm\n\n";

        echo "=== COMPARACIÓN BASELINE TEXTO fila 2 ===\n";
        echo "  Baseline Tarija: {$r2c['estimated_row_baseline_mm']} mm\n";
        echo "  Baseline Página: {$r2r['estimated_row_baseline_mm']} mm\n";
        echo "  Desfase baseline: {$baselineDiff} mm\n\n";

        echo "=== DIFERENCIAS ESTRUCTURALES (fila 2) ===\n";
        echo '  Centro wrappers: ' . implode(' → ', $r2c['wrappers']) . "\n";
        echo '  Derecha wrappers: ' . implode(' → ', $r2r['wrappers']) . "\n";
        echo "  Centro: usa <center> (2 niveles en fila 1). Derecha: usa <div align=right> (2 niveles en fila 1).\n";
        echo "  Fila 2 centro: pdf-ft-piece + pdf-ft-custom-text. Fila 2 derecha: solo pdf-ft-pagination.\n";

        // Altura caja línea fila 1 con label 8pt lh1.35 + valor 8pt lh1.1
        $lhLabelPt = 8 * 1.35;
        $lhValuePt = 8 * 1.1;
        $row1BoxPt = max($lhLabelPt, $lhValuePt);
        echo "\n=== CAJA DE LÍNEA fila 1 (tipografía mixta label+valor) ===\n";
        echo '  max(8pt×1.35, 8pt×1.1) = ' . round($row1BoxPt, 2) . " pt ≈ " . round($row1BoxPt * 0.3528, 2) . " mm\n";
        echo '  + padding-top fila 2 (3px) = ' . round(3 * 0.264583, 3) . " mm\n";
        echo '  → inicio teórico fila 2 si fila1 igual: ' . round($row1BoxPt * 0.3528 + 3 * 0.264583, 3) . " mm desde tope pila\n";
    }
}

// PDF diagnóstico: mPDF en SetHTMLFooter suele ignorar background CSS en <td>.
// Usar bgcolor HTML + bordes gruesos + etiquetas de texto visibles.
$rowMarker = static function (int $stackNo, int $rowNo): array {
    if ($rowNo === 1) {
        return ['tag' => 'F1', 'bgcolor' => '#FFE082', 'border' => '2px solid #F57F17'];
    }

    return $stackNo === 1
        ? ['tag' => 'F2-C', 'bgcolor' => '#A5D6A7', 'border' => '2px solid #2E7D32']
        : ['tag' => 'F2-D', 'bgcolor' => '#90CAF9', 'border' => '2px solid #1565C0'];
};

$debugFooter = preg_replace_callback(
    '/(<table\b[^>]*\bmpdf-ft-stack\b[^>]*>)(.*?)(<\/table>)/is',
    static function (array $m) use ($rowMarker): string {
        static $stackNo = 0;
        $stackNo++;
        $inner = preg_replace_callback(
            '/(<tr\b[^>]*>)(.*?)(<\/tr>)/is',
            static function (array $rm) use ($stackNo, $rowMarker): string {
                static $rowByStack = [];
                $rowByStack[$stackNo] = ($rowByStack[$stackNo] ?? 0) + 1;
                $rowNo = $rowByStack[$stackNo];
                $mark = $rowMarker($stackNo, $rowNo);
                $extra = 'border:' . $mark['border'] . ';';
                if ($rowNo === 2) {
                    $extra .= 'border-top:4px solid #E91E63 !important;';
                }

                $useBgOnTd = $rowNo === 1;
                $out = $rm[0];
                if ($useBgOnTd) {
                    $out = preg_replace(
                        '/(<td\b)([^>]*\bstyle=(["\']))/i',
                        '$1 bgcolor="' . $mark['bgcolor'] . '"$2' . $extra,
                        $out,
                        1,
                    ) ?? $out;
                } else {
                    $out = preg_replace(
                        '/(<td\b)([^>]*\bstyle=(["\']))/i',
                        '$1$2' . $extra,
                        $out,
                        1,
                    ) ?? $out;
                }
                $out = preg_replace('/\bborder\s*:\s*0\s*;?/i', '', $out) ?? $out;

                if ($rowNo === 2) {
                    $out = preg_replace_callback(
                        '/(<td\b[^>]*>)(.*?)(<\/td>)/is',
                        static function (array $tm) use ($mark): string {
                            $inner = $tm[2];
                            $bandStyle = 'border-collapse:collapse;width:100%;height:15pt;min-height:15pt;max-height:15pt;border:'
                                . $mark['border'] . ';margin:0;padding:0';
                            $inner = preg_replace_callback(
                                '/(<table\b)(?![^>]*\bmpdf-ft-stack\b)([^>]*)(>)/i',
                                static function (array $tableMatch) use ($mark, $bandStyle): string {
                                    $attrs = $tableMatch[2];
                                    if (preg_match('/\bstyle=(["\'])/i', $attrs)) {
                                        $attrs = preg_replace(
                                            '/\bstyle=(["\'])(.*?)\1/is',
                                            'style="$2;' . $bandStyle . '"',
                                            $attrs,
                                            1,
                                        ) ?? $attrs;
                                    } else {
                                        $attrs .= ' style="' . $bandStyle . '"';
                                    }
                                    if (! preg_match('/\bbgcolor=/i', $attrs)) {
                                        $attrs = ' bgcolor="' . $mark['bgcolor'] . '"' . $attrs;
                                    }

                                    return $tableMatch[1] . $attrs . $tableMatch[3];
                                },
                                $inner,
                                1,
                            ) ?? $inner;
                            $inner = preg_replace(
                                '/(<table\b[^>]*>)(\s*<tr\b[^>]*>\s*<td\b)([^>]*>)/i',
                                '$1$2$3<span style="font-size:6pt;font-weight:bold;color:#000">[' . $mark['tag'] . ']</span> ',
                                $inner,
                                1,
                            ) ?? $inner;

                            return $tm[1] . $inner . $tm[3];
                        },
                        $out,
                        1,
                    ) ?? $out;
                } else {
                    $out = preg_replace(
                        '/(<td\b[^>]*>)(\s*)/i',
                        '$1<span style="font-size:6pt;font-weight:bold;color:#000">[' . $mark['tag'] . ']</span> ',
                        $out,
                        1,
                    ) ?? $out;
                }

                return $out;
            },
            $m[2],
        ) ?? $m[2];

        return $m[1] . $inner . $m[3];
    },
    $footer,
) ?? $footer;

$debugFooter = '<div style="font-family:sans-serif;font-size:8pt;margin-bottom:4px;line-height:1.2;border:2px solid #000;padding:2px 4px">'
    . '<b>PDF DIAGNÓSTICO (no es viewreport)</b> — '
    . '[F1]=fila1 amarillo | [F2-C]/[F2-D]=franja 15pt misma ALTURA (no comparar ancho columna) | '
    . 'línea rosa = inicio fila 2'
    . '</div>'
    . $debugFooter;

$htmlPath = WRITEPATH . 'debug/footer_stack_measure_' . $id . '_debug.html';
file_put_contents($htmlPath, $debugFooter);

$tempDir = WRITEPATH . 'cache/mpdf';
$m = new \App\Libraries\Pdf\SafeMpdf([
    'mode' => 'utf-8', 'format' => 'Letter', 'tempDir' => $tempDir,
    'margin_bottom' => 35, 'margin_footer' => 12, 'use_kwt' => false,
]);
$m->SetHTMLFooter($debugFooter);
$m->WriteHTML('<html><body class="pdf-engine-mpdf"><p style="font-size:10pt">Página de prueba — ver colores en el pie</p></body></html>');
$pdfBin = $m->Output('', \Mpdf\Output\Destination::STRING_RETURN);
$pdfPath = WRITEPATH . 'debug/footer_stack_measure_' . $id . '.pdf';
file_put_contents($pdfPath, $pdfBin);

echo "\nPDF diagnóstico: {$pdfPath}\n";
echo "HTML diagnóstico: {$htmlPath}\n";
echo "\nIMPORTANTE: abre ESE pdf (footer_stack_measure_{$id}.pdf), NO el de /registers/viewreport\n";
echo "Debes ver etiquetas [F1] [F2-C] [F2-D] y celdas con borde de color en el pie.\n";
