<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Insumos orden<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_registers'), 'url' => site_url('registers')],
    ['label' => 'Orden ' . registro_orden_display($register_info) . ' - Insumos consumidos', 'url' => null],
]]) ?>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Datos de la orden</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-1"><strong>No. Orden:</strong> <?= esc(registro_orden_display($register_info)) ?></p>
                <p class="mb-1"><strong>Paciente:</strong> <?= esc(trim(($paciente->first_name ?? '') . ' ' . ($paciente->last_name_fa ?? '') . ' ' . ($paciente->last_name_mom ?? '')) ?: '-') ?></p>
                <p class="mb-1"><strong>Fecha ingreso:</strong> <?= esc($register_info->ingreso ? \App\Services\RegisterService::formatStoredReporteFechaCorta((string) $register_info->ingreso) : '-') ?></p>
            </div>
            <div class="col-md-6">
                <?php if (!empty($doctor->report_sin_prefijo_medico ?? false)) : ?>
                <p class="mb-1"><strong>Médico:</strong> <?= esc($doctor->name ?? '-') ?></p>
                <?php else : ?>
                <?php $tituloMedico = ((int)($doctor->gender ?? 0) === 1) ? 'Dr.' : 'Dra.'; ?>
                <p class="mb-1"><strong>Médico:</strong> <?= $tituloMedico ?> <?= esc($doctor->name ?? '-') ?></p>
                <?php endif; ?>
                <p class="mb-1"><strong>Total orden:</strong> <?= format_currency((float)(($pago ?? null)?->total ?? 0)) ?></p>
                <p class="mb-0"><strong>Pruebas:</strong> <?= esc($register_info->pruebas ?? '-') ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Insumos consumidos en esta orden</strong>
        <a href="<?= site_url('registers/view/' . (int)($register_info->registro_id ?? 0)) ?>" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-pen me-1"></i>Editar orden
        </a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($insumos)): ?>
        <div class="p-4 text-muted">
            <i class="fas fa-info-circle me-2"></i>No hay insumos registrados para esta orden.
            Los consumos se vinculan al indicar el número de orden al <a href="<?= site_url('inventario') ?>">registrar salida</a> en Insumos.
        </div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table table-bordered table-striped mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Insumo / Reactivo</th>
                    <th class="text-end">Cantidad</th>
                    <th>Unidad</th>
                    <th>Fecha consumo</th>
                    <th>Responsable</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($insumos as $i): ?>
                <tr>
                    <td><?= esc($i['reactivo_nombre'] ?? '-') ?></td>
                    <td class="text-end"><?= number_format((float)($i['cantidad'] ?? 0), 4) ?></td>
                    <td><?= esc($i['unidad_base'] ?? $i['unidad'] ?? '-') ?></td>
                    <td><?= esc($i['fecha'] ? \App\Services\RegisterService::formatStoredReporteFechaCorta((string) $i['fecha']) : '-') ?></td>
                    <td><?= esc(trim($i['responsable'] ?? '') ?: '-') ?></td>
                    <td><?= esc($i['observaciones'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<p class="mt-3">
    <a href="<?= site_url('registers/lista') ?>" class="btn btn-secondary">Volver a lista</a>
    <a href="<?= site_url('registers/viewreport/' . (int)($register_info->registro_id ?? 0)) ?>" class="btn btn-outline-secondary ms-2" target="_blank">Ver reporte</a>
    <a href="<?= site_url('registers/pdf/' . (int)($register_info->registro_id ?? 0)) ?>" class="btn btn-success ms-2" target="_blank">Descargar PDF</a>
</p>
<?= $this->endSection() ?>
