<?php
declare(strict_types=1);

helper('registro');

$pl = is_array($pdf_layout ?? null) ? $pdf_layout : [];
$wm = is_array($pl['watermark'] ?? null) ? $pl['watermark'] : \App\Services\ReportPdfLayoutService::defaultWatermarkStatic();
$wmUri = $pdf_watermark_uri ?? \App\Services\ReportPdfLayoutService::getWatermarkDataUriForLayout($pl);
$opacityW = (float) ($wm['opacity'] ?? 0.12);
$sizeW    = (int) ($wm['size_percent'] ?? 45);

$labForLogo = is_array($lab_config ?? null) ? $lab_config : [];
$logoRel    = $labForLogo['logo'] ?? 'images/logo-john.png';
$logoPath   = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $logoRel);
$pdf_logo_data_uri = $pdf_logo_data_uri ?? '';
if ($pdf_logo_data_uri === '') {
    if (file_exists($logoPath)) {
        $logoData = base64_encode((string) file_get_contents($logoPath));
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mime     = $finfo ? finfo_file($finfo, $logoPath) : false;
        if ($finfo) {
            finfo_close($finfo);
        }
        $pdf_logo_data_uri = 'data:' . ($mime ?: 'image/png') . ';base64,' . $logoData;
    }
}

$pdfBlockViews = [
    'header'         => 'registers/pdf/blocks/header',
    'patient_doctor' => 'registers/pdf/blocks/patient_doctor',
    'results'        => 'registers/pdf/blocks/results',
    'notes'          => 'registers/pdf/blocks/notes',
    'lab_firmas'     => 'registers/pdf/blocks/lab_firmas',
    'footer'         => 'registers/pdf/blocks/footer',
];

$ctx = [
    'register_info'     => $register_info,
    'paciente'          => $paciente,
    'doctor'            => $doctor,
    'grupos'            => $grupos,
    'lab_config'        => $lab_config,
    'report_url'        => $report_url ?? '',
    'qr_data_uri'       => $qr_data_uri ?? '',
    'report_emitido_en' => $report_emitido_en ?? \App\Services\RegisterService::formatNowForReport(),
    'pdf_layout'        => $pl,
    'pdf_logo_data_uri' => $pdf_logo_data_uri,
    'report_pria_tipo_muestra_nombre' => $report_pria_tipo_muestra_nombre ?? [],
    'report_pria_metodo_nombre'       => $report_pria_metodo_nombre ?? [],
    'report_lab_firmas'               => $report_lab_firmas ?? [],
    'report_pria_refs_consolidada'    => $report_pria_refs_consolidada ?? [],
    'analisis_variant'                => $analisis_variant ?? 'pdf',
];
?>
<?php if ($wmUri !== null && $wmUri !== ''): ?>
<?php
// <img> con transform suele no pintarse en Dompdf; la opacidad en el propio img a veces no se aplica: usar contenedor + tabla centrada.
$wmSize     = max(10, min(95, (int) $sizeW));
$opacityCss = number_format(max(0.05, min(0.9, $opacityW)), 2, '.', '');
?>
<div class="pdf-watermark-layer" aria-hidden="true">
    <div class="pdf-watermark-inner" style="opacity:<?= esc($opacityCss, 'attr') ?>;">
        <table class="pdf-watermark-table" width="100%" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;height:11in;border-collapse:collapse;border-spacing:0;">
            <tr>
                <td class="pdf-watermark-td" align="center" valign="middle" style="height:11in;vertical-align:middle;">
                    <img src="<?= esc($wmUri, 'attr') ?>" alt="" class="pdf-watermark-img" style="width:<?= $wmSize ?>%;max-width:95%;height:auto;">
                </td>
            </tr>
        </table>
    </div>
</div>
<?php endif; ?>
<div class="pdf-main-stack">
<?php foreach (($pl['blocks'] ?? []) as $block):
    if (empty($block['enabled'])) {
        continue;
    }
    $bid = (string) ($block['id'] ?? '');
    if ($bid === '' || ! isset($pdfBlockViews[$bid])) {
        continue;
    }
    echo view($pdfBlockViews[$bid], $ctx);
endforeach; ?>
</div>
