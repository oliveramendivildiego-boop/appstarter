<?= view('partial/header', ['allowed_modules' => $allowed_modules ?? [], 'user_info' => $user_info ?? null, 'current_module' => 'registers']) ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
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
                else { alert(res.message || 'Error al guardar'); }
            })
            .catch(function() { alert('Error de conexión'); });
        });
    }

    // Modal Historial
    var modalHistorial = document.getElementById('modalHistorial');
    var historialContent = document.getElementById('historialContent');
    document.querySelectorAll('.btn-historial').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var rid = this.getAttribute('data-id');
            if (!rid) return;
            if (historialContent) historialContent.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Cargando...</p></div>';
            if (typeof bootstrap !== 'undefined' && modalHistorial) new bootstrap.Modal(modalHistorial).show();
            fetch('<?= site_url('registers/historial') ?>/' + rid)
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (!res.success || !res.data) {
                        if (historialContent) historialContent.innerHTML = '<p class="text-danger">Error al cargar el historial.</p>';
                        return;
                    }
                    var d = res.data;
                    var reg = d.registro || {};
                    var pago = d.pago || {};
                    var html = '<div class="mb-3"><h6 class="border-bottom pb-2">Datos del registro</h6>';
                    html += '<p class="mb-1"><strong>Orden:</strong> ' + (reg.registro_id || '-') + '</p>';
                    html += '<p class="mb-1"><strong>Paciente:</strong> ' + (reg.paciente || '-') + '</p>';
                    html += '<p class="mb-1"><strong>Doctor:</strong> ' + (reg.doctor || '-') + '</p>';
                    html += '<p class="mb-0"><strong>Fecha ingreso:</strong> ' + (reg.ingreso ? new Date(reg.ingreso).toLocaleString('es') : '-') + '</p></div>';
                    html += '<div class="mb-3"><h6 class="border-bottom pb-2">Historial de pagos</h6>';
                    html += '<p class="mb-2"><strong>Total orden:</strong> ' + (pago.total || '0') + ' Bs &nbsp;|&nbsp; <strong>Pagado:</strong> ' + (pago.monto_pagar || '0') + ' Bs &nbsp;|&nbsp; <strong>Saldo:</strong> ' + (pago.saldo || '0') + ' Bs</p>';
                    if (d.abonos && d.abonos.length > 0) {
                        html += '<table class="table table-sm table-bordered"><thead><tr><th>Fecha</th><th>Monto</th><th>Método</th></tr></thead><tbody>';
                        d.abonos.forEach(function(a) {
                            var fecha = a.fecha_abono ? new Date(a.fecha_abono).toLocaleString('es') : '-';
                            html += '<tr><td>' + fecha + '</td><td>' + (a.monto || '0') + ' Bs</td><td>' + (a.tipo_nombre || a.tipopago || '-') + '</td></tr>';
                        });
                        html += '</tbody></table>';
                    } else {
                        html += '<p class="text-muted small mb-0">Sin pagos registrados.</p>';
                    }
                    html += '</div>';
                    html += '<div><h6 class="border-bottom pb-2">Pruebas realizadas</h6>';
                    if (d.pruebas && d.pruebas.length > 0) {
                        html += '<ul class="list-group list-group-flush">';
                        d.pruebas.forEach(function(p) {
                            html += '<li class="list-group-item d-flex justify-content-between"><span>' + (p.nombre || '-') + '</span>';
                            if (p.categoria) html += '<span class="text-muted small">' + p.categoria + '</span>';
                            html += '</li>';
                        });
                        html += '</ul>';
                        html += '<p class="mt-2 small text-muted">Resultados: ' + (d.tiene_resultados ? d.regvalues_count + ' valores registrados' : 'Sin resultados') + '</p>';
                    } else {
                        html += '<p class="text-muted">No hay pruebas registradas.</p>';
                    }
                    html += '</div>';
                    if (historialContent) historialContent.innerHTML = html;
                })
                .catch(function() {
                    if (historialContent) historialContent.innerHTML = '<p class="text-danger">Error de conexión.</p>';
                });
        });
    });

    document.querySelectorAll('.btn-eliminar-registro').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            if (!id) return;
            if (!confirm('¿Eliminar el registro #' + id + '? Esta acción no se puede deshacer.')) return;
            var csrf = (typeof CI_CSRF_TOKEN !== 'undefined' && typeof CI_CSRF_TOKEN_NAME !== 'undefined')
                ? '&' + CI_CSRF_TOKEN_NAME + '=' + encodeURIComponent(CI_CSRF_TOKEN) : '';
            fetch('<?= site_url('registers/delete') ?>/' + id, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: csrf ? csrf.substring(1) : ''
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) location.reload();
                else alert(res.message || 'Error al eliminar');
            })
            .catch(function() { alert('Error de conexión'); });
        });
    });
});
</script>

<?= view('partial/breadcrumb_nav', [
    'items' => [
        ['label' => lang('Module.module_registers'), 'url' => site_url('registers/lista')],
        ['label' => 'Registro', 'url' => null],
    ],
    'right' => '<a href="' . site_url('registers') . '" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i> Registrar</a> ' .
        '<a href="' . site_url('expediente') . '" class="btn btn-outline-info btn-sm"><i class="fa-solid fa-folder-open me-1"></i> Historial paciente</a>',
]) ?>

<div class="row mb-3">
    <div class="col-md-12">
        <form method="get" action="<?= site_url('registers/lista') ?>" class="d-flex flex-wrap gap-2 align-items-center">
            <input type="text" name="q" class="form-control" style="max-width:280px;" placeholder="Buscar por código de prueba, nombre, apellidos o CI del paciente..." value="<?= esc($search ?? '') ?>">
            <select name="estado" class="form-select" style="max-width:180px;">
                <option value="">Todos</option>
                <option value="completo" <?= ($estado ?? '') === 'completo' ? 'selected' : '' ?>>Completos (con resultados)</option>
                <option value="incompleto" <?= ($estado ?? '') === 'incompleto' ? 'selected' : '' ?>>Incompletos (sin resultados)</option>
            </select>
            <button type="submit" class="btn btn-outline-primary"><i class="fa-solid fa-search"></i> Buscar</button>
            <?php if (!empty($search) || !empty($estado ?? '')): ?>
            <a href="<?= site_url('registers/lista') ?>" class="btn btn-outline-secondary">Limpiar</a>
            <?php endif; ?>
        </form>
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
        <p class="text-muted small">Mostrando <?= $desde ?>–<?= $hasta ?> de <?= $totalReg ?> registro(s)</p>
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
        $searchParam = !empty($search) ? 'q=' . urlencode($search) . '&' : '';
        if (!empty($estado ?? '')) $searchParam .= 'estado=' . urlencode($estado) . '&';
        for ($i = 1; $i <= ($totalPages ?? 1); $i++):
        ?>
        <li class="page-item <?= ($i === $pageNum) ? 'active' : '' ?>">
            <a class="page-link" href="<?= site_url('registers/lista') . '?' . $searchParam . 'page=' . $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?= view('partial/footer') ?>
