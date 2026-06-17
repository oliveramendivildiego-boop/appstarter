<?= $this->extend('layouts/main') ?>

<?= $this->section('head_extra') ?>
<?= view('reports/analytics/partials/head_assets', ['with_charts' => true]) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
helper('registro');
$qs = array_filter([
    'start'               => $startDate ?? '',
    'end'                 => $endDate ?? '',
    'tipo_procesamiento'  => $tipoProcesamiento ?? '',
    'estado_pago'         => $estadoPago ?? '',
    'doctor_id'           => (int) ($doctorId ?? 0) ?: '',
    'institucion'         => $institucion ?? '',
], static fn ($v): bool => $v !== null && $v !== '');
$resumen    = $resumen ?? [];
$subtotales = $subtotales ?? [];
$detalle    = $detalle ?? [];
?>
<?= view('reports/analytics/partials/report_header', [
    'title'     => $title ?? '',
    'subtitle'  => $subtitle ?? '',
    'pdf_url'   => site_url('reports/ingresosPorTipoProcesamientoPdf?' . http_build_query($qs)),
    'excel_url' => site_url('reports/ingresosPorTipoProcesamientoExcel?' . http_build_query($qs)),
]) ?>

<form method="get" action="<?= site_url('reports/ingresosPorTipoProcesamiento') ?>" class="row g-3 mb-4 d-print-none">
    <div class="col-auto">
        <label for="report_start" class="form-label">Fecha inicio</label>
        <input type="text" id="report_start" name="start" class="form-control flatpickr-input" value="<?= esc($startDate ?? '') ?>">
    </div>
    <div class="col-auto">
        <label for="report_end" class="form-label">Fecha fin</label>
        <input type="text" id="report_end" name="end" class="form-control flatpickr-input" value="<?= esc($endDate ?? '') ?>">
    </div>
    <div class="col-auto">
        <label for="tipo_procesamiento" class="form-label">Tipo de procesamiento</label>
        <select id="tipo_procesamiento" name="tipo_procesamiento" class="form-select">
            <option value="">Todos</option>
            <option value="interno" <?= ($tipoProcesamiento ?? '') === 'interno' ? 'selected' : '' ?>>Interno</option>
            <option value="derivado" <?= ($tipoProcesamiento ?? '') === 'derivado' ? 'selected' : '' ?>>Derivado</option>
        </select>
    </div>
    <div class="col-auto">
        <label for="estado_pago" class="form-label">Estado de pago</label>
        <select id="estado_pago" name="estado_pago" class="form-select">
            <option value="">Todos</option>
            <option value="pagado" <?= ($estadoPago ?? '') === 'pagado' ? 'selected' : '' ?>>Pagado</option>
            <option value="pendiente" <?= ($estadoPago ?? '') === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
            <option value="parcial" <?= ($estadoPago ?? '') === 'parcial' ? 'selected' : '' ?>>Parcial</option>
        </select>
    </div>
    <div class="col-auto">
        <label for="doctor_id" class="form-label">Médico solicitante</label>
        <select id="doctor_id" name="doctor_id" class="form-select">
            <option value="0">Todos</option>
            <?php foreach ($doctores ?? [] as $d): ?>
                <option value="<?= (int) $d['doctor_id'] ?>" <?= (int) ($doctorId ?? 0) === (int) $d['doctor_id'] ? 'selected' : '' ?>><?= esc($d['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <label for="institucion" class="form-label">Convenio / Empresa</label>
        <select id="institucion" name="institucion" class="form-select">
            <option value="">Todos</option>
            <?php foreach ($instituciones ?? [] as $inst): ?>
                <option value="<?= esc($inst) ?>" <?= ($institucion ?? '') === $inst ? 'selected' : '' ?>><?= esc($inst) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto d-flex align-items-end">
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </div>
</form>

<p class="small text-muted">Órdenes recepcionadas en el período (fecha de ingreso). Cada fila es una prueba facturada. Las órdenes anuladas no se incluyen.</p>

<div class="alert alert-info mb-4">
    <div class="row g-2">
        <div class="col-md-3"><strong>Total de pruebas:</strong> <?= (int) ($resumen['total_pruebas'] ?? 0) ?></div>
        <div class="col-md-3"><strong>Total facturado:</strong> <?= format_currency((float) ($resumen['total_facturado'] ?? 0)) ?></div>
        <div class="col-md-3"><strong>Total cobrado:</strong> <?= format_currency((float) ($resumen['total_cobrado'] ?? 0)) ?></div>
        <div class="col-md-3"><strong>Total pendiente:</strong> <span class="text-danger"><?= format_currency((float) ($resumen['total_pendiente'] ?? 0)) ?></span></div>
        <div class="col-md-3"><strong>Pruebas internas:</strong> <?= (int) ($resumen['total_pruebas_internas'] ?? 0) ?></div>
        <div class="col-md-3"><strong>Pruebas derivadas:</strong> <?= (int) ($resumen['total_pruebas_derivadas'] ?? 0) ?></div>
        <div class="col-md-3"><strong>% pruebas derivadas:</strong> <?= number_format((float) ($resumen['pct_pruebas_derivadas'] ?? 0), 2) ?>%</div>
        <div class="col-md-3"><strong>% ingresos derivados:</strong> <?= number_format((float) ($resumen['pct_ingresos_derivados'] ?? 0), 2) ?>%</div>
    </div>
</div>

<?php if (! empty($detalle)): ?>
<div class="row d-print-none mb-4">
    <div class="col-lg-4 mb-3">
        <div class="card h-100">
            <div class="card-header"><strong>Distribución de ingresos</strong></div>
            <div class="card-body"><canvas id="chartIngresos" height="160"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4 mb-3">
        <div class="card h-100">
            <div class="card-header"><strong>Cantidad de pruebas</strong></div>
            <div class="card-body"><canvas id="chartCantidad" height="160"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4 mb-3">
        <div class="card h-100">
            <div class="card-header"><strong>Evolución mensual de ingresos</strong></div>
            <div class="card-body"><canvas id="chartEvolucion" height="160"></canvas></div>
        </div>
    </div>
</div>

<?php if (! empty($top_derivadas)): ?>
<h5 class="mt-2">Top 10 pruebas más derivadas</h5>
<div class="table-responsive mb-4">
    <table class="table table-bordered table-sm">
        <thead class="table-secondary">
            <tr>
                <th>#</th>
                <th>Prueba</th>
                <th class="text-end">Cantidad</th>
                <th class="text-end">% del total derivado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($top_derivadas as $row): ?>
            <tr>
                <td><?= (int) ($row['ranking'] ?? 0) ?></td>
                <td><?= esc($row['prueba'] ?? '') ?></td>
                <td class="text-end"><?= (int) ($row['cantidad'] ?? 0) ?></td>
                <td class="text-end"><?= number_format((float) ($row['porcentaje'] ?? 0), 2) ?>%</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
<?php endif; ?>

<?= view('reports/analytics/partials/table_tools', ['table_id' => 'tabla-ingresos-tipo']) ?>

<?php
$grupos = ['interno' => 'Interno', 'derivado' => 'Derivado'];
$lineasPorGrupo = ['interno' => [], 'derivado' => []];
foreach ($detalle as $line) {
    $tipo = (string) ($line['tipo_procesamiento'] ?? 'interno');
    if (! isset($lineasPorGrupo[$tipo])) {
        $lineasPorGrupo[$tipo] = [];
    }
    $lineasPorGrupo[$tipo][] = $line;
}
?>

<div class="table-responsive">
    <table class="table table-bordered table-striped" id="tabla-ingresos-tipo">
        <thead class="table-dark">
            <tr>
                <th>Fecha</th>
                <th>Código de orden</th>
                <th>Paciente</th>
                <th>Prueba</th>
                <th>Tipo procesamiento</th>
                <th>Laboratorio derivado</th>
                <th class="text-end">Importe</th>
                <th class="text-end">Monto cobrado</th>
                <th class="text-end">Saldo pendiente</th>
                <th>Estado de pago</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($detalle)): ?>
            <tr><td colspan="10" class="text-muted text-center">No hay datos en el período seleccionado.</td></tr>
            <?php else: ?>
                <?php foreach ($grupos as $tipoKey => $tipoLabel): ?>
                    <?php $lineas = $lineasPorGrupo[$tipoKey] ?? []; ?>
                    <?php if ($lineas === []) { continue; } ?>
                    <tr class="table-group-divider">
                        <td colspan="10" class="fw-bold bg-light"><?= esc($tipoLabel) ?></td>
                    </tr>
                    <?php foreach ($lineas as $line): ?>
                    <?php
                    $codigo = trim((string) ($line['codigo_orden'] ?? ''));
                    if ($codigo === '' && ! empty($line['registro_id'])) {
                        $codigo = registro_codigo_recepcion_display($line, false);
                    }
                    ?>
                    <tr>
                        <td><?= esc(\App\Services\RegisterService::formatReportDate($line['fecha'] ?? '')) ?></td>
                        <td><strong><?= esc($codigo !== '' ? $codigo : '—') ?></strong></td>
                        <td><?= esc($line['paciente'] ?? '') ?></td>
                        <td><?= esc($line['prueba'] ?? '') ?></td>
                        <td><?= esc($line['tipo_label'] ?? '') ?></td>
                        <td><?= esc($line['laboratorio_derivado'] ?? '—') ?></td>
                        <td class="text-end"><?= format_currency((float) ($line['importe'] ?? 0)) ?></td>
                        <td class="text-end"><?= format_currency((float) ($line['monto_cobrado'] ?? 0)) ?></td>
                        <td class="text-end"><?= format_currency((float) ($line['saldo_pendiente'] ?? 0)) ?></td>
                        <td>
                            <?php
                            $estado = (string) ($line['estado_pago'] ?? '');
                            $badge = match ($estado) {
                                'pagado'    => 'bg-success',
                                'pendiente' => 'bg-danger',
                                default     => 'bg-warning text-dark',
                            };
                            ?>
                            <span class="badge <?= $badge ?>"><?= esc($line['estado_label'] ?? '') ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php $st = $subtotales[$tipoKey] ?? []; ?>
                    <tr class="table-secondary fw-semibold">
                        <td colspan="6" class="text-end">Subtotal <?= esc($tipoLabel) ?></td>
                        <td class="text-end"><?= format_currency((float) ($st['importe'] ?? 0)) ?></td>
                        <td class="text-end"><?= format_currency((float) ($st['cobrado'] ?? 0)) ?></td>
                        <td class="text-end"><?= format_currency((float) ($st['pendiente'] ?? 0)) ?></td>
                        <td class="text-end"><?= (int) ($st['cantidad'] ?? 0) ?> prueba(s)</td>
                    </tr>
                <?php endforeach; ?>
                <tr class="table-dark fw-bold">
                    <td colspan="6" class="text-end">TOTAL GENERAL</td>
                    <td class="text-end"><?= format_currency((float) ($resumen['total_facturado'] ?? 0)) ?></td>
                    <td class="text-end"><?= format_currency((float) ($resumen['total_cobrado'] ?? 0)) ?></td>
                    <td class="text-end"><?= format_currency((float) ($resumen['total_pendiente'] ?? 0)) ?></td>
                    <td class="text-end"><?= (int) ($resumen['total_pruebas'] ?? 0) ?> prueba(s)</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?= view('reports/analytics/partials/table_pagination', ['table_id' => 'tabla-ingresos-tipo']) ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/analytics_table.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var fpOpts = { dateFormat: "Y-m-d", locale: "es" };
    if (typeof flatpickrPositionArrowTopLeft === 'function') {
        fpOpts.onOpen = function (s, d, i) { flatpickrPositionArrowTopLeft(i); };
    }
    flatpickr("#report_start", fpOpts);
    flatpickr("#report_end", fpOpts);

    if (typeof Chart === 'undefined') { return; }

    var ingresos = <?= json_encode($chart_ingresos ?? ['interno' => 0, 'derivado' => 0]) ?>;
    var cantidad = <?= json_encode($chart_cantidad ?? ['interno' => 0, 'derivado' => 0]) ?>;
    var evolucion = <?= json_encode($evolucion_mensual ?? []) ?>;

    var elIng = document.getElementById('chartIngresos');
    if (elIng) {
        new Chart(elIng, {
            type: 'doughnut',
            data: {
                labels: ['Interno', 'Derivado'],
                datasets: [{ data: [ingresos.interno || 0, ingresos.derivado || 0], backgroundColor: ['#0d6efd', '#fd7e14'] }]
            },
            options: { plugins: { legend: { position: 'bottom' } } }
        });
    }

    var elCant = document.getElementById('chartCantidad');
    if (elCant) {
        new Chart(elCant, {
            type: 'bar',
            data: {
                labels: ['Interno', 'Derivado'],
                datasets: [{ label: 'Pruebas', data: [cantidad.interno || 0, cantidad.derivado || 0], backgroundColor: ['#198754', '#fd7e14'] }]
            },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
        });
    }

    var elEvo = document.getElementById('chartEvolucion');
    if (elEvo && evolucion.length) {
        new Chart(elEvo, {
            type: 'line',
            data: {
                labels: evolucion.map(function (r) { return r.mes; }),
                datasets: [
                    { label: 'Interno', data: evolucion.map(function (r) { return r.interno_importe; }), borderColor: '#0d6efd', tension: 0.2 },
                    { label: 'Derivado', data: evolucion.map(function (r) { return r.derivado_importe; }), borderColor: '#fd7e14', tension: 0.2 }
                ]
            },
            options: { plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
        });
    }
});
</script>
<?= $this->endSection() ?>
