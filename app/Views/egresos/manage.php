<?= $this->extend('layouts/main') ?>
<?= $this->section('head_extra') ?>
<link rel="stylesheet" href="<?= base_url('css/vendor/flatpickr.min.css') ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_green.css">
<script src="<?= base_url('js/vendor/flatpickr.min.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/es.js"></script>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_egresos'), 'url' => site_url('egresos')],
]]) ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-7">
        <div class="card mb-3">
            <div class="card-header"><strong>Listado de movimientos</strong></div>
            <div class="card-body">
                <form method="get" action="<?= site_url('egresos') ?>" class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label for="f_q" class="form-label">Buscar</label>
                        <input type="text" id="f_q" name="q" class="form-control" value="<?= esc((string) ($q ?? '')) ?>" placeholder="Desglose, monto, tipo pago o movimiento">
                    </div>
                    <div class="col-md-3">
                        <label for="f_start" class="form-label">Desde</label>
                        <input type="text" id="f_start" name="start" class="form-control flatpickr-input" value="<?= esc((string) ($startDate ?? '')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="f_end" class="form-label">Hasta</label>
                        <input type="text" id="f_end" name="end" class="form-control flatpickr-input" value="<?= esc((string) ($endDate ?? '')) ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary btn-sm" title="Filtrar" aria-label="Filtrar">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                        <a href="<?= site_url('egresos') ?>" class="btn btn-outline-secondary btn-sm" title="Limpiar filtros" aria-label="Limpiar filtros">
                            <i class="fa-solid fa-eraser"></i>
                        </a>
                    </div>
                </form>

                <?php if (empty($egresos ?? [])): ?>
                    <p class="text-muted mb-0">No hay movimientos registrados.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Movimiento</th>
                                    <th class="text-end">Monto</th>
                                    <th>Tipo pago</th>
                                    <th>Desglose</th>
                                    <th class="text-center" style="width:130px">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($egresos ?? []) as $e): ?>
                                    <tr>
                                        <td><?= esc(
                                            !empty($e['fecha'])
                                                ? \App\Services\RegisterService::formatStoredReporteFechaHora((string) $e['fecha'])
                                                : '—'
                                        ) ?></td>
                                        <td>
                                            <?php $tm = (string) ($e['tipo_movimiento'] ?? 'egreso'); ?>
                                            <span class="badge <?= $tm === 'ingreso' ? 'bg-success' : 'bg-danger' ?>">
                                                <?= esc($tipos_movimiento[$tm] ?? ucfirst($tm)) ?>
                                            </span>
                                        </td>
                                        <td class="text-end"><?= esc(number_format((float) ($e['monto'] ?? 0), 2, '.', ',')) ?></td>
                                        <td><?= esc($tipos_pago[(string) ($e['tipopago'] ?? '')] ?? ($e['tipopago'] ?? '-')) ?></td>
                                        <td><?= esc((string) ($e['desglose'] ?? '')) ?></td>
                                        <td class="text-center">
                                            <a href="<?= site_url('egresos?editar=' . (int) ($e['egreso_id'] ?? 0)) ?>" class="btn btn-sm btn-outline-primary" title="Editar"><i class="fa-solid fa-pen"></i></a>
                                            <a href="<?= site_url('egresos/delete/' . (int) ($e['egreso_id'] ?? 0)) ?>" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return uiConfirmLink(this, '¿Eliminar este egreso?');"><i class="fa-solid fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                <?php
                    $totalPages = (int) ($totalPages ?? 1);
                    $currentPage = (int) ($page ?? 1);
                    $queryBase = [
                        'q' => (string) ($q ?? ''),
                        'start' => (string) ($startDate ?? ''),
                        'end' => (string) ($endDate ?? ''),
                    ];
                ?>
                <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
                    <small class="text-muted">
                        Mostrando <?= count($egresos ?? []) ?> de <?= (int) ($total ?? 0) ?> registro(s)
                    </small>
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Paginación de egresos">
                            <ul class="pagination pagination-sm mb-0">
                                <?php $prev = max(1, $currentPage - 1); ?>
                                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= $currentPage <= 1 ? '#' : site_url('egresos?' . http_build_query(array_merge($queryBase, ['page' => $prev]))) ?>">Anterior</a>
                                </li>
                                <?php
                                    $startPage = max(1, $currentPage - 2);
                                    $endPage = min($totalPages, $currentPage + 2);
                                    for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                    <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= site_url('egresos?' . http_build_query(array_merge($queryBase, ['page' => $i]))) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <?php $next = min($totalPages, $currentPage + 1); ?>
                                <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= $currentPage >= $totalPages ? '#' : site_url('egresos?' . http_build_query(array_merge($queryBase, ['page' => $next]))) ?>">Siguiente</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><strong><?= !empty($egreso_editar) ? 'Editar movimiento' : 'Nuevo movimiento' ?></strong></div>
            <div class="card-body">
                <?= form_open(site_url('egresos/save')) ?>
                <input type="hidden" name="egreso_id" value="<?= (int) ($egreso_editar['egreso_id'] ?? 0) ?>">
                <div class="mb-3">
                    <label class="form-label">Fecha</label>
                    <input type="text" id="egreso_fecha" name="fecha" class="form-control flatpickr-input"
                           value="<?= esc(
                               !empty($egreso_editar['fecha'])
                                   ? lab_stored_form_datetime((string) $egreso_editar['fecha'])
                                   : lab_now_form_datetime()
                           ) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Monto</label>
                    <input type="number" name="monto" class="form-control" min="0.01" step="0.01" required
                           value="<?= esc((string) ($egreso_editar['monto'] ?? '')) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Tipo de movimiento</label>
                    <select name="tipo_movimiento" class="form-select" required>
                        <?php $tipoMovSel = (string) ($egreso_editar['tipo_movimiento'] ?? 'egreso'); ?>
                        <?php foreach (($tipos_movimiento ?? []) as $k => $lbl): ?>
                            <option value="<?= esc($k) ?>" <?= $tipoMovSel === (string) $k ? 'selected' : '' ?>>
                                <?= esc($lbl) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tipo de pago</label>
                    <select name="tipopago" class="form-select" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach (($tipos_pago ?? []) as $k => $lbl): ?>
                            <option value="<?= esc($k) ?>" <?= ((string) ($egreso_editar['tipopago'] ?? '') === (string) $k) ? 'selected' : '' ?>>
                                <?= esc($lbl) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Desglose del movimiento</label>
                    <textarea name="desglose" class="form-control" rows="5" required><?= esc((string) ($egreso_editar['desglose'] ?? '')) ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Guardar</button>
                <a href="<?= site_url('egresos') ?>" class="btn btn-secondary">Cancelar</a>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    flatpickr('#f_start', {
        dateFormat: 'Y-m-d',
        locale: 'es',
        onOpen: function (s, d, i) { flatpickrPositionArrowTopLeft(i); }
    });
    flatpickr('#f_end', {
        dateFormat: 'Y-m-d',
        locale: 'es',
        onOpen: function (s, d, i) { flatpickrPositionArrowTopLeft(i); }
    });
    flatpickr('#egreso_fecha', {
        enableTime: true,
        time_24hr: true,
        dateFormat: 'Y-m-d H:i',
        locale: 'es',
        onOpen: function (s, d, i) { flatpickrPositionArrowTopLeft(i); }
    });
});
</script>
<?= $this->endSection() ?>

