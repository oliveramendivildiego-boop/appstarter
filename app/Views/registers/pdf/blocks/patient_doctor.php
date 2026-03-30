<?php
declare(strict_types=1);

$layout = is_array($pdf_layout ?? null) ? $pdf_layout : [];
if (empty($layout['instances']) || ! is_array($layout['instances'])) {
    $layout = (new \App\Services\ReportPdfLayoutService())->getDefaultLayout();
}

[$n, $buckets] = \App\Services\ReportPdfLayoutService::columnBucketsForSection($layout, 'patient_doctor');
$n             = max(1, $n);

$elementCtx = [
    'lab_config'        => $lab_config ?? [],
    'paciente'          => $paciente ?? null,
    'doctor'            => $doctor ?? null,
    'register_info'     => $register_info ?? null,
    'report_url'        => $report_url ?? '',
    'qr_data_uri'       => $qr_data_uri ?? '',
    'pdf_logo_data_uri' => $pdf_logo_data_uri ?? '',
];
?>
<div class="patient-section">
    <div class="patient-columns">
        <?php for ($c = 0; $c < $n; $c++):
            $pct   = round(100 / $n, 4);
            $align = \App\Services\ReportPdfLayoutService::columnAlign($c, $n);
            ?>
        <div class="patient-col patient-col-<?= $c ?>" style="width: <?= $pct ?>%; text-align: <?= esc($align) ?>;">
            <?php foreach ($buckets[$c] as $elType):
                echo view('registers/pdf/partials/element', array_merge($elementCtx, [
                    'pdf_element_type' => $elType,
                ]));
            endforeach; ?>
        </div>
        <?php endfor; ?>
    </div>
</div>
