<?= $this->extend('layouts/main') ?>

<?= $this->section('head_extra') ?>
<link rel="stylesheet" href="<?= base_url('css/vendor/flatpickr.min.css') ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_green.css">
<script src="<?= base_url('js/vendor/flatpickr.min.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/es.js"></script>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php helper('registro'); ?>
<div class="d-print-none">
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_reports'), 'url' => site_url('reports')],
    ['label' => $title ?? '', 'url' => null],
]]) ?>
</div>

<form method="get" action="<?= site_url('reports/pruebasDetallado') ?>" class="row g-3 mb-4 d-print-none">
    <div class="col-auto">
        <label for="report_start" class="form-label">Desde</label>
        <input type="text" id="report_start" name="start" class="form-control flatpickr-input" value="<?= esc($startDate ?? '') ?>">
    </div>
    <div class="col-auto">
        <label for="report_end" class="form-label">Hasta</label>
        <input type="text" id="report_end" name="end" class="form-control flatpickr-input" value="<?= esc($endDate ?? '') ?>">
    </div>
    <div class="col-auto d-flex align-items-end">
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </div>
</form>

<?= view('reports/partials/report_actions', [
    'pdf_url' => site_url('reports/pruebasDetalladoPdf?' . http_build_query(['start' => $startDate ?? '', 'end' => $endDate ?? ''])),
]) ?>

<h4><?= esc($title ?? '') ?></h4>
<p class="text-muted"><?= esc($subtitle ?? '') ?></p>
<p class="small text-muted">Órdenes completas con trazabilidad de recepción y edición de resultados. Use <strong>Ver detalles</strong> para consultar quién cargó o modificó valores.</p>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Cód. recepción</th>
                <th>Fecha</th>
                <th>Paciente</th>
                <th>Usuario recepción</th>
                <th>Primera carga</th>
                <th>Última edición</th>
                <th>Pruebas</th>
                <th class="text-center d-print-none" style="width:120px">Detalle</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data ?? [] as $row): ?>
            <?php
            $codigo = registro_codigo_recepcion_display($row, false);
            $usuarioRecepcion = trim((string) ($row['usuario_recepcion'] ?? ''));
            $usuarioPrimera   = trim((string) ($row['usuario_primera_carga'] ?? ''));
            $usuarioEdicion   = trim((string) ($row['usuario_ultima_edicion'] ?? ''));
            ?>
            <tr>
                <td><strong><?= esc($codigo !== '' ? $codigo : '—') ?></strong></td>
                <td><?= esc(lab_dt_short($row['ingreso'] ?? null)) ?></td>
                <td><?= esc($row['paciente'] ?? '') ?></td>
                <td><?= esc($usuarioRecepcion !== '' ? $usuarioRecepcion : '—') ?></td>
                <td><?= esc($usuarioPrimera !== '' ? $usuarioPrimera : '—') ?></td>
                <td>
                    <?php if ($usuarioEdicion !== ''): ?>
                        <?= esc($usuarioEdicion) ?>
                        <?php if ((int) ($row['veces_editado'] ?? 0) > 0): ?>
                            <span class="badge bg-warning text-dark ms-1"><?= (int) $row['veces_editado'] ?> ed.</span>
                        <?php endif; ?>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
                <td class="small"><?= esc($row['pruebas_nombres'] ?? '-') ?></td>
                <td class="text-center d-print-none">
                    <button type="button"
                            class="btn btn-sm btn-outline-primary btn-ver-detalle-prueba"
                            data-registro-id="<?= (int) ($row['registro_id'] ?? 0) ?>"
                            data-codigo="<?= esc($codigo) ?>"
                            data-paciente="<?= esc($row['paciente'] ?? '') ?>">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Ver detalles
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (empty($data)): ?>
<p class="text-muted">No hay órdenes con pruebas en el período seleccionado.</p>
<?php else: ?>
<p class="text-muted small">Total: <?= count($data) ?> orden(es).</p>
<?php endif; ?>

<div class="modal fade d-print-none" id="modalPruebasDetallado" tabindex="-1" aria-labelledby="modalPruebasDetalladoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="modalPruebasDetalladoLabel">Trazabilidad de pruebas</h5>
                    <p class="mb-0 small text-muted" id="modalPruebasDetalladoSubtitulo"></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="modalPruebasDetalladoCargando" class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div> Cargando…
                </div>
                <div id="modalPruebasDetalladoError" class="alert alert-danger d-none"></div>
                <div id="modalPruebasDetalladoContenido" class="d-none">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="small text-muted">Usuario recepción</div>
                                <div class="fw-semibold" id="detalleUsuarioRecepcion">—</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="small text-muted">Primera carga de resultados</div>
                                <div class="fw-semibold" id="detalleUsuarioPrimeraCarga">—</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="small text-muted">Última edición de resultados</div>
                                <div class="fw-semibold" id="detalleUsuarioUltimaEdicion">—</div>
                            </div>
                        </div>
                    </div>

                    <h6 class="mb-2"><i class="fa-solid fa-flask me-2"></i>Historial de resultados</h6>
                    <div id="detalleHistorialResultados"></div>

                    <h6 class="mb-2 mt-4"><i class="fa-solid fa-list-check me-2"></i>Ediciones de la orden</h6>
                    <div id="detalleEdicionesOrden"></div>
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

    const detalleUrl = <?= json_encode(site_url('reports/pruebasDetalladoDetalle')) ?>;
    const modalEl = document.getElementById('modalPruebasDetallado');

    function escHtml(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function resetModal() {
        document.getElementById('modalPruebasDetalladoCargando').classList.remove('d-none');
        document.getElementById('modalPruebasDetalladoError').classList.add('d-none');
        document.getElementById('modalPruebasDetalladoContenido').classList.add('d-none');
        document.getElementById('detalleHistorialResultados').innerHTML = '';
        document.getElementById('detalleEdicionesOrden').innerHTML = '';
    }

    function renderCambiosTabla(cambios, tipo) {
        if (!cambios || !cambios.length) return '';
        let html = '<div class="table-responsive mb-2"><table class="table table-sm table-bordered mb-0">';
        if (tipo === 'cambio') {
            html += '<thead><tr><th>Parámetro</th><th>Prueba</th><th>Valor anterior</th><th>Valor nuevo</th></tr></thead><tbody>';
            cambios.forEach(function(c) {
                html += '<tr><td>' + escHtml(c.campo) + '</td><td class="small">' + escHtml(c.prueba || '—') + '</td>'
                    + '<td>' + escHtml(c.valor_anterior || '—') + '</td><td><strong>' + escHtml(c.valor_nuevo || '—') + '</strong></td></tr>';
            });
        } else if (tipo === 'agregado') {
            html += '<thead><tr><th>Parámetro</th><th>Prueba</th><th>Valor agregado</th></tr></thead><tbody>';
            cambios.forEach(function(c) {
                html += '<tr><td>' + escHtml(c.campo) + '</td><td class="small">' + escHtml(c.prueba || '—') + '</td>'
                    + '<td><strong>' + escHtml(c.valor_nuevo || '—') + '</strong></td></tr>';
            });
        } else {
            html += '<thead><tr><th>Parámetro</th><th>Prueba</th><th>Valor eliminado</th></tr></thead><tbody>';
            cambios.forEach(function(c) {
                html += '<tr><td>' + escHtml(c.campo) + '</td><td class="small">' + escHtml(c.prueba || '—') + '</td>'
                    + '<td>' + escHtml(c.valor_anterior || '—') + '</td></tr>';
            });
        }
        html += '</tbody></table></div>';
        return html;
    }

    async function abrirDetalle(registroId, codigo, paciente) {
        if (!modalEl || typeof bootstrap === 'undefined') return;
        resetModal();
        document.getElementById('modalPruebasDetalladoSubtitulo').textContent =
            'Orden ' + codigo + ' · ' + paciente;
        bootstrap.Modal.getOrCreateInstance(modalEl).show();

        try {
            const res = await fetch(detalleUrl + '?' + new URLSearchParams({ registro_id: String(registroId) }));
            const data = await res.json();
            document.getElementById('modalPruebasDetalladoCargando').classList.add('d-none');
            if (!data.success) {
                const err = document.getElementById('modalPruebasDetalladoError');
                err.textContent = data.message || 'No se pudo cargar el detalle.';
                err.classList.remove('d-none');
                return;
            }

            document.getElementById('detalleUsuarioRecepcion').textContent = data.usuario_recepcion || '—';
            document.getElementById('detalleUsuarioPrimeraCarga').textContent = data.usuario_primera_carga || '—';
            document.getElementById('detalleUsuarioUltimaEdicion').textContent = data.usuario_ultima_edicion || '—';

            const histWrap = document.getElementById('detalleHistorialResultados');
            const historial = data.historial_resultados || [];
            if (!historial.length) {
                histWrap.innerHTML = '<p class="text-muted small mb-0">Sin registros de carga o edición de resultados en auditoría.</p>';
            } else {
                let histHtml = '';
                historial.forEach(function(evt, idx) {
                    const badge = evt.tipo === 'primera_carga'
                        ? '<span class="badge bg-success">Primera carga</span>'
                        : '<span class="badge bg-warning text-dark">Edición</span>';
                    histHtml += '<div class="card mb-2"><div class="card-body py-2">'
                        + '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">'
                        + '<div><strong>' + escHtml(evt.usuario) + '</strong> ' + badge
                        + ' <span class="small text-muted ms-1">' + escHtml(evt.fecha) + '</span></div>';
                    if (evt.pruebas_editadas && evt.pruebas_editadas.length) {
                        histHtml += '<div class="small"><span class="text-muted">Pruebas:</span> ' + escHtml(evt.pruebas_editadas.join(', ')) + '</div>';
                    }
                    histHtml += '</div>';
                    if (evt.tiene_detalle) {
                        if (evt.cambios && evt.cambios.length) {
                            histHtml += '<div class="small fw-semibold text-primary mb-1">Valores modificados</div>' + renderCambiosTabla(evt.cambios, 'cambio');
                        }
                        if (evt.agregados && evt.agregados.length) {
                            histHtml += '<div class="small fw-semibold text-success mb-1">Valores agregados</div>' + renderCambiosTabla(evt.agregados, 'agregado');
                        }
                        if (evt.eliminados && evt.eliminados.length) {
                            histHtml += '<div class="small fw-semibold text-danger mb-1">Valores eliminados</div>' + renderCambiosTabla(evt.eliminados, 'eliminado');
                        }
                    } else if (evt.tipo === 'primera_carga') {
                        histHtml += '<p class="small text-muted mb-0">Carga inicial (' + (evt.cantidad_valores || 0) + ' valor(es)).</p>';
                    } else {
                        histHtml += '<p class="small text-muted mb-0">Edición registrada sin detalle de cambios (registro anterior a esta mejora).</p>';
                    }
                    histHtml += '</div></div>';
                });
                histWrap.innerHTML = histHtml;
            }

            const ordWrap = document.getElementById('detalleEdicionesOrden');
            const ediciones = data.ediciones_orden || [];
            if (!ediciones.length) {
                ordWrap.innerHTML = '<p class="text-muted small mb-0">Sin ediciones de la orden registradas.</p>';
            } else {
                let ordHtml = '<div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr>'
                    + '<th>Fecha</th><th>Usuario</th><th>Pruebas en la orden</th></tr></thead><tbody>';
                ediciones.forEach(function(evt) {
                    ordHtml += '<tr><td class="small">' + escHtml(evt.fecha) + '</td><td>' + escHtml(evt.usuario)
                        + '</td><td class="small">' + escHtml(evt.pruebas) + '</td></tr>';
                });
                ordHtml += '</tbody></table></div>';
                ordWrap.innerHTML = ordHtml;
            }

            document.getElementById('modalPruebasDetalladoContenido').classList.remove('d-none');
        } catch (e) {
            document.getElementById('modalPruebasDetalladoCargando').classList.add('d-none');
            const err = document.getElementById('modalPruebasDetalladoError');
            err.textContent = 'Error de conexión al cargar el detalle.';
            err.classList.remove('d-none');
        }
    }

    document.querySelectorAll('.btn-ver-detalle-prueba').forEach(function(btn) {
        btn.addEventListener('click', function() {
            abrirDetalle(
                btn.getAttribute('data-registro-id'),
                btn.getAttribute('data-codigo') || '',
                btn.getAttribute('data-paciente') || ''
            );
        });
    });
});
</script>
<?= $this->endSection() ?>
