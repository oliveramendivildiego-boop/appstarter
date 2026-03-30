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
        body { margin: <?= esc((string) $mt) ?>mm <?= esc((string) $mr) ?>mm <?= esc((string) $mb) ?>mm <?= esc((string) $ml) ?>mm !important; position: relative; }
    </style>
</head>
<body>
<?= view('registers/pdf/report_document', [
    'pdf_layout'          => $pdf_layout ?? [],
    'register_info'       => $register_info,
    'paciente'            => $paciente,
    'doctor'              => $doctor,
    'grupos'              => $grupos,
    'lab_config'          => $lab_config,
    'report_url'          => $report_url ?? '',
    'qr_data_uri'         => $qr_data_uri ?? '',
    'pdf_watermark_uri'   => null,
    'pdf_logo_data_uri'   => $pdf_logo_data_uri ?? null,
]) ?>
</body>
</html>
