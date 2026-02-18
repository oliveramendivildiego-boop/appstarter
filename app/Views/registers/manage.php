<?= view('partial/header', ['allowed_modules' => $allowed_modules ?? [], 'user_info' => $user_info ?? null, 'current_module' => 'registers']) ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('searchInput');
    var guardarBtn = document.getElementById('guardar');

    function recalcular() {
        var totalCost = 0, totalRefe = 0;
        document.querySelectorAll('.contador:checked').forEach(function(cb) {
            totalCost += parseFloat(cb.getAttribute('cost') || 0);
            totalRefe += parseFloat(cb.getAttribute('refe') || 0);
        });
        var el = document.getElementById('total_reco');
        if (el) el.value = totalCost.toFixed(2);
    }

    document.querySelectorAll('.contador').forEach(function(cb) {
        cb.addEventListener('change', recalcular);
    });

    document.getElementById('total_reco').addEventListener('click', function() {
        document.getElementById('total').value = this.value;
    });

    function quitarInvalid() {
        document.querySelectorAll('.is-invalid').forEach(function(el) { el.classList.remove('is-invalid'); });
        document.querySelectorAll('[id$="_error"]').forEach(function(el) { el.textContent = ''; });
        var pe = document.getElementById('pruebas_error');
        if (pe) { pe.textContent = ''; pe.style.display = 'none'; }
        var rfe = document.getElementById('registers_form_error');
        if (rfe) { rfe.textContent = ''; rfe.style.display = 'none'; }
    }

    function mostrarError(el, msg) {
        if (!el) return;
        el.classList.add('is-invalid');
        var err = document.getElementById(el.id + '_error');
        if (err) { err.textContent = msg; }
    }

    if (guardarBtn) {
        guardarBtn.addEventListener('click', function(e) {
            e.preventDefault();
            quitarInvalid();
            var pruebas = [];
            document.querySelectorAll('.contador:checked').forEach(function(cb) {
                var id = (cb.getAttribute('id') || cb.value || '').replace(/^contador_/, '');
                if (id) pruebas.push(id);
            });
            var pruebasStr = pruebas.join(',');
            var prioridad = (document.getElementById('prioridad') && document.getElementById('prioridad').value) || '0';
            var registroData = {
                person_id: (document.getElementById('person_id') || {}).value || '',
                doctor_id: (document.getElementById('doctor_id') || {}).value || '',
                pruebas: pruebasStr,
                prioridad: prioridad
            };
            var pagosData = {
                total_reco: (document.getElementById('total_reco') || {}).value || '',
                total: (document.getElementById('total') || {}).value || '',
                monto_pagar: (document.getElementById('monto_pagar') || {}).value || '',
                tipopago: (document.getElementById('tipopago') || {}).value || '',
                saldo: (document.getElementById('saldo') || {}).value || '',
                comentarios: (document.getElementById('comentarios') || {}).value || ''
            };
            var primero = null;
            if (!registroData.person_id || registroData.person_id === '0') {
                var p = document.getElementById('paciente');
                mostrarError(p, 'Seleccione un paciente.'); primero = primero || p;
            }
            if (!registroData.doctor_id || registroData.doctor_id === '0') {
                var d = document.getElementById('doctor');
                mostrarError(d, 'Seleccione un doctor.'); primero = primero || d;
            }
            if (pruebasStr === '') {
                var pe = document.getElementById('pruebas_error');
                if (pe) { pe.textContent = 'Seleccione al menos una prueba.'; pe.style.display = 'block'; pe.className = 'text-danger small'; if (!primero) primero = pe; }
            }
            if (!pagosData.total_reco || parseFloat(pagosData.total_reco) <= 0) {
                var tr = document.getElementById('total_reco');
                mostrarError(tr, 'Total reco es obligatorio.'); primero = primero || tr;
            }
            if (!pagosData.total || isNaN(parseFloat(pagosData.total))) {
                var tot = document.getElementById('total');
                mostrarError(tot, 'Total es obligatorio.'); primero = primero || tot;
            }
            if (!pagosData.monto_pagar || isNaN(parseFloat(pagosData.monto_pagar))) {
                var mp = document.getElementById('monto_pagar');
                mostrarError(mp, 'Monto a pagar es obligatorio.'); primero = primero || mp;
            }
            if (!pagosData.tipopago) {
                var tp = document.getElementById('tipopago');
                mostrarError(tp, 'Seleccione tipo de pago.'); primero = primero || tp;
            }
            if (!pagosData.saldo || isNaN(parseFloat(pagosData.saldo))) {
                var s = document.getElementById('saldo');
                mostrarError(s, 'Saldo es obligatorio.'); primero = primero || s;
            }
            if (primero) {
                primero.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            var csrf = (typeof CI_CSRF_TOKEN !== 'undefined' && typeof CI_CSRF_TOKEN_NAME !== 'undefined')
                ? '&' + CI_CSRF_TOKEN_NAME + '=' + encodeURIComponent(CI_CSRF_TOKEN) : '';
            fetch('<?= site_url('registers/save') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: 'registro[person_id]=' + encodeURIComponent(registroData.person_id) +
                    '&registro[doctor_id]=' + encodeURIComponent(registroData.doctor_id) +
                    '&registro[pruebas]=' + encodeURIComponent(registroData.pruebas) +
                    '&registro[prioridad]=' + encodeURIComponent(registroData.prioridad) +
                    '&pagos[total_reco]=' + encodeURIComponent(pagosData.total_reco) +
                    '&pagos[total]=' + encodeURIComponent(pagosData.total) +
                    '&pagos[monto_pagar]=' + encodeURIComponent(pagosData.monto_pagar) +
                    '&pagos[tipopago]=' + encodeURIComponent(pagosData.tipopago) +
                    '&pagos[saldo]=' + encodeURIComponent(pagosData.saldo) +
                    '&pagos[comentarios]=' + encodeURIComponent(pagosData.comentarios) + csrf
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    window.location.href = '<?= site_url('registers/view') ?>/' + res.id;
                } else {
                    var errDiv = document.getElementById('registers_form_error');
                    if (errDiv) { errDiv.textContent = (res.message || 'Error al guardar.'); errDiv.className = 'alert alert-danger'; errDiv.style.display = 'block'; errDiv.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
                }
            })
            .catch(function() {
                var errDiv = document.getElementById('registers_form_error');
                if (errDiv) { errDiv.textContent = 'Error en la petición.'; errDiv.className = 'alert alert-danger'; errDiv.style.display = 'block'; }
            });
        });
    }

    // Perfil rápido: seleccionar perfil marca las pruebas correspondientes
    var perfilSel = document.getElementById('perfil_rapido');
    if (perfilSel) {
        perfilSel.addEventListener('change', function() {
            var pruebasStr = this.value;
            if (!pruebasStr) return;
            var ids = pruebasStr.split(',').map(function(x) { return parseInt(x, 10); }).filter(function(x) { return !isNaN(x); });
            document.querySelectorAll('.contador').forEach(function(cb) {
                var cbId = (cb.getAttribute('id') || '').replace(/^contador_/, '');
                var id = parseInt(cbId, 10);
                cb.checked = !isNaN(id) && ids.indexOf(id) >= 0;
            });
            recalcular();
        });
    }

    // Autocomplete para búsqueda de pruebas
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var q = this.value.trim();
            if (q.length < 2) return;
            fetch('<?= site_url('registers/search_prueba') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: 'prueba=' + encodeURIComponent(q)
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.length) {
                    var cb = document.getElementById(data[0].data);
                    if (cb && cb.classList.contains('contador')) {
                        cb.checked = !cb.checked;
                        recalcular();
                    }
                }
            });
        });
    }
});
</script>

<?= view('partial/breadcrumb_nav', [
    'items' => [['label' => lang('Module.module_registers'), 'url' => site_url('registers')]],
    'right' => '<input type="text" id="searchInput" name="search" class="form-control" placeholder="Buscar prueba..." style="max-width:200px">' .
        '<a href="' . site_url('registers/lista') . '" class="btn btn-outline-primary">Ver lista</a>' .
        '<a href="' . site_url('expediente') . '" class="btn btn-outline-info">Historial paciente</a>',
]) ?>

<div id="registers_form_error" class="alert alert-danger" style="display:none;"></div>
<div class="row">
    <div class="col-md-8 mb-3">
        <?= view('registers/form_basic_info') ?>
    </div>
    <div class="col-md-4 mb-3">
        <?= view('registers/form_pagos') ?>
    </div>
</div>

<div id="pruebas_error" class="text-danger small mb-2" style="display:none;"></div>
<div class="row">
    <?php foreach ($categories ?? [] as $cat): ?>
    <div class="col-12 col-md-6 col-lg-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white py-2">
                <strong><?= esc($cat['name']) ?></strong>
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($cat['items'] ?? [] as $item): ?>
                <li class="list-group-item d-flex align-items-center">
                    <input type="checkbox" id="contador_<?= (int)$item['id'] ?>" name="contador_<?= (int)$item['id'] ?>" class="form-check-input contador me-2"
                        cost="<?= (int)($item['cost'] ?? 0) ?>" refe="<?= (int)($item['cost_deriv'] ?? 0) ?>">
                    <span class="flex-grow-1"><?= esc($item['name']) ?></span>
                    <span class="badge bg-secondary"><?= (int)($item['cost'] ?? 0) ?> Bs</span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (empty($categories)): ?>
<div class="alert alert-info">No hay pruebas disponibles.</div>
<?php endif; ?>

<?= view('partial/footer') ?>
