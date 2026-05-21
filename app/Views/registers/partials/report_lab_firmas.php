<?php
$report_lab_firmas = $report_lab_firmas ?? [];
$report_lab_firmas = array_values(array_filter($report_lab_firmas, static function ($firma): bool {
    return is_array($firma) && trim((string) ($firma['prueba_nombre'] ?? '')) === '';
}));
if ($report_lab_firmas === []) {
    return;
}
$multi = count($report_lab_firmas) > 1;
$imgNone = 'border: none; outline: none; box-shadow: none; background: transparent;';
?>
<div class="card mt-3" id="report-lab-firmas">
    <div class="card-header"><strong>Validación y aprobación</strong></div>
    <div class="card-body">
        <?php foreach ($report_lab_firmas as $idx => $firma): ?>
            <?php if ($multi && trim((string) ($firma['prueba_nombre'] ?? '')) !== ''): ?>
                <div class="fw-semibold text-uppercase small text-muted border-bottom pb-1 mb-3"><?= esc($firma['prueba_nombre'] ?? '') ?></div>
            <?php endif; ?>
            <?php
            $seal = trim((string) ($firma['approver_seal'] ?? ''));
            $sig  = trim((string) ($firma['approver_signature'] ?? ''));
            $hasSealFile = $seal !== '' && is_file(FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $seal));
            $hasSigFile  = $sig !== '' && is_file(FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $sig));
            ?>
            <div class="row align-items-start <?= $multi && $idx < count($report_lab_firmas) - 1 ? 'mb-4 pb-4 border-bottom' : 'mb-0' ?> g-3">
                <div class="col-12 col-md-4">
                    <div class="small text-muted mb-1">Verificado por:</div>
                    <div><?= esc(($firma['validator_name'] ?? '') !== '' ? $firma['validator_name'] : '—') ?></div>
                </div>
                <div class="col-12 col-md-4 text-center">
                    <?php if ($hasSealFile): ?>
                        <img src="<?= base_url($seal) ?>" alt="" class="d-inline-block" style="max-height: 110px; max-width: 100%; <?= $imgNone ?>">
                    <?php else: ?>
                        <div class="text-muted small">—</div>
                    <?php endif; ?>
                </div>
                <div class="col-12 col-md-4">
                    <div class="small text-muted mb-1">ATENTAMENTE</div>
                    <?php if ($hasSigFile): ?>
                        <div class="mb-2">
                            <img src="<?= base_url($sig) ?>" alt="" class="d-block" style="max-height: 80px; max-width: 240px; <?= $imgNone ?>">
                        </div>
                    <?php endif; ?>
                    <div class="fw-semibold"><?= esc(($firma['approver_name'] ?? '') !== '' ? $firma['approver_name'] : '—') ?></div>
                    <?php $cargo = trim((string) ($firma['approver_cargo'] ?? '')); ?>
                    <?php if ($cargo !== ''): ?>
                        <div class="mt-2"><?= esc($cargo) ?></div>
                    <?php endif; ?>
                    <?php $matricula = trim((string) ($firma['approver_matricula'] ?? '')); ?>
                    <?php if ($matricula !== ''): ?>
                        <div class="mt-1"><span class="small text-muted">Matrícula:</span> <?= esc($matricula) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
