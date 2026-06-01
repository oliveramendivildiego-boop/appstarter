<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="d-print-none">
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_reports'), 'url' => site_url('reports')],
    ['label' => $title ?? 'Cierres de pagos', 'url' => null],
]]) ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>
</div>

<?= view('reports/partials/report_actions', [
    'pdf_url' => site_url('reports/pagosCierresPdf'),
]) ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 d-print-none">
    <div>
        <h4 class="mb-0"><?= esc($title ?? '') ?></h4>
        <p class="text-muted small mb-0">Cada cierre guarda una copia del resumen del período. La impresión y el PDF usan esos datos congelados.</p>
    </div>
    <a href="<?= site_url('reports/pagos') ?>" class="btn btn-outline-primary btn-sm">Ir al reporte de pagos</a>
</div>

<div class="table-responsive">
    <table class="table table-sm table-bordered table-striped align-middle">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Período</th>
                <th>Registrado</th>
                <th>Elaborado por</th>
                <th class="text-end d-print-none">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cierres ?? [] as $c): ?>
                <?php
                $id = (int) ($c['cierre_id'] ?? 0);
                $fd = $c['fecha_desde'] ?? '';
                $fh = $c['fecha_hasta'] ?? '';
                $periodo = $fd && $fh
                    ? (lab_date($fd) . ' — ' . lab_date($fh))
                    : '-';
                $creado = !empty($c['created_at'])
                    ? \App\Services\RegisterService::formatStoredReporteFechaHora((string) $c['created_at'])
                    : '-';
                $por    = trim((string) ($c['elaborado_nombre'] ?? ''));
                ?>
                <tr>
                    <td><?= $id ?></td>
                    <td><?= esc($periodo) ?></td>
                    <td><?= esc($creado) ?></td>
                    <td><?= esc($por !== '' ? $por : '—') ?></td>
                    <td class="text-end text-nowrap d-print-none">
                        <a href="<?= site_url('reports/pagosCierre/' . $id) ?>" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">Imprimir</a>
                        <a href="<?= site_url('reports/pagosCierrePdf/' . $id) ?>" class="btn btn-sm btn-outline-danger">PDF</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if (empty($cierres)): ?>
    <p class="text-muted">Aún no hay cierres. En <a href="<?= site_url('reports/pagos') ?>">Reporte de pagos</a> filtre el período y pulse <strong>Registrar cierre</strong>.</p>
<?php endif; ?>

<?= $this->endSection() ?>
