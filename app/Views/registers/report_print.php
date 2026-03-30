<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Imprimir resultados - <?= esc($paciente->first_name ?? '') ?> <?= esc($paciente->last_name_fa ?? '') ?></title>
    <base href="<?= base_url() ?>" />
    <link rel="stylesheet" href="<?= base_url('assets/css/report_pdf.css') ?>" />
    <link rel="stylesheet" href="<?= base_url('css/dom_print.css') ?>" media="print" />
    <?php
    $pl = is_array($pdf_layout ?? null) ? $pdf_layout : [];
    $mm = is_array($pl['margins_mm'] ?? null)
        ? $pl['margins_mm']
        : \App\Services\ReportPdfLayoutService::defaultMarginsMmStatic();
    $mt = (float) ($mm['top'] ?? 15);
    $mr = (float) ($mm['right'] ?? 15);
    $mb = (float) ($mm['bottom'] ?? 15);
    $ml = (float) ($mm['left'] ?? 15);
    $rid = (int) ($registro_id ?? 0);
    ?>
    <style>
        body { margin: <?= esc((string) $mt) ?>mm <?= esc((string) $mr) ?>mm <?= esc((string) $mb) ?>mm <?= esc((string) $ml) ?>mm !important; position: relative; background: #fff; }
        .report-print-toolbar { padding: 10px 12px; margin: -8px -8px 16px -8px; background: #f1f3f5; border-bottom: 1px solid #dee2e6; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .report-print-btn-primary { padding: 8px 16px; background: #0d6efd; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
        .report-print-btn-secondary { padding: 8px 16px; background: #fff; color: #333; border: 1px solid #ced4da; border-radius: 6px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
        @media print {
            .report-print-toolbar { display: none !important; }
        }
    </style>
</head>
<body>
<div class="report-print-toolbar">
    <button type="button" class="report-print-btn-primary" onclick="window.print()">Imprimir de nuevo</button>
    <?php if ($rid > 0): ?>
    <a href="<?= site_url('registers/viewreport/' . $rid) ?>" class="report-print-btn-secondary">Volver al reporte</a>
    <?php endif; ?>
</div>
<?= view('registers/pdf/report_document', [
    'pdf_layout'        => $pdf_layout ?? [],
    'register_info'     => $register_info,
    'paciente'          => $paciente,
    'doctor'            => $doctor,
    'grupos'            => $grupos,
    'lab_config'        => $lab_config,
    'report_url'        => $report_url ?? '',
    'qr_data_uri'       => $qr_data_uri ?? '',
    'pdf_watermark_uri' => null,
    'pdf_logo_data_uri' => null,
]) ?>
<script>
(function() {
    function openPrintDialog() {
        window.print();
    }
    window.addEventListener('afterprint', function() {
        setTimeout(function() {
            window.close();
        }, 150);
    });
    if (document.readyState === 'complete') {
        setTimeout(openPrintDialog, 350);
    } else {
        window.addEventListener('load', function() {
            setTimeout(openPrintDialog, 350);
        });
    }
})();
</script>
</body>
</html>
