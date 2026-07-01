<?= $this->extend('layouts/main') ?>

<?= $this->section('head_extra') ?>
<?= view('reports/analytics/partials/head_assets', ['with_charts' => true]) ?>
<style>
.snis-kpi-card { border-left: 4px solid var(--bs-primary); transition: box-shadow .15s ease; }
.snis-kpi-card:hover { box-shadow: 0 .25rem .75rem rgba(0,0,0,.08)!important; }
.snis-kpi-card.kpi-info { border-left-color: var(--bs-info); }
.snis-kpi-card.kpi-success { border-left-color: var(--bs-success); }
.snis-kpi-card.kpi-warning { border-left-color: var(--bs-warning); }
.snis-kpi-card.kpi-danger { border-left-color: var(--bs-danger); }
.snis-section-title { font-size: 1.05rem; font-weight: 600; margin-bottom: 1rem; padding-bottom: .35rem; border-bottom: 2px solid var(--bs-primary); }
.chart-wrap { position: relative; min-height: 220px; }
#tabla-estadisticas-prueba .est-sort-btn { font-size: .75rem; line-height: 1.2; min-width: 1.6rem; }
#tabla-estadisticas-prueba .est-sort-btn.active { font-weight: bold; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$qs = http_build_query(['start' => $startDate ?? '', 'end' => $endDate ?? '']);
$snis = $snis ?? [];
$kpis = $snis['kpis'] ?? [];
$charts = $snis['charts'] ?? [];
$indicadores = $snis['indicadores'] ?? [];
$ejecutivo = $snis['resumen_ejecutivo'] ?? [];
$limitaciones = $snis['limitaciones'] ?? [];
$resumen = $resumen ?? [];
$porGeneroOrdenes = $porGeneroOrdenes ?? ['1' => 0, '2' => 0, '_' => 0];
$porGeneroPacientes = $porGeneroPacientes ?? ['1' => 0, '2' => 0, '_' => 0];
$poblacionGrupoRows = $poblacionGrupoRows ?? [];
$porPruebaRows = $porPruebaRows ?? [];
$pruebasSolicitadas = (int) ($resumen['pruebas_solicitadas'] ?? ($resumen['pruebas_realizadas'] ?? 0));
$pruebasProcesadas  = (int) ($resumen['pruebas_procesadas'] ?? 0);
?>

<?= view('reports/analytics/partials/report_header', [
    'title'     => $title ?? 'Tablero SNIS — Estadísticas de laboratorio',
    'subtitle'  => $subtitle ?? '',
    'pdf_url'   => $pdf_url ?? site_url('reports/estadisticasLaboratorioPdf?' . $qs),
    'excel_url' => $excel_url ?? site_url('reports/estadisticasLaboratorioExcel?' . $qs),
]) ?>
<?php if (! empty($csv_url)): ?>
<div class="d-print-none mb-3">
    <a href="<?= esc($csv_url) ?>" class="btn btn-outline-secondary btn-sm" title="Exportar CSV">
        <i class="fas fa-file-csv me-1"></i> CSV
    </a>
</div>
<?php endif; ?>

<form method="get" action="<?= site_url('reports/estadisticasLaboratorio') ?>" class="row g-3 mb-4 d-print-none">
    <div class="col-auto">
        <label for="report_start" class="form-label">Desde</label>
        <input type="text" id="report_start" name="start" class="form-control flatpickr-input" value="<?= esc($startDate ?? '') ?>">
    </div>
    <div class="col-auto">
        <label for="report_end" class="form-label">Hasta</label>
        <input type="text" id="report_end" name="end" class="form-control flatpickr-input" value="<?= esc($endDate ?? '') ?>">
    </div>
    <div class="col-auto d-flex align-items-end">
        <button type="submit" class="btn btn-primary">Generar tablero</button>
    </div>
</form>

<p class="text-muted small d-print-none mb-4">
    Tablero orientado al <strong>Sistema Nacional de Información en Salud (SNIS)</strong> de Bolivia.
    Misma lógica de conteo que Historial por prueba y Pruebas por grupo.
    Solo pruebas <strong>activas</strong> en catálogo. Órdenes solicitadas = no anuladas con análisis en el período;
    procesadas = con resultado en <code>regvalues</code>.
</p>

<?php if (! empty($limitaciones)): ?>
<div class="alert alert-light border small d-print-none mb-4">
    <strong class="d-block mb-1"><i class="fas fa-info-circle text-muted me-1"></i> Limitaciones de datos</strong>
    <ul class="mb-0 ps-3">
        <?php foreach ($limitaciones as $lim): ?>
        <li><?= esc($lim['mensaje'] ?? '') ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- Resumen ejecutivo -->
<div class="card shadow-sm mb-4 border-primary">
    <div class="card-header bg-primary text-white">
        <i class="fas fa-clipboard-list me-1"></i> Resumen ejecutivo
    </div>
    <div class="card-body">
        <p class="lead mb-3"><?= esc($ejecutivo['productividad'] ?? '') ?></p>
        <ul class="mb-0">
            <?php foreach ($ejecutivo['bullets'] ?? [] as $bullet): ?>
            <li class="mb-1"><?= esc($bullet) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<!-- KPIs SNIS -->
<div class="snis-section-title d-print-none"><i class="fas fa-tachometer-alt me-1"></i> Indicadores clave (KPI)</div>
<div class="row g-3 mb-4">
    <?php
    $kpiCards = [
        ['label' => 'Pacientes atendidos', 'val' => $kpis['pacientes_atendidos'] ?? 0, 'hint' => 'Personas distintas', 'cls' => 'kpi-success'],
        ['label' => 'Órdenes solicitadas', 'val' => $kpis['ordenes_solicitadas'] ?? 0, 'hint' => 'No anuladas', 'cls' => ''],
        ['label' => 'Órdenes procesadas', 'val' => $kpis['ordenes_procesadas'] ?? 0, 'hint' => 'Con resultado', 'cls' => 'kpi-info'],
        ['label' => 'Pruebas solicitadas', 'val' => $kpis['pruebas_solicitadas'] ?? $pruebasSolicitadas, 'hint' => 'Análisis en órdenes', 'cls' => ''],
        ['label' => 'Pruebas procesadas', 'val' => $kpis['pruebas_procesadas'] ?? $pruebasProcesadas, 'hint' => 'Con resultado', 'cls' => 'kpi-info'],
        ['label' => '% procesamiento', 'val' => isset($kpis['porcentaje_procesamiento']) ? $kpis['porcentaje_procesamiento'] . '%' : '—', 'hint' => 'Proc. / solicitadas', 'cls' => 'kpi-warning'],
        ['label' => 'Prom. pruebas/orden', 'val' => $kpis['promedio_pruebas_orden'] ?? '—', 'hint' => 'Carga por orden', 'cls' => ''],
        ['label' => 'Prom. pruebas/paciente', 'val' => $kpis['promedio_pruebas_paciente'] ?? '—', 'hint' => 'Por persona', 'cls' => ''],
        ['label' => 'Prom. diario pruebas', 'val' => $kpis['promedio_diario_pruebas'] ?? '—', 'hint' => (int)($kpis['dias_periodo'] ?? 1) . ' días', 'cls' => ''],
        ['label' => 'Tipos de prueba', 'val' => $kpis['tipos_prueba_distintos'] ?? count($porPruebaRows), 'hint' => 'Catálogo activo', 'cls' => ''],
    ];
    foreach ($kpiCards as $card):
    ?>
    <div class="col-6 col-md-4 col-xl">
        <div class="card h-100 shadow-sm snis-kpi-card <?= esc($card['cls']) ?>">
            <div class="card-body py-3">
                <div class="text-muted text-uppercase small mb-1"><?= esc($card['label']) ?></div>
                <div class="fs-3 fw-bold mb-0"><?= esc((string) $card['val']) ?></div>
                <small class="text-muted"><?= esc($card['hint']) ?></small>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Indicadores operativos -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card h-100 border-warning shadow-sm">
            <div class="card-body text-center">
                <div class="text-muted small">Órdenes pendientes</div>
                <div class="fs-2 fw-bold text-warning"><?= (int) ($indicadores['ordenes_pendientes'] ?? 0) ?></div>
                <small class="text-muted">Sin validar</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 border-danger shadow-sm">
            <div class="card-body text-center">
                <div class="text-muted small">Órdenes anuladas</div>
                <div class="fs-2 fw-bold text-danger"><?= (int) ($indicadores['ordenes_anuladas'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 shadow-sm">
            <div class="card-body text-center">
                <div class="text-muted small">Resultados corregidos</div>
                <div class="fs-2 fw-bold"><?= (int) ($indicadores['resultados_corregidos'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 border-success shadow-sm">
            <div class="card-body text-center">
                <div class="text-muted small">Resultados validados</div>
                <div class="fs-2 fw-bold text-success"><?= (int) ($indicadores['resultados_validados'] ?? 0) ?></div>
            </div>
        </div>
    </div>
</div>

<?php
$tat = $indicadores['tat'] ?? [];
$tatMuestra = $indicadores['tat_muestra'] ?? null;
if (! empty($tat['promedio_resultado']) || ! empty($tat['promedio_validacion']) || $tatMuestra):
?>
<div class="card shadow-sm mb-4 d-print-none">
    <div class="card-header"><strong>Tiempos de respuesta (promedio)</strong></div>
    <div class="card-body">
        <div class="row g-3">
            <?php if (! empty($tat['promedio_resultado'])): ?>
            <div class="col-md-3"><span class="text-muted small d-block">Ingreso → resultado</span><strong><?= esc((string) $tat['promedio_resultado']) ?> h</strong></div>
            <?php endif; ?>
            <?php if (! empty($tat['promedio_validacion'])): ?>
            <div class="col-md-3"><span class="text-muted small d-block">Ingreso → validación</span><strong><?= esc((string) $tat['promedio_validacion']) ?> h</strong></div>
            <?php endif; ?>
            <?php if ($tatMuestra && ! empty($tatMuestra['promedio_recepcion_h'])): ?>
            <div class="col-md-3"><span class="text-muted small d-block">Ingreso → recepción muestra</span><strong><?= esc((string) $tatMuestra['promedio_recepcion_h']) ?> h</strong></div>
            <?php endif; ?>
            <?php if ($tatMuestra && ! empty($tatMuestra['promedio_procesamiento_h'])): ?>
            <div class="col-md-3"><span class="text-muted small d-block">Recepción → procesamiento muestra</span><strong><?= esc((string) $tatMuestra['promedio_procesamiento_h']) ?> h</strong></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Gráficos -->
<div class="snis-section-title d-print-none"><i class="fas fa-chart-area me-1"></i> Visualización gráfica</div>
<div class="row g-3 mb-4 d-print-none">
    <div class="col-lg-8">
        <div class="card h-100 shadow-sm">
            <div class="card-header"><strong>Evolución diaria</strong> <span class="text-muted small">órdenes y pruebas</span></div>
            <div class="card-body chart-wrap"><canvas id="chartEvolucion" height="120"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100 shadow-sm">
            <div class="card-header"><strong>Procesadas vs pendientes</strong></div>
            <div class="card-body chart-wrap d-flex align-items-center justify-content-center"><canvas id="chartPendientes" height="160"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm">
            <div class="card-header"><strong>Top 10 pruebas solicitadas</strong></div>
            <div class="card-body chart-wrap"><canvas id="chartTop10" height="140"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm">
            <div class="card-header"><strong>Producción por categoría</strong></div>
            <div class="card-body chart-wrap"><canvas id="chartCategoria" height="140"></canvas></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-header"><strong>Distribución por sexo</strong></div>
            <div class="card-body chart-wrap"><canvas id="chartSexo" height="160"></canvas></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-header"><strong>Grupos etarios</strong></div>
            <div class="card-body chart-wrap"><canvas id="chartEdad" height="160"></canvas></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-header"><strong>Comparación período anterior</strong></div>
            <div class="card-body chart-wrap">
                <?php if (! empty($charts['comparacion']['disponible'])): ?>
                <canvas id="chartComparacion" height="160"></canvas>
                <?php else: ?>
                <p class="text-muted small mb-0 py-4 text-center">Sin datos en el período anterior comparable.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php $micro = $indicadores['microbiologia'] ?? null; if ($micro): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header"><strong>Microbiología — positividad</strong> <span class="text-muted small"><?= esc(implode(', ', $micro['grupos'] ?? [])) ?></span></div>
    <div class="card-body">
        <p class="mb-3">Total resultados: <strong><?= (int) $micro['total'] ?></strong> · Positivos: <strong><?= (int) $micro['positivos'] ?></strong> · Tasa: <strong><?= esc((string) ($micro['porcentaje'] ?? '')) ?>%</strong></p>
        <?php if (! empty($micro['por_prueba'])): ?>
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead><tr><th>Prueba</th><th class="text-end">Total</th><th class="text-end">Positivos</th><th class="text-end">%</th></tr></thead>
                <tbody>
                <?php foreach ($micro['por_prueba'] as $mp): ?>
                <tr>
                    <td><?= esc($mp['prueba'] ?? '') ?></td>
                    <td class="text-end"><?= (int) ($mp['total'] ?? 0) ?></td>
                    <td class="text-end"><?= (int) ($mp['positivos'] ?? 0) ?></td>
                    <td class="text-end"><?= esc((string) ($mp['porcentaje'] ?? '')) ?>%</td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Tablas producción -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header"><strong>Producción por categoría</strong></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light"><tr><th>Categoría</th><th class="text-end">Solicitadas</th><th class="text-end">Procesadas</th></tr></thead>
                        <tbody>
                        <?php foreach ($indicadores['por_categoria'] ?? [] as $cat): ?>
                        <tr>
                            <td><?= esc($cat['categoria'] ?? '') ?></td>
                            <td class="text-end"><?= (int) ($cat['solicitadas'] ?? 0) ?></td>
                            <td class="text-end"><?= (int) ($cat['procesadas'] ?? 0) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header"><strong>Producción por médico solicitante</strong></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light"><tr><th>Médico</th><th class="text-end">Órdenes</th><th class="text-end">Pruebas</th></tr></thead>
                        <tbody>
                        <?php foreach ($indicadores['por_medico'] ?? [] as $med): ?>
                        <tr>
                            <td><?= esc($med['doctor'] ?? '') ?></td>
                            <td class="text-end"><?= (int) ($med['ordenes'] ?? 0) ?></td>
                            <td class="text-end"><?= (int) ($med['pruebas'] ?? 0) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (! empty($indicadores['por_institucion'])): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header"><strong>Procedencia / institución del paciente</strong></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
                <thead class="table-light"><tr><th>Institución</th><th class="text-end">Órdenes</th><th class="text-end">Pruebas</th></tr></thead>
                <tbody>
                <?php foreach ($indicadores['por_institucion'] as $inst): ?>
                <tr>
                    <td><?= esc($inst['institucion'] ?? '') ?></td>
                    <td class="text-end"><?= (int) ($inst['ordenes'] ?? 0) ?></td>
                    <td class="text-end"><?= (int) ($inst['pruebas'] ?? 0) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Desglose por prueba (existente) -->
<div class="card shadow-sm mb-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <strong>Desglose por prueba</strong>
        <span class="text-muted small d-print-none">Use ⇅ en cada columna para ordenar</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-striped table-hover mb-0" id="tabla-estadisticas-prueba">
                <thead class="table-dark">
                    <tr>
                        <th class="text-end" style="width:3rem" data-nosort>#</th>
                        <th><span class="d-inline-flex align-items-center gap-1 w-100"><span class="est-th-label">Prueba</span><button type="button" class="btn btn-sm btn-outline-light est-sort-btn d-print-none" data-col="1" data-type="text" title="Ordenar">⇅</button></span></th>
                        <th><span class="d-inline-flex align-items-center gap-1 w-100"><span class="est-th-label">Categoría</span><button type="button" class="btn btn-sm btn-outline-light est-sort-btn d-print-none" data-col="2" data-type="text" title="Ordenar">⇅</button></span></th>
                        <th class="text-end"><span class="d-inline-flex align-items-center justify-content-end gap-1 w-100"><span class="est-th-label">Órdenes con la prueba</span><button type="button" class="btn btn-sm btn-outline-light est-sort-btn d-print-none" data-col="3" data-type="number" title="Ordenar">⇅</button></span></th>
                        <th class="text-end"><span class="d-inline-flex align-items-center justify-content-end gap-1 w-100"><span class="est-th-label">Órdenes procesadas</span><button type="button" class="btn btn-sm btn-outline-light est-sort-btn d-print-none" data-col="4" data-type="number" title="Ordenar">⇅</button></span></th>
                        <th class="text-end"><span class="d-inline-flex align-items-center justify-content-end gap-1 w-100"><span class="est-th-label">Pacientes distintos</span><button type="button" class="btn btn-sm btn-outline-light est-sort-btn d-print-none" data-col="5" data-type="number" title="Ordenar">⇅</button></span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $n = 0; $sumSolicitadas = 0; $sumProcesadas = 0;
                    foreach ($porPruebaRows as $p):
                        $n++;
                        $sumSolicitadas += (int) ($p['ordenes_con_prueba'] ?? 0);
                        $sumProcesadas  += (int) ($p['ordenes_procesadas'] ?? 0);
                    ?>
                    <tr>
                        <td class="text-end text-muted"><?= $n ?></td>
                        <td><?= esc($p['prueba'] ?? '') ?></td>
                        <td class="text-muted small"><?= esc(($p['categoria'] ?? '') !== '' ? (string) $p['categoria'] : '—') ?></td>
                        <td class="text-end fw-semibold"><?= (int) ($p['ordenes_con_prueba'] ?? 0) ?></td>
                        <td class="text-end"><?= (int) ($p['ordenes_procesadas'] ?? 0) ?></td>
                        <td class="text-end"><?= (int) ($p['pacientes_distintos'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($porPruebaRows)): ?>
                    <tr><td colspan="6" class="text-muted px-3 py-3">Sin análisis en órdenes del período.</td></tr>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($porPruebaRows)): ?>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="3" class="text-end"><strong>Totales</strong></td>
                        <td class="text-end"><strong><?= (int) $sumSolicitadas ?></strong></td>
                        <td class="text-end"><strong><?= (int) $sumProcesadas ?></strong></td>
                        <td class="text-end text-muted small">—</td>
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
            <div class="card-header"><strong>Por grupo poblacional</strong> <span class="text-muted small">(órdenes con resultado)</span></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light"><tr><th>Grupo</th><th>Criterio de edad</th><th class="text-end">Órdenes</th></tr></thead>
                        <tbody>
                            <?php foreach ($poblacionGrupoRows as $fila): ?>
                            <tr>
                                <td class="fw-medium"><?= esc($fila['nombre'] ?? '') ?></td>
                                <td class="text-muted small"><?= esc($fila['rango_edad'] ?? '') ?></td>
                                <td class="text-end"><?= (int) ($fila['ordenes'] ?? 0) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($poblacionGrupoRows)): ?>
                            <tr><td colspan="3" class="text-muted px-3 py-3">Sin datos en el período.</td></tr>
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
                        <thead class="table-light"><tr><th>Género</th><th class="text-end">Pacientes únicos</th><th class="text-end">Órdenes procesadas</th></tr></thead>
                        <tbody>
                            <tr><td>Masculino</td><td class="text-end"><?= (int) ($porGeneroPacientes['1'] ?? 0) ?></td><td class="text-end"><?= (int) ($porGeneroOrdenes['1'] ?? 0) ?></td></tr>
                            <tr><td>Femenino</td><td class="text-end"><?= (int) ($porGeneroPacientes['2'] ?? 0) ?></td><td class="text-end"><?= (int) ($porGeneroOrdenes['2'] ?? 0) ?></td></tr>
                            <tr><td>No indicado</td><td class="text-end"><?= (int) ($porGeneroPacientes['_'] ?? 0) ?></td><td class="text-end"><?= (int) ($porGeneroOrdenes['_'] ?? 0) ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/estadisticas_lab_table.js') ?>"></script>
<script>
window.SNIS_CHART_DATA = <?= json_encode($charts, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= base_url('js/estadisticas_lab_dashboard.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    flatpickr("#report_start", { dateFormat: "Y-m-d", locale: "es", onOpen: function(s,d,i){ flatpickrPositionArrowTopLeft(i); } });
    flatpickr("#report_end", { dateFormat: "Y-m-d", locale: "es", onOpen: function(s,d,i){ flatpickrPositionArrowTopLeft(i); } });
});
</script>
<?= $this->endSection() ?>
