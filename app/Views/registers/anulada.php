<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Orden anulada<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
helper('registro');
$reg = $historial['registro'] ?? null;
$pago = $historial['pago'] ?? null;
$pruebas = $historial['pruebas'] ?? [];
$abonos = $historial['abonos'] ?? [];
$rid = (int) ($reg->registro_id ?? 0);
?>
<?= view('partial/breadcrumb_nav', [
    'items' => [
        ['label' => lang('Module.module_registers'), 'url' => site_url('registers/lista')],
        ['label' => 'Orden anulada', 'url' => null],
    ],
    'right' => '<a href="' . site_url('registers/lista') . '" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-left me-1"></i>Volver a la lista</a>',
]) ?>

<div class="alert alert-warning d-flex align-items-start gap-2">
    <i class="fa-solid fa-lock fa-lg mt-1"></i>
    <div>
        <strong>Orden anulada</strong> — Solo consulta. No puede editarse, cargarse resultados, pagos ni consumo de inventario asociado a esta orden.
    </div>
</div>

<div class="card mb-3 border-danger border-opacity-50">
    <div class="card-header bg-danger bg-opacity-10">
        <h5 class="mb-0"><i class="fa-solid fa-comment-dots me-2"></i>Motivo de la anulación</h5>
    </div>
    <div class="card-body">
        <p class="mb-2"><?= nl2br(esc($info->motivo_anulacion ?? '—')) ?></p>
        <p class="text-muted small mb-0">
            <?php if (!empty($info->fecha_anulacion)): ?>
                Fecha: <?= esc(date('d/m/Y H:i', strtotime((string) $info->fecha_anulacion))) ?>
            <?php endif; ?>
            <?php if (!empty($nombre_anulo)): ?>
                &nbsp;·&nbsp; Registró: <?= esc($nombre_anulo) ?>
            <?php endif; ?>
        </p>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-3">
        <div class="card h-100">
            <div class="card-header"><strong>Datos de la orden</strong></div>
            <div class="card-body">
                <p class="mb-1"><span class="text-muted">N.º orden:</span> <?= esc(registro_orden_display($reg)) ?></p>
                <p class="mb-1"><span class="text-muted">Paciente:</span> <?= esc($reg->paciente ?? '—') ?></p>
                <p class="mb-1"><span class="text-muted">Médico:</span> <?= esc($reg->doctor ?? '—') ?></p>
                <p class="mb-0"><span class="text-muted">Ingreso:</span> <?= esc($reg->ingreso ? date('d/m/Y H:i', strtotime((string) $reg->ingreso)) : '—') ?></p>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-3">
        <div class="card h-100">
            <div class="card-header"><strong>Pagos (referencia)</strong></div>
            <div class="card-body">
                <?php if ($pago): ?>
                    <p class="mb-1"><span class="text-muted">Total orden:</span> <?= esc($pago->total ?? '0') ?> <?= esc(currency_symbol()) ?></p>
                    <p class="mb-1"><span class="text-muted">Pagado:</span> <?= esc($pago->monto_pagar ?? '0') ?> <?= esc(currency_symbol()) ?></p>
                    <p class="mb-2"><span class="text-muted">Saldo:</span> <?= esc($pago->saldo ?? '0') ?> <?= esc(currency_symbol()) ?></p>
                <?php else: ?>
                    <p class="text-muted mb-2">Sin registro de pago.</p>
                <?php endif; ?>
                <?php if (!empty($abonos)): ?>
                    <h6 class="small text-uppercase text-muted">Abonos</h6>
                    <ul class="list-group list-group-flush small">
                        <?php foreach ($abonos as $a): ?>
                            <li class="list-group-item px-0 d-flex justify-content-between">
                                <span><?= esc($a['fecha_abono'] ?? '') ?></span>
                                <span><?= esc($a['monto'] ?? '0') ?> <?= esc(currency_symbol()) ?> — <?= esc($a['tipo_nombre'] ?? '') ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><strong>Pruebas solicitadas en la orden</strong></div>
    <div class="card-body">
        <?php if (!empty($pruebas)): ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($pruebas as $p): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <span><?= esc($p['nombre'] ?? '-') ?></span>
                        <?php if (!empty($p['categoria'])): ?>
                            <span class="text-muted small"><?= esc($p['categoria']) ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="text-muted small mt-2 mb-0">
                Resultados cargados: <?= (int) ($historial['regvalues_count'] ?? 0) ?> valor(es) — la orden permanece anulada y no opera en el flujo del laboratorio.
            </p>
        <?php else: ?>
            <p class="text-muted mb-0">No hay pruebas registradas en esta orden.</p>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><strong>Insumos / reactivos registrados como salida de esta orden</strong></div>
    <div class="card-body p-0">
        <?php if (!empty($insumos)): ?>
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead><tr><th>Fecha</th><th>Insumo</th><th>Cantidad</th><th>Responsable</th><th>Obs.</th></tr></thead>
                    <tbody>
                        <?php foreach ($insumos as $m): ?>
                            <tr>
                                <td><?= esc($m['fecha'] ?? '') ?></td>
                                <td><?= esc($m['reactivo_nombre'] ?? '') ?></td>
                                <td><?= esc((string) ($m['cantidad'] ?? '')) ?> <?= esc($m['unidad'] ?? $m['unidad_base'] ?? '') ?></td>
                                <td><?= esc(trim($m['responsable'] ?? '')) ?></td>
                                <td class="small"><?= esc($m['observaciones'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted p-3 mb-0">No hay movimientos de inventario vinculados a esta orden.</p>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
