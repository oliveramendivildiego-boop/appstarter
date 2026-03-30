<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultados - <?= esc($paciente->first_name ?? '') ?> <?= esc($paciente->last_name_fa ?? '') ?></title>
    <base href="<?= base_url() ?>" />
    <link rel="stylesheet" href="<?= base_url('assets/css/report_pdf.css') ?>" />
    <?php
    $pl = is_array($pdf_layout ?? null) ? $pdf_layout : [];
    $mm = is_array($pl['margins_mm'] ?? null)
        ? $pl['margins_mm']
        : \App\Services\ReportPdfLayoutService::defaultMarginsMmStatic();
    $mt = (float) ($mm['top'] ?? 15);
    $mr = (float) ($mm['right'] ?? 15);
    $mb = (float) ($mm['bottom'] ?? 15);
    $ml = (float) ($mm['left'] ?? 15);
    ?>
    <style>
        body { margin: <?= esc((string) $mt) ?>mm <?= esc((string) $mr) ?>mm <?= esc((string) $mb) ?>mm <?= esc((string) $ml) ?>mm !important; }
    </style>
</head>
<body>
<?php
helper('registro');

$labForLogo = is_array($lab_config ?? null) ? $lab_config : [];
$logoRel    = $labForLogo['logo'] ?? 'images/logo-john.png';
$logoPath   = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $logoRel);
$pdf_logo_data_uri = '';
if (file_exists($logoPath)) {
    $logoData = base64_encode((string) file_get_contents($logoPath));
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mime     = $finfo ? finfo_file($finfo, $logoPath) : false;
    if ($finfo) {
        finfo_close($finfo);
    }
    $pdf_logo_data_uri = 'data:' . ($mime ?: 'image/png') . ';base64,' . $logoData;
}

$pdfBlockViews = [
    'header'         => 'registers/pdf/blocks/header',
    'patient_doctor' => 'registers/pdf/blocks/patient_doctor',
    'results'        => 'registers/pdf/blocks/results',
    'notes'          => 'registers/pdf/blocks/notes',
    'footer'         => 'registers/pdf/blocks/footer',
];

$ctx = [
    'register_info' => $register_info,
    'paciente'      => $paciente,
    'doctor'        => $doctor,
    'grupos'        => $grupos,
    'lab_config'    => $lab_config,
    'report_url'    => $report_url ?? '',
    'qr_data_uri'       => $qr_data_uri ?? '',
    'pdf_layout'        => $pdf_layout ?? [],
    'pdf_logo_data_uri' => $pdf_logo_data_uri,
];

foreach (($pdf_layout['blocks'] ?? []) as $block) {
    if (empty($block['enabled'])) {
        continue;
    }
    $bid = (string) ($block['id'] ?? '');
    if ($bid === '' || ! isset($pdfBlockViews[$bid])) {
        continue;
    }
    echo view($pdfBlockViews[$bid], $ctx);
}
?>
</body>
</html>
