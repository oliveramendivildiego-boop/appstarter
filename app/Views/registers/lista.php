<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Lista de registros<?= $this->endSection() ?>

<?= $this->section('head_extra') ?>
<link rel="stylesheet" href="<?= base_url('css/vendor/flatpickr.min.css') ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_green.css">
<script src="<?= base_url('js/vendor/flatpickr.min.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/es.js"></script>
<style>
.lista-filtros-form .lista-btn-filtrar {
    border: 2px solid var(--bs-primary, #0d6efd) !important;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.15);
    font-weight: 600;
    white-space: nowrap;
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', [
    'items' => [
        ['label' => lang('Module.module_registers'), 'url' => site_url('registers/lista')],
        ['label' => 'Registro', 'url' => null],
    ],
    'right' => '<a href="' . site_url('registers') . '" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i> Registrar</a> ' .
        '<a href="' . site_url('expediente') . '" class="btn btn-outline-info btn-sm"><i class="fa-solid fa-folder-open me-1"></i> Historial paciente</a>',
]) ?>

<?php
$listaQueryBase = static function (array $extra = []) use ($search, $estado, $fecha_desde, $fecha_hasta, $fecha_todos): string {
    $params = [];
    if (! empty($search)) {
        $params['q'] = $search;
    }
    if (! empty($estado ?? '')) {
        $params['estado'] = $estado;
    }
    if (! empty($fecha_todos)) {
        $params['fecha_todos'] = '1';
    } else {
        if (! empty($fecha_desde ?? '')) {
            $params['fecha_desde'] = $fecha_desde;
        }
        if (! empty($fecha_hasta ?? '')) {
            $params['fecha_hasta'] = $fecha_hasta;
        }
    }
    foreach ($extra as $k => $v) {
        if ($v === null || $v === '') {
            unset($params[$k]);
        } else {
            $params[$k] = $v;
        }
    }
    $qs = http_build_query($params);

    return site_url('registers/lista') . ($qs !== '' ? '?' . $qs : '');
};
$esHoy = ! ($fecha_todos ?? false)
    && ($fecha_desde ?? '') === ($fecha_hoy ?? '')
    && ($fecha_hasta ?? '') === ($fecha_hoy ?? '');
$esSemana = ! ($fecha_todos ?? false)
    && ($fecha_desde ?? '') === ($fecha_semana_desde ?? '')
    && ($fecha_hasta ?? '') === ($fecha_hoy ?? '');
$esMes = ! ($fecha_todos ?? false)
    && ($fecha_desde ?? '') === ($fecha_mes_desde ?? '')
    && ($fecha_hasta ?? '') === ($fecha_hoy ?? '');
$esPresetFecha = $esHoy || $esSemana || $esMes;
$listaBtnClass = static fn (bool $active): string => 'btn btn-sm ' . ($active ? 'btn-secondary' : 'btn-outline-secondary');
?>
<div class="row mb-3">
    <div class="col-md-12">
        <form method="get" action="<?= site_url('registers/lista') ?>" class="lista-filtros-form">
            <?php if (! empty($fecha_todos)): ?>
            <input type="hidden" name="fecha_todos" value="1">
            <?php endif; ?>
            <div class="row g-2 align-items-end">
                <div class="col-xl-4 col-lg-5 col-md-6">
                    <label for="lista_q" class="form-label small mb-0">Buscar</label>
                    <input type="text" id="lista_q" name="q" class="form-control form-control-sm" placeholder="Código, nombre, apellidos o CI..." value="<?= esc($search ?? '') ?>">
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <label for="lista_estado" class="form-label small mb-0">Estado</label>
                    <select id="lista_estado" name="estado" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="activo" <?= ($estado ?? '') === 'activo' ? 'selected' : '' ?>>Solo activas (no anuladas)</option>
                        <option value="completo" <?= ($estado ?? '') === 'completo' ? 'selected' : '' ?>>Completos (con resultados)</option>
                        <option value="incompleto" <?= ($estado ?? '') === 'incompleto' ? 'selected' : '' ?>>Incompletos (sin resultados)</option>
                        <option value="anulado" <?= ($estado ?? '') === 'anulado' ? 'selected' : '' ?>>Solo anuladas</option>
                    </select>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4">
                    <label for="lista_fecha_desde" class="form-label small mb-0">Desde</label>
                    <input type="text" id="lista_fecha_desde" name="fecha_desde" class="form-control form-control-sm flatpickr-input" autocomplete="off"
                           value="<?= esc($fecha_desde ?? '') ?>" <?= ! empty($fecha_todos) ? 'disabled' : '' ?>>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4">
                    <label for="lista_fecha_hasta" class="form-label small mb-0">Hasta</label>
                    <input type="text" id="lista_fecha_hasta" name="fecha_hasta" class="form-control form-control-sm flatpickr-input" autocomplete="off"
                           value="<?= esc($fecha_hasta ?? '') ?>" <?= ! empty($fecha_todos) ? 'disabled' : '' ?>>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm lista-btn-filtrar"><i class="fa-solid fa-search"></i> Filtrar</button>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center pt-2 pb-1">
                <div class="btn-group btn-group-sm" role="group" aria-label="Período rápido">
                    <a href="<?= esc($listaQueryBase(['fecha_desde' => $fecha_hoy ?? '', 'fecha_hasta' => $fecha_hoy ?? '', 'fecha_todos' => null, 'page' => null]), 'attr') ?>"
                       class="<?= $listaBtnClass($esHoy) ?>" title="Solo registros de hoy">Hoy</a>
                    <a href="<?= esc($listaQueryBase(['fecha_desde' => $fecha_semana_desde ?? '', 'fecha_hasta' => $fecha_hoy ?? '', 'fecha_todos' => null, 'page' => null]), 'attr') ?>"
                       class="<?= $listaBtnClass($esSemana) ?>" title="Desde el lunes de esta semana hasta hoy">Esta semana</a>
                    <a href="<?= esc($listaQueryBase(['fecha_desde' => $fecha_mes_desde ?? '', 'fecha_hasta' => $fecha_hoy ?? '', 'fecha_todos' => null, 'page' => null]), 'attr') ?>"
                       class="<?= $listaBtnClass($esMes) ?>" title="Desde el primer día del mes hasta hoy">Este mes</a>
                </div>
                <?php if (! $esPresetFecha && empty($fecha_todos)): ?>
                <a href="<?= esc($listaQueryBase(['fecha_todos' => '1', 'fecha_desde' => null, 'fecha_hasta' => null, 'page' => null]), 'attr') ?>" class="btn btn-outline-secondary btn-sm">Todas las fechas</a>
                <?php endif; ?>
                <?php if (! empty($search) || ! empty($estado ?? '') || ! ($es_lista_default ?? false)): ?>
                <a href="<?= site_url('registers/lista') ?>" class="btn btn-outline-secondary btn-sm">Limpiar</a>
                <?php endif; ?>
            </div>
        </form>
        <p class="text-muted small mb-0 mt-2">
            <?php if (! empty($fecha_todos)): ?>
                Mostrando registros de <strong>todas las fechas</strong>.
            <?php elseif ($esHoy): ?>
                Mostrando registros de <strong>hoy</strong> (<?= esc(\App\Services\RegisterService::formatReportDate($fecha_hoy ?? '')) ?>).
            <?php elseif ($esSemana): ?>
                Mostrando registros de <strong>esta semana</strong>
                (<?= esc(\App\Services\RegisterService::formatReportDate($fecha_semana_desde ?? '')) ?>
                – <?= esc(\App\Services\RegisterService::formatReportDate($fecha_hoy ?? '')) ?>).
            <?php elseif ($esMes): ?>
                Mostrando registros de <strong>este mes</strong>
                (<?= esc(\App\Services\RegisterService::formatReportDate($fecha_mes_desde ?? '')) ?>
                – <?= esc(\App\Services\RegisterService::formatReportDate($fecha_hoy ?? '')) ?>).
            <?php elseif (($fecha_desde ?? '') === ($fecha_hasta ?? '')): ?>
                Mostrando registros del <strong><?= esc(\App\Services\RegisterService::formatReportDate($fecha_desde ?? '')) ?></strong>.
            <?php else: ?>
                Mostrando registros del <strong><?= esc(\App\Services\RegisterService::formatReportDate($fecha_desde ?? '')) ?></strong>
                al <strong><?= esc(\App\Services\RegisterService::formatReportDate($fecha_hasta ?? '')) ?></strong>.
            <?php endif; ?>
        </p>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <?php
        $totalReg = (int)($total ?? 0);
        $pageNum = (int)($page ?? 1);
        $perPage = (int)($perPage ?? 20);
        $desde = $totalReg > 0 ? (($pageNum - 1) * $perPage) + 1 : 0;
        $hasta = min($pageNum * $perPage, $totalReg);
        ?>
        <p class="text-muted small mb-1">Mostrando <?= $desde ?>–<?= $hasta ?> de <?= $totalReg ?> registro(s)</p>
        <p class="text-muted small d-md-none mb-2"><i class="fa-solid fa-arrows-left-right me-1"></i>Desliza horizontalmente para ver todas las columnas.</p>
        <?= $manage_table ?? '' ?>
    </div>
</div>

<!-- Modal Historial de pagos y pruebas -->
<div class="modal fade" id="modalHistorial" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-clock-rotate-left me-2"></i>Historial de pagos y pruebas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="historialContent">
                <div class="text-center py-4 text-muted">Seleccione un registro para ver el historial.</div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Enviar PDF por WhatsApp -->
<div class="modal fade" id="modalWhatsapp" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-brands fa-whatsapp text-success me-2"></i>Enviar PDF por WhatsApp</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="wa_registro_id" value="">
                <input type="hidden" id="wa_paciente_phone" value="">
                <input type="hidden" id="wa_doctor_phone" value="">
                <p class="mb-2" id="wa_info_text">Se enviará el PDF de resultados al destinatario seleccionado.</p>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Enviar a:</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="wa_destinatario" id="wa_paciente" value="paciente">
                        <label class="form-check-label" for="wa_paciente">
                            Paciente: <span id="wa_paciente_nombre"></span>
                            <span id="wa_paciente_phone_badge" class="badge bg-secondary ms-1"></span>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="wa_destinatario" id="wa_doctor" value="doctor">
                        <label class="form-check-label" for="wa_doctor">
                            Doctor: <span id="wa_doctor_nombre"></span>
                            <span id="wa_doctor_phone_badge" class="badge bg-secondary ms-1"></span>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="wa_destinatario" id="wa_ambos" value="ambos" checked>
                        <label class="form-check-label" for="wa_ambos">Ambos (paciente y doctor)</label>
                    </div>
                </div>
                <div id="wa_alert_sin_telefono" class="alert alert-warning small py-2" style="display:none;">
                    <i class="fa-solid fa-exclamation-triangle me-1"></i>El destinatario seleccionado no tiene número de teléfono registrado.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnEnviarWhatsapp"><i class="fa-brands fa-whatsapp me-1"></i>Enviar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Anular orden -->
<div class="modal fade" id="modalAnularRegistro" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-ban text-danger me-2"></i>Anular orden</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">El registro <strong>no se borra</strong> de la base de datos. Quedará bloqueado: solo podrá consultarse el motivo y las pruebas que llevaba la orden. No se podrán usar costos operativos, resultados, pagos adicionales ni consumo de inventario vinculado a esta orden.</p>
                <input type="hidden" id="anular_registro_id" value="">
                <label for="anular_motivo" class="form-label fw-semibold">Motivo de la anulación <span class="text-danger">*</span></label>
                <textarea id="anular_motivo" class="form-control" rows="4" placeholder="Explique por qué se anula esta orden (mínimo 5 caracteres)" required></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmarAnular"><i class="fa-solid fa-ban me-1"></i>Confirmar anulación</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Agregar pago -->
<div class="modal fade" id="modalAgregarPago" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar nuevo pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="pago_registro_id" value="">
                <div class="mb-3">
                    <label class="form-label">Total orden:</label>
                    <span id="pago_total_show" class="form-control-plaintext fw-bold">0.00</span>
                </div>
                <div class="mb-3">
                    <label class="form-label">Saldo actual (deuda):</label>
                    <span id="pago_saldo_actual" class="form-control-plaintext fw-bold text-danger">0.00</span>
                </div>
                <div class="mb-3">
                    <label for="pago_monto_pagar" class="form-label">Monto a agregar:</label>
                    <input type="text" id="pago_monto_pagar" class="form-control" placeholder="0.00">
                </div>
                <div class="mb-3">
                    <label for="pago_tipopago" class="form-label">Método de pago:</label>
                    <select id="pago_tipopago" class="form-select">
                        <option value="1">Efectivo</option>
                        <option value="2">QR</option>
                        <option value="3">Transferencia</option>
                        <option value="4">Pendiente</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Saldo después del pago:</label>
                    <span id="pago_saldo_calc" class="form-control-plaintext fw-bold">0.00</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarPago">Agregar pago</button>
            </div>
        </div>
    </div>
</div>

<?php if (($totalPages ?? 1) > 1): ?>
<nav class="mt-3">
    <ul class="pagination justify-content-center">
        <?php
        $pageNum = (int)($page ?? 1);
        for ($i = 1; $i <= ($totalPages ?? 1); $i++):
        ?>
        <li class="page-item <?= ($i === $pageNum) ? 'active' : '' ?>">
            <a class="page-link" href="<?= esc($listaQueryBase(['page' => (string) $i]), 'attr') ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
<!-- Modal Editar pago (historial) -->
<div class="modal fade" id="modalEditarTipoPago" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-pen-to-square me-2"></i>Editar pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit_abono_registro_id" value="">
                <input type="hidden" id="edit_abono_id" value="">
                <p class="small text-muted mb-3" id="edit_abono_resumen"></p>
                <div class="mb-3">
                    <label for="edit_abono_monto" class="form-label">Monto:</label>
                    <input type="text" id="edit_abono_monto" class="form-control" placeholder="0.00">
                </div>
                <div class="mb-0">
                    <label for="edit_abono_tipopago" class="form-label">Método de pago:</label>
                    <select id="edit_abono_tipopago" class="form-select">
                        <option value="1">Efectivo</option>
                        <option value="2">QR</option>
                        <option value="3">Transferencia</option>
                        <option value="4">Pendiente</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarTipoPago">Guardar cambio</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal mensaje (reemplaza alert para confirmaciones/mensajes) -->
<div class="modal fade" id="modalMensajeAccion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalMensajeAccionTitulo"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="modalMensajeAccionTexto"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof flatpickr === 'undefined') {
        return;
    }
    var fpOpts = {
        dateFormat: 'Y-m-d',
        locale: 'es',
        allowInput: true,
        onOpen: function (s, d, i) {
            if (typeof flatpickrPositionArrowTopLeft === 'function') {
                flatpickrPositionArrowTopLeft(i);
            }
        }
    };
    flatpickr('#lista_fecha_desde', fpOpts);
    flatpickr('#lista_fecha_hasta', fpOpts);
});
function escapeHtml(str) {
    if (str == null) return '';
    var s = String(str);
    return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}
document.addEventListener('DOMContentLoaded', function() {
    // Modal mensaje general (reemplaza alert() para confirmaciones/mensajes)
    var modalMensajeAccion = document.getElementById('modalMensajeAccion');
    var modalMensajeAccionTitulo = document.getElementById('modalMensajeAccionTitulo');
    var modalMensajeAccionTexto = document.getElementById('modalMensajeAccionTexto');

    function mostrarMensajeModal(titulo, texto) {
        texto = (texto == null) ? '' : String(texto);
        if (modalMensajeAccion && modalMensajeAccionTitulo && modalMensajeAccionTexto && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            modalMensajeAccionTitulo.textContent = titulo || 'Mensaje';
            modalMensajeAccionTexto.textContent = texto;
            new bootstrap.Modal(modalMensajeAccion).show();
            return;
        }
        if (typeof uiAlert === 'function') {
            uiAlert(texto, titulo || 'Mensaje');
        }
    }

    // Modal Agregar pago
    var modalPago = document.getElementById('modalAgregarPago');
    var btnAgregarPagoList = document.querySelectorAll('.btn-agregar-pago');
    var montoPagoInput = document.getElementById('pago_monto_pagar');
    var tipopagoSelect = document.getElementById('pago_tipopago');
    var saldoPagoSpan = document.getElementById('pago_saldo_calc');
    var pagoRegistroIdInput = document.getElementById('pago_registro_id');
    var pagoTotalSpan = document.getElementById('pago_total_show');

    var pagoSaldoActualSpan = document.getElementById('pago_saldo_actual');
    function calcSaldoPago() {
        if (!montoPagoInput || !pagoSaldoActualSpan || !saldoPagoSpan) return;
        var saldoActual = parseFloat(pagoSaldoActualSpan.textContent || 0);
        var montoAgregar = parseFloat(montoPagoInput.value || 0);
        var nuevoSaldo = saldoActual - montoAgregar;
        saldoPagoSpan.textContent = isNaN(nuevoSaldo) ? '' : Math.max(0, nuevoSaldo).toFixed(2);
    }

    if (modalPago && montoPagoInput) montoPagoInput.addEventListener('input', calcSaldoPago);

    btnAgregarPagoList.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var rid = this.getAttribute('data-id');
            var total = this.getAttribute('data-total') || '0';
            var saldo = this.getAttribute('data-saldo') || '0';
            if (pagoRegistroIdInput) pagoRegistroIdInput.value = rid;
            if (pagoTotalSpan) pagoTotalSpan.textContent = total;
            if (pagoSaldoActualSpan) pagoSaldoActualSpan.textContent = saldo;
            if (montoPagoInput) montoPagoInput.value = '';
            if (tipopagoSelect) tipopagoSelect.value = '1';
            calcSaldoPago();
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                new bootstrap.Modal(modalPago).show();
            } else if (modalPago) {
                modalPago.classList.add('show');
                modalPago.style.display = 'block';
            }
        });
    });

    var btnGuardarPago = document.getElementById('btnGuardarPago');
    if (btnGuardarPago) {
        btnGuardarPago.addEventListener('click', function() {
            var rid = pagoRegistroIdInput ? pagoRegistroIdInput.value : '';
            if (!rid) return;
            var monto = montoPagoInput ? montoPagoInput.value : '';
            var tipopago = tipopagoSelect ? tipopagoSelect.value : '';
            var csrf = (typeof CI_CSRF_TOKEN !== 'undefined' && typeof CI_CSRF_TOKEN_NAME !== 'undefined')
                ? '&' + CI_CSRF_TOKEN_NAME + '=' + encodeURIComponent(CI_CSRF_TOKEN) : '';
            var body = 'monto_pagar=' + encodeURIComponent(monto) + '&tipopago=' + encodeURIComponent(tipopago) + csrf;
            fetch('<?= site_url('registers/addpago') ?>/' + rid, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: body
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) { location.reload(); }
                else { mostrarMensajeModal('Error', res.message || 'Error al guardar'); }
            })
            .catch(function() { mostrarMensajeModal('Error', 'Error de conexión'); });
        });
    }

    // Modal Historial
    var modalHistorial = document.getElementById('modalHistorial');
    var historialContent = document.getElementById('historialContent');
    var historialRegistroIdActual = '';
    var modalEditarTipoPago = document.getElementById('modalEditarTipoPago');
    var editAbonoRegistroId = document.getElementById('edit_abono_registro_id');
    var editAbonoId = document.getElementById('edit_abono_id');
    var editAbonoTipopago = document.getElementById('edit_abono_tipopago');
    var editAbonoMonto = document.getElementById('edit_abono_monto');
    var editAbonoResumen = document.getElementById('edit_abono_resumen');
    var btnGuardarTipoPago = document.getElementById('btnGuardarTipoPago');

    function getCsrfBody() {
        return (typeof CI_CSRF_TOKEN !== 'undefined' && typeof CI_CSRF_TOKEN_NAME !== 'undefined')
            ? '&' + CI_CSRF_TOKEN_NAME + '=' + encodeURIComponent(CI_CSRF_TOKEN) : '';
    }

    function renderHistorialHtml(rid, d) {
        var reg = d.registro || {};
        var pago = d.pago || {};
        var html = '<div class="mb-3"><h6 class="border-bottom pb-2">Datos del registro</h6>';
        html += '<p class="mb-1"><strong>Orden:</strong> ' + escapeHtml((reg.numero_orden && String(reg.numero_orden).trim() !== '') ? reg.numero_orden : (reg.registro_id || '-')) + '</p>';
        html += '<p class="mb-1"><strong>Paciente:</strong> ' + escapeHtml(reg.paciente || '-') + '</p>';
        html += '<p class="mb-1"><strong>Doctor:</strong> ' + escapeHtml(reg.doctor || '-') + '</p>';
        html += '<p class="mb-0"><strong>Fecha ingreso:</strong> ' + escapeHtml(reg.ingreso_display || reg.ingreso || '-') + '</p></div>';
        html += '<div class="mb-3"><h6 class="border-bottom pb-2">Historial de pagos</h6>';
        html += '<p class="mb-2"><strong>Total orden:</strong> ' + escapeHtml(pago.total || '0') + ' ' + (window.APP_CURRENCY_SYMBOL || '$') + ' &nbsp;|&nbsp; <strong>Pagado:</strong> ' + escapeHtml(pago.monto_pagar || '0') + ' ' + (window.APP_CURRENCY_SYMBOL || '$') + ' &nbsp;|&nbsp; <strong>Saldo:</strong> ' + escapeHtml(pago.saldo || '0') + ' ' + (window.APP_CURRENCY_SYMBOL || '$') + '</p>';
        var urlComp = '<?= site_url('registers/comprobantePdf') ?>/' + encodeURIComponent(rid);
        var lblComp = d.sin_billing_enabled ? 'Descargar factura (PDF)' : 'Descargar recibo (PDF)';
        html += '<p class="mb-2"><a href="' + urlComp + '" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener"><i class="fa-solid fa-file-invoice-dollar me-1"></i>' + escapeHtml(lblComp) + '</a></p>';
        if (!d.pago_completo && pago && Object.keys(pago).length > 0) {
            html += '<p class="small text-muted mb-2"><i class="fa-solid fa-circle-info me-1"></i>El comprobante mostrará el saldo pendiente si la orden aún no está saldada.</p>';
        }
        if (d.abonos && d.abonos.length > 0) {
            var tieneEditable = d.abonos.some(function(a) { return a.pago_abono_id; });
            html += '<div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Fecha</th><th>Monto</th><th>Método</th>';
            if (tieneEditable) html += '<th class="text-end" style="min-width:110px">Acciones</th>';
            html += '</tr></thead><tbody>';
            d.abonos.forEach(function(a) {
                var fecha = a.fecha_abono_display || a.fecha_abono || '-';
                var abonoId = a.pago_abono_id || '';
                html += '<tr><td>' + escapeHtml(fecha) + '</td><td>' + escapeHtml(a.monto || '0') + ' ' + (window.APP_CURRENCY_SYMBOL || '$') + '</td><td>' + escapeHtml(a.tipo_nombre || a.tipopago || '-') + '</td>';
                if (tieneEditable) {
                    html += '<td class="text-end text-nowrap">';
                    if (abonoId) {
                        html += '<button type="button" class="btn btn-sm btn-outline-primary btn-editar-abono me-1" title="Editar pago" data-registro-id="' + escapeHtml(rid) + '" data-abono-id="' + escapeHtml(abonoId) + '" data-tipopago="' + escapeHtml(a.tipopago || '1') + '" data-monto="' + escapeHtml(a.monto || '0') + '" data-fecha="' + escapeHtml(fecha) + '"><i class="fa-solid fa-pen"></i></button>';
                        html += '<button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-abono" title="Eliminar pago" data-registro-id="' + escapeHtml(rid) + '" data-abono-id="' + escapeHtml(abonoId) + '" data-monto="' + escapeHtml(a.monto || '0') + '" data-fecha="' + escapeHtml(fecha) + '"><i class="fa-solid fa-trash"></i></button>';
                    } else {
                        html += '<span class="text-muted small">—</span>';
                    }
                    html += '</td>';
                }
                html += '</tr>';
            });
            html += '</tbody></table></div>';
            if (!tieneEditable) {
                html += '<p class="small text-muted mb-0"><i class="fa-solid fa-circle-info me-1"></i>Pago histórico sin detalle individual; no se puede editar ni eliminar por abono.</p>';
            }
        } else {
            html += '<p class="text-muted small mb-0">Sin pagos registrados.</p>';
        }
        html += '</div>';
        html += '<div><h6 class="border-bottom pb-2">Pruebas realizadas</h6>';
        if (d.pruebas && d.pruebas.length > 0) {
            html += '<ul class="list-group list-group-flush">';
            d.pruebas.forEach(function(p) {
                html += '<li class="list-group-item d-flex justify-content-between"><span>' + escapeHtml(p.nombre || '-') + '</span>';
                if (p.categoria) html += '<span class="text-muted small">' + escapeHtml(p.categoria) + '</span>';
                html += '</li>';
            });
            html += '</ul>';
            html += '<p class="mt-2 small text-muted">Resultados: ' + escapeHtml(d.tiene_resultados ? d.regvalues_count + ' valores registrados' : 'Sin resultados') + '</p>';
        } else {
            html += '<p class="text-muted">No hay pruebas registradas.</p>';
        }
        html += '</div>';
        return html;
    }

    function cargarHistorial(rid, mostrarModal) {
        if (!rid) return;
        historialRegistroIdActual = rid;
        if (historialContent) historialContent.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Cargando...</p></div>';
        if (mostrarModal && typeof bootstrap !== 'undefined' && modalHistorial) {
            new bootstrap.Modal(modalHistorial).show();
        }
        fetch('<?= site_url('registers/historial') ?>/' + rid)
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (!res.success || !res.data) {
                    if (historialContent) historialContent.innerHTML = '<p class="text-danger">Error al cargar el historial.</p>';
                    return;
                }
                if (historialContent) historialContent.innerHTML = renderHistorialHtml(rid, res.data);
            })
            .catch(function() {
                if (historialContent) historialContent.innerHTML = '<p class="text-danger">Error de conexión.</p>';
            });
    }

    document.querySelectorAll('.btn-historial').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var rid = this.getAttribute('data-id');
            cargarHistorial(rid, true);
        });
    });

    if (historialContent) {
        historialContent.addEventListener('click', function(e) {
            var btnEdit = e.target.closest('.btn-editar-abono');
            if (btnEdit) {
                var rid = btnEdit.getAttribute('data-registro-id') || '';
                var abonoId = btnEdit.getAttribute('data-abono-id') || '';
                var tipopago = btnEdit.getAttribute('data-tipopago') || '1';
                var monto = btnEdit.getAttribute('data-monto') || '0';
                var fecha = btnEdit.getAttribute('data-fecha') || '';
                if (editAbonoRegistroId) editAbonoRegistroId.value = rid;
                if (editAbonoId) editAbonoId.value = abonoId;
                if (editAbonoTipopago) editAbonoTipopago.value = tipopago;
                if (editAbonoMonto) editAbonoMonto.value = monto;
                if (editAbonoResumen) {
                    editAbonoResumen.textContent = 'Pago registrado el ' + fecha;
                }
                if (typeof bootstrap !== 'undefined' && modalEditarTipoPago) {
                    new bootstrap.Modal(modalEditarTipoPago).show();
                }
                return;
            }
            var btnDel = e.target.closest('.btn-eliminar-abono');
            if (btnDel) {
                var ridDel = btnDel.getAttribute('data-registro-id') || '';
                var abonoIdDel = btnDel.getAttribute('data-abono-id') || '';
                var montoDel = btnDel.getAttribute('data-monto') || '0';
                var fechaDel = btnDel.getAttribute('data-fecha') || '';
                var msg = '¿Eliminar el pago del ' + fechaDel + ' por ' + montoDel + ' ' + (window.APP_CURRENCY_SYMBOL || '$') + '? Se actualizará el saldo de la orden.';
                var ejecutarEliminar = function() {
                    var body = 'pago_abono_id=' + encodeURIComponent(abonoIdDel) + getCsrfBody();
                    fetch('<?= site_url('registers/eliminarAbonoPago') ?>/' + ridDel, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                        body: body
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(res) {
                        if (res.success) {
                            cargarHistorial(ridDel, false);
                        } else {
                            mostrarMensajeModal('Error', res.message || 'Error al eliminar');
                        }
                    })
                    .catch(function() { mostrarMensajeModal('Error', 'Error de conexión'); });
                };
                if (typeof uiConfirm === 'function') {
                    uiConfirm(msg, 'Confirmar eliminación').then(function(ok) {
                        if (ok) ejecutarEliminar();
                    });
                } else if (window.confirm(msg)) {
                    ejecutarEliminar();
                }
            }
        });
    }

    if (btnGuardarTipoPago) {
        btnGuardarTipoPago.addEventListener('click', function() {
            var rid = editAbonoRegistroId ? editAbonoRegistroId.value : '';
            var abonoId = editAbonoId ? editAbonoId.value : '';
            var tipopago = editAbonoTipopago ? editAbonoTipopago.value : '';
            var monto = editAbonoMonto ? editAbonoMonto.value : '';
            if (!rid || !abonoId) return;
            if (!monto || parseFloat(String(monto).replace(',', '.')) <= 0) {
                mostrarMensajeModal('Error', 'El monto debe ser mayor a 0');
                return;
            }
            var body = 'pago_abono_id=' + encodeURIComponent(abonoId) + '&tipopago=' + encodeURIComponent(tipopago) + '&monto=' + encodeURIComponent(monto) + getCsrfBody();
            fetch('<?= site_url('registers/editarAbonoPago') ?>/' + rid, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: body
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    if (typeof bootstrap !== 'undefined' && modalEditarTipoPago) {
                        var inst = bootstrap.Modal.getInstance(modalEditarTipoPago);
                        if (inst) inst.hide();
                    }
                    cargarHistorial(rid, false);
                } else {
                    mostrarMensajeModal('Error', res.message || 'Error al guardar');
                }
            })
            .catch(function() { mostrarMensajeModal('Error', 'Error de conexión'); });
        });
    }

    // Modal WhatsApp
    var modalWhatsapp = document.getElementById('modalWhatsapp');
    var btnWhatsappList = document.querySelectorAll('.btn-whatsapp-pdf');
    var waRegistroId = document.getElementById('wa_registro_id');
    var btnEnviarWhatsapp = document.getElementById('btnEnviarWhatsapp');
    var waAlertSinTelefono = document.getElementById('wa_alert_sin_telefono');

    btnWhatsappList.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var paciente = this.getAttribute('data-paciente') || '-';
            var doctor = this.getAttribute('data-doctor') || '-';
            var pacientePhone = this.getAttribute('data-paciente-phone') || '';
            var doctorPhone = this.getAttribute('data-doctor-phone') || '';
            if (waRegistroId) waRegistroId.value = id;
            document.getElementById('wa_paciente_phone').value = pacientePhone;
            document.getElementById('wa_doctor_phone').value = doctorPhone;
            document.getElementById('wa_paciente_nombre').textContent = paciente;
            document.getElementById('wa_doctor_nombre').textContent = doctor;
            document.getElementById('wa_paciente_phone_badge').textContent = pacientePhone ? '✓' : 'Sin teléfono';
            document.getElementById('wa_paciente_phone_badge').className = 'badge ms-1 ' + (pacientePhone ? 'bg-success' : 'bg-danger');
            document.getElementById('wa_doctor_phone_badge').textContent = doctorPhone ? '✓' : 'Sin teléfono';
            document.getElementById('wa_doctor_phone_badge').className = 'badge ms-1 ' + (doctorPhone ? 'bg-success' : 'bg-danger');
            if (waAlertSinTelefono) waAlertSinTelefono.style.display = 'none';
            if (modalWhatsapp && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                new bootstrap.Modal(modalWhatsapp).show();
            }
        });
    });

    if (btnEnviarWhatsapp) {
        btnEnviarWhatsapp.addEventListener('click', function() {
            var id = waRegistroId ? waRegistroId.value : '';
            if (!id) return;
            var dest = document.querySelector('input[name="wa_destinatario"]:checked');
            var destinatarios = dest ? dest.value : 'ambos';
            var pacientePhone = (document.getElementById('wa_paciente_phone') || {}).value || '';
            var doctorPhone = (document.getElementById('wa_doctor_phone') || {}).value || '';
            if ((destinatarios === 'paciente' && !pacientePhone) || (destinatarios === 'doctor' && !doctorPhone)) {
                if (waAlertSinTelefono) {
                    waAlertSinTelefono.style.display = 'block';
                }
                return;
            }
            if (destinatarios === 'ambos' && !pacientePhone && !doctorPhone) {
                if (waAlertSinTelefono) {
                    waAlertSinTelefono.textContent = 'Ninguno tiene número de teléfono registrado.';
                    waAlertSinTelefono.style.display = 'block';
                }
                return;
            }
            btnEnviarWhatsapp.disabled = true;
            btnEnviarWhatsapp.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enviando...';
            var csrf = (typeof CI_CSRF_TOKEN !== 'undefined' && typeof CI_CSRF_TOKEN_NAME !== 'undefined')
                ? '&' + CI_CSRF_TOKEN_NAME + '=' + encodeURIComponent(CI_CSRF_TOKEN) : '';
            var body = 'destinatarios=' + encodeURIComponent(destinatarios) + csrf;
            fetch('<?= site_url('registers/sendWhatsapp') ?>/' + id, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: body
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (typeof bootstrap !== 'undefined' && modalWhatsapp) {
                    var m = bootstrap.Modal.getInstance(modalWhatsapp);
                    if (m) m.hide();
                }
                mostrarMensajeModal('Resultado', res.message || (res.success ? 'Enviado.' : 'Error.'));
                if (res.success) location.reload();
            })
            .catch(function() { mostrarMensajeModal('Error', 'Error de conexión'); })
            .finally(function() {
                btnEnviarWhatsapp.disabled = false;
                btnEnviarWhatsapp.innerHTML = '<i class="fa-brands fa-whatsapp me-1"></i>Enviar';
            });
        });
    }

    var modalAnular = document.getElementById('modalAnularRegistro');
    var anularRegistroId = document.getElementById('anular_registro_id');
    var anularMotivo = document.getElementById('anular_motivo');
    var btnConfirmarAnular = document.getElementById('btnConfirmarAnular');

    document.querySelectorAll('.btn-anular-registro').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            if (!id || !modalAnular || !anularRegistroId || !anularMotivo) return;
            anularRegistroId.value = id;
            anularMotivo.value = '';
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                new bootstrap.Modal(modalAnular).show();
            }
        });
    });

    if (btnConfirmarAnular && anularRegistroId && anularMotivo) {
        btnConfirmarAnular.addEventListener('click', function() {
            var id = anularRegistroId.value;
            var motivo = (anularMotivo.value || '').trim();
            if (!id) return;
            if (motivo.length < 5) {
                mostrarMensajeModal('Validación', 'Indique el motivo de anulación (mínimo 5 caracteres).');
                return;
            }
            var csrf = (typeof CI_CSRF_TOKEN !== 'undefined' && typeof CI_CSRF_TOKEN_NAME !== 'undefined')
                ? CI_CSRF_TOKEN_NAME + '=' + encodeURIComponent(CI_CSRF_TOKEN) : '';
            var body = 'motivo_anulacion=' + encodeURIComponent(motivo) + (csrf ? '&' + csrf : '');
            btnConfirmarAnular.disabled = true;
            fetch('<?= site_url('registers/delete') ?>/' + id, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: body
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    location.reload();
                } else {
                    mostrarMensajeModal('Error', res.message || 'Error al anular');
                }
            })
            .catch(function() { mostrarMensajeModal('Error', 'Error de conexión'); })
            .finally(function() { btnConfirmarAnular.disabled = false; });
        });
    }
});
</script>
<?= $this->endSection() ?>
