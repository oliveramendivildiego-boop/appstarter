<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultados - <?= esc($paciente->first_name ?? '') ?> <?= esc($paciente->last_name_fa ?? '') ?></title>
    <base href="<?= base_url() ?>" />
    <?= view('registers/partials/report_pdf_theme_styles', [
        'pdf_layout'                     => $pdf_layout ?? [],
        'use_sheet_padding_for_margins' => false,
    ]) ?>
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
    'report_emitido_en'   => $report_emitido_en ?? \App\Services\RegisterService::formatNowForReport(),
    'pdf_watermark_uri'   => null,
    'pdf_logo_data_uri'   => $pdf_logo_data_uri ?? null,
    'report_pria_tipo_muestra_nombre' => $report_pria_tipo_muestra_nombre ?? [],
    'report_pria_metodo_nombre'       => $report_pria_metodo_nombre ?? [],
    'report_lab_firmas'               => $report_lab_firmas ?? [],
    'report_pria_refs_consolidada'    => $report_pria_refs_consolidada ?? [],
]) ?>
</body>
</html>
