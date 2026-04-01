<?= $this->extend('layouts/main') ?>

<?= $this->section('head_extra') ?>
<link rel="stylesheet" href="<?= base_url('css/vendor/flatpickr.min.css') ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_green.css">
<script src="<?= base_url('js/vendor/flatpickr.min.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/es.js"></script>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_reports'), 'url' => site_url('reports')],
    ['label' => $title ?? '', 'url' => null],
]]) ?>

<form method="get" action="<?= site_url('reports/estadisticasLaboratorio') ?>" class="row g-3 mb-4">
    <div class="col-auto">
        <label for="report_start" class="form-label">Desde</label>
        <input type="text" id="report_start" name="start" class="form-control flatpickr-input" value="<?= esc($startDate ?? '') ?>">
    </div>
    <div class="col-auto">
        <label for="report_end" class="form-label">Hasta</label>
        <input type="text" id="report_end" name="end" class="form-control flatpickr-input" value="<?= esc($endDate ?? '') ?>">
    </div>
    <div class="col-auto d-flex align-items-end">
        <button type="submit" class="btn btn-primary">Generar</button>
    </div>
</form>

<h4><?= esc($title ?? '') ?></h4>
<p class="text-muted mb-4"><?= esc($subtitle ?? '') ?></p>
<p class="text-muted small">Las <strong>pruebas realizadas</strong>, el desglose por prueba y el conteo de órdenes coinciden con <a href="<?= site_url('reports/pruebasFecha') ?>">Pruebas por fecha</a>: análisis en <code>registro.pruebas</code>, solo órdenes <strong>completas</strong> (hay filas en resultados), <strong>no anuladas ni eliminadas</strong>, con paciente, doctor y pago. Los nombres salen del catálogo cuando existen; si no, el ID. Los grupos poblacionales vienen de <a href="<?= site_url('config?tab=poblacion') ?>">Configuración → Población</a> según edad y sexo a la fecha de ingreso.</p>

<?php
$resumen = $resumen ?? [];
$porGeneroOrdenes = $porGeneroOrdenes ?? ['1' => 0, '2' => 0, '_' => 0];
$porGeneroPacientes = $porGeneroPacientes ?? ['1' => 0, '2' => 0, '_' => 0];
$poblacionGrupoRows = $poblacionGrupoRows ?? [];
$porPruebaRows = $porPruebaRows ?? [];
?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-primary h-100 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small mb-1">Pruebas realizadas</h6>
                <p class="display-6 mb-0"><?= (int) ($resumen['pruebas_realizadas'] ?? ($resumen['pruebas_completadas'] ?? ($resumen['pruebas_solicitadas'] ?? 0))) ?></p>
                <small class="text-muted">Total de análisis en las órdenes del período (igual que «Pruebas por fecha»)</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-success h-100 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small mb-1">Pacientes atendidos</h6>
                <p class="display-6 mb-0"><?= (int) ($resumen['pacientes_distintos'] ?? 0) ?></p>
                <small class="text-muted">Personas distintas (ficha en el sistema)</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small mb-1">Órdenes de laboratorio</h6>
                <p class="display-6 mb-0"><?= (int) ($resumen['ordenes'] ?? 0) ?></p>
                <?php if (($resumen['ordenes_sin_persona'] ?? 0) > 0): ?>
                <small class="text-warning"><?= (int) $resumen['ordenes_sin_persona'] ?> sin paciente vinculado (person_id 0)</small>
                <?php else: ?>
                <small class="text-muted">Registros no anulados con pago</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header"><strong>Desglose por prueba</strong> <span class="text-muted small">por análisis solicitado en la orden</span></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-striped table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="text-end" style="width:3rem">#</th>
                        <th>Prueba</th>
                        <th>Categoría</th>
                        <th class="text-end">Órdenes con la prueba</th>
                        <th class="text-end">Pacientes distintos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $n = 0;
                    $sumOrdenesPrueba = 0;
                    foreach ($porPruebaRows as $p):
                        $n++;
                        $sumOrdenesPrueba += (int) ($p['ordenes_con_prueba'] ?? 0);
                    ?>
                    <tr>
                        <td class="text-end text-muted"><?= $n ?></td>
                        <td><?= esc($p['prueba'] ?? '') ?></td>
                        <td class="text-muted small"><?= esc(($p['categoria'] ?? '') !== '' ? (string) $p['categoria'] : '—') ?></td>
                        <td class="text-end fw-semibold"><?= (int) ($p['ordenes_con_prueba'] ?? 0) ?></td>
                        <td class="text-end"><?= (int) ($p['pacientes_distintos'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($porPruebaRows)): ?>
                    <tr><td colspan="5" class="text-muted px-3 py-3">Sin análisis listados en órdenes del período.</td></tr>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($porPruebaRows)): ?>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="3" class="text-end"><strong>Totales</strong></td>
                        <td class="text-end"><strong><?= (int) $sumOrdenesPrueba ?></strong></td>
                        <td class="text-end text-muted small">—</td>
                    </tr>
                    <tr>
                        <td colspan="5" class="small text-muted py-2">
                            <?= count($porPruebaRows) ?> tipo(s) de prueba. La suma de la columna coincide con <strong><?= (int) ($resumen['pruebas_realizadas'] ?? ($resumen['pruebas_completadas'] ?? ($resumen['pruebas_solicitadas'] ?? 0))) ?></strong> (total pruebas realizadas).
                        </td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header">
                <strong>Por grupo poblacional</strong>
                <span class="text-muted small">(según <a href="<?= site_url('config?tab=poblacion') ?>" class="text-muted">Config → Población</a>; una orden cuenta en un solo grupo)</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Grupo</th>
                                <th>Criterio de edad</th>
                                <th class="text-end">Órdenes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($poblacionGrupoRows as $fila): ?>
                            <tr>
                                <td class="fw-medium"><?= esc($fila['nombre'] ?? '') ?></td>
                                <td class="text-muted small"><?= esc($fila['rango_edad'] ?? '') ?></td>
                                <td class="text-end"><?= (int) ($fila['ordenes'] ?? 0) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($poblacionGrupoRows)): ?>
                            <tr><td colspan="3" class="text-muted px-3 py-3">No hay grupos en configuración o sin datos en el período.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header"><strong>Por género</strong></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Género</th>
                                <th class="text-end">Pacientes únicos</th>
                                <th class="text-end">Órdenes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Masculino</td>
                                <td class="text-end"><?= (int) ($porGeneroPacientes['1'] ?? 0) ?></td>
                                <td class="text-end"><?= (int) ($porGeneroOrdenes['1'] ?? 0) ?></td>
                            </tr>
                            <tr>
                                <td>Femenino</td>
                                <td class="text-end"><?= (int) ($porGeneroPacientes['2'] ?? 0) ?></td>
                                <td class="text-end"><?= (int) ($porGeneroOrdenes['2'] ?? 0) ?></td>
                            </tr>
                            <tr>
                                <td>No indicado</td>
                                <td class="text-end"><?= (int) ($porGeneroPacientes['_'] ?? 0) ?></td>
                                <td class="text-end"><?= (int) ($porGeneroOrdenes['_'] ?? 0) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    flatpickr("#report_start", { dateFormat: "Y-m-d", locale: "es", onOpen: function(s,d,i){ flatpickrPositionArrowTopLeft(i); } });
    flatpickr("#report_end", { dateFormat: "Y-m-d", locale: "es", onOpen: function(s,d,i){ flatpickrPositionArrowTopLeft(i); } });
});
</script>
<?= $this->endSection() ?>
