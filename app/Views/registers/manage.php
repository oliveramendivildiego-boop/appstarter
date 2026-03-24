<?= $this->extend('layouts/main') ?>
<?php $pageTitle = !empty($edit_registro) ? 'Editar orden' : 'Nuevo registro'; ?>
<?= $this->section('title') ?><?= esc($pageTitle) ?><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$pruebasLookup = [];
foreach ($categories ?? [] as $cat) {
    $padreName = $cat['name'] ?? '';
    foreach ($cat['items'] ?? [] as $item) {
        $pruebasLookup[(string)($item['id'] ?? '')] = [
            'name' => $item['name'] ?? '',
            'padre' => $padreName,
            'cost' => (float)($item['cost'] ?? 0)
        ];
    }
}
?>
<script>window.PRUEBAS_LOOKUP = <?= json_encode($pruebasLookup) ?>;</script>
<?php
$editPayload = null;
if (!empty($edit_registro)) {
    $editPayload = [
        'registro_id' => (int)($edit_registro->registro_id ?? 0),
        'person_id'   => (int)($edit_registro->person_id ?? 0),
        'doctor_id'   => (int)($edit_registro->doctor_id ?? 0),
        'paciente'    => trim((string)(($edit_registro->first_name ?? '') . ' ' . ($edit_registro->last_name_fa ?? ''))),
        'doctor'      => trim((string)($edit_registro->doctor_name ?? '')),
        'prioridad'   => (int)($edit_registro->prioridad ?? 0),
        'pruebas'     => (string)($edit_registro->pruebas ?? ''),
        'pago'        => [
            'total_reco'  => $edit_pago->total_reco ?? '',
            'total'       => $edit_pago->total ?? '',
            'monto_pagar' => $edit_pago->monto_pagar ?? '',
            'tipopago'    => $edit_pago->tipopago ?? '',
            'saldo'       => $edit_pago->saldo ?? '',
            'comentarios' => $edit_pago->comentarios ?? '',
        ],
    ];
}
?>
<script>window.EDIT_REGISTRO = <?= json_encode($editPayload) ?>;</script>
<?= view('partial/breadcrumb_nav', [
    'items' => [['label' => lang('Module.module_registers'), 'url' => site_url('registers')]],
    'right' => '<a href="' . site_url('registers/lista') . '" class="btn btn-outline-primary">Ver lista</a>' .
        '<a href="' . site_url('expediente') . '" class="btn btn-outline-info">Historial paciente</a>',
]) ?>

<div id="registers_form_error" class="alert alert-danger" style="display:none;"></div>
<div class="row">
    <div class="col-md-8 mb-3">
        <?= view('registers/form_basic_info') ?>
        <div id="pruebas_error" class="text-danger small mb-2" style="display:none;"></div>
        <div class="mb-3">
            <label class="form-label">Pruebas seleccionadas:</label>
            <div id="pruebas_lista" class="border rounded p-2 bg-light" style="min-height:60px;">
                <p class="text-muted small mb-0">Use el buscador para agregar pruebas. La lista aparecerá aquí.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <?= view('registers/form_pagos') ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('search_prueba_input');
    var pruebaListDropdown = document.getElementById('prueba_list');
    var pruebaListaContainer = document.getElementById('pruebas_lista');
    var guardarBtn = document.getElementById('guardar');
    var pruebasSeleccionadas = []; // {id, name, padre, cost}
    var editInfo = (typeof window.EDIT_REGISTRO !== 'undefined') ? window.EDIT_REGISTRO : null;

    function recalcular() {
        var totalCost = 0;
        pruebasSeleccionadas.forEach(function(p) { totalCost += p.cost; });
        var val = totalCost.toFixed(2);
        var reco = document.getElementById('total_reco');
        var tot = document.getElementById('total');
        if (reco) reco.value = val;
        if (tot) tot.value = val;
    }

    function renderPruebasLista() {
        if (!pruebaListaContainer) return;
        pruebaListaContainer.innerHTML = '';
        if (pruebasSeleccionadas.length === 0) {
            pruebaListaContainer.innerHTML = '<p class="text-muted small mb-0">Use el buscador para agregar pruebas. La lista aparecerá aquí.</p>';
        } else {
            pruebasSeleccionadas.forEach(function(p, idx) {
                var row = document.createElement('div');
                row.className = 'd-flex align-items-center justify-content-between py-2 border-bottom pruebaitem';
                row.dataset.id = p.id;
                var displayName = (p.name || '');
                if (p.padre) displayName += ' <span class="text-muted small">(' + p.padre + ')</span>';
                row.innerHTML = '<span class="flex-grow-1">' + displayName + '</span>' +
                    '<span class="badge bg-secondary me-2">' + (p.cost || 0) + ' Bs</span>' +
                    '<button type="button" class="btn btn-outline-danger btn-sm quitar-prueba" data-idx="' + idx + '" title="Eliminar"><i class="fa-solid fa-times"></i></button>';
                pruebaListaContainer.appendChild(row);
            });
        }
        recalcular();
    }

    function agregarPrueba(item) {
        if (!item || !item.data) return;
        if (pruebasSeleccionadas.some(function(p) { return String(p.id) === String(item.data); })) return;
        pruebasSeleccionadas.push({
            id: item.data,
            name: item.value || '',
            padre: item.padre || '',
            cost: parseFloat(item.cost || 0)
        });
        renderPruebasLista();
    }

    function quitarPrueba(idx) {
        pruebasSeleccionadas.splice(idx, 1);
        renderPruebasLista();
    }


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
            var pruebas = pruebasSeleccionadas.map(function(p) { return p.id; });
            var pruebasStr = pruebas.join(',');
            var prioridad = (document.getElementById('prioridad') && document.getElementById('prioridad').value) || '0';
            var registroData = {
                person_id: (document.getElementById('person_id') || {}).value || '',
                doctor_id: (document.getElementById('doctor_id') || {}).value || '',
                pruebas: pruebasStr,
                prioridad: prioridad
            };
            var tipopagoVal = (document.getElementById('tipopago') || {}).value || '';
            var esPendiente = tipopagoVal === '4';
            var montoPagarRaw = ((document.getElementById('monto_pagar') || {}).value || '').trim();
            var totalNum = parseFloat((document.getElementById('total') || {}).value || 0);
            var saldoEl = document.getElementById('saldo');
            if (esPendiente && montoPagarRaw === '') {
                if (saldoEl) saldoEl.value = (!isNaN(totalNum) ? (totalNum - 0).toFixed(2) : '');
            }
            var pagosData = {
                total_reco: (document.getElementById('total_reco') || {}).value || '',
                total: (document.getElementById('total') || {}).value || '',
                monto_pagar: esPendiente && montoPagarRaw === '' ? '0' : (document.getElementById('monto_pagar') || {}).value || '',
                tipopago: tipopagoVal,
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
                mostrarError(tr, 'Total Recomendado es obligatorio.'); primero = primero || tr;
            }
            if (!pagosData.total || isNaN(parseFloat(pagosData.total))) {
                var tot = document.getElementById('total');
                mostrarError(tot, 'Total es obligatorio.'); primero = primero || tot;
            }
            if (!esPendiente) {
                if (!pagosData.monto_pagar || isNaN(parseFloat(pagosData.monto_pagar))) {
                    var mp = document.getElementById('monto_pagar');
                    mostrarError(mp, 'Monto a pagar es obligatorio.'); primero = primero || mp;
                }
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
            var urlGuardar = editInfo && editInfo.registro_id ? ('<?= site_url('registers/update') ?>/' + editInfo.registro_id) : '<?= site_url('registers/save') ?>';
            fetch(urlGuardar, {
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

    // Prefill al editar orden
    if (editInfo && editInfo.registro_id) {
        try {
            var personIdEl = document.getElementById('person_id');
            var doctorIdEl = document.getElementById('doctor_id');
            var pacienteEl = document.getElementById('paciente');
            var doctorEl = document.getElementById('doctor');
            var prioridadEl = document.getElementById('prioridad');
            if (personIdEl) personIdEl.value = String(editInfo.person_id || '');
            if (doctorIdEl) doctorIdEl.value = String(editInfo.doctor_id || '');
            if (pacienteEl) pacienteEl.value = String(editInfo.paciente || '');
            if (doctorEl) doctorEl.value = String(editInfo.doctor || '');
            if (prioridadEl) prioridadEl.value = String(editInfo.prioridad || '0');

            // Pago
            var p = editInfo.pago || {};
            var totalEl = document.getElementById('total');
            var totalRecoEl = document.getElementById('total_reco');
            var montoEl = document.getElementById('monto_pagar');
            var tipopagoEl = document.getElementById('tipopago');
            var saldoEl = document.getElementById('saldo');
            var comentariosEl = document.getElementById('comentarios');
            if (tipopagoEl) tipopagoEl.value = String(p.tipopago || '');
            if (montoEl) montoEl.value = String(p.monto_pagar ?? '');
            if (comentariosEl) comentariosEl.value = String(p.comentarios ?? '');
            if (totalEl) totalEl.value = String(p.total ?? '');
            if (saldoEl) saldoEl.value = String(p.saldo ?? '');

            // Pruebas
            var pruebasStr = String(editInfo.pruebas || '').trim();
            if (pruebasStr) {
                pruebasStr.split(',').map(function(x) { return String(parseInt(x, 10)); })
                    .filter(function(x) { return x !== 'NaN'; })
                    .forEach(function(id) {
                        var info = (window.PRUEBAS_LOOKUP || {})[id];
                        if (info) {
                            agregarPrueba({ value: info.name, padre: info.padre, data: id, cost: info.cost });
                        }
                    });
            }

            // total_reco se recalcula por pruebas (readonly), pero si viene algo y no hay lookup completo, lo mostramos.
            if (totalRecoEl && (totalRecoEl.value === '' || totalRecoEl.value === '0.00')) {
                totalRecoEl.value = String(p.total_reco ?? totalRecoEl.value);
            }
            renderPruebasLista();
        } catch (e) {}
    }

    // Perfil rápido: agregar pruebas del perfil a la lista
    var perfilSel = document.getElementById('perfil_rapido');
    if (perfilSel && typeof window.PRUEBAS_LOOKUP !== 'undefined') {
        perfilSel.addEventListener('change', function() {
            var pruebasStr = this.value;
            if (!pruebasStr) return;
            var ids = pruebasStr.split(',').map(function(x) { return String(parseInt(x, 10)); }).filter(function(x) { return x !== 'NaN'; });
            ids.forEach(function(id) {
                var info = window.PRUEBAS_LOOKUP[id];
                if (info) agregarPrueba({ value: info.name, padre: info.padre, data: id, cost: info.cost });
            });
        });
    }

    // Delegación para botones eliminar
    if (pruebaListaContainer) {
        pruebaListaContainer.addEventListener('click', function(e) {
            var btn = e.target.closest('.quitar-prueba');
            if (btn) quitarPrueba(parseInt(btn.dataset.idx, 10));
        });
    }

    // Autocomplete para búsqueda de pruebas
    var pruebaSearchTimeout;
    if (searchInput) {
        searchInput.addEventListener('focus', function() { this.select(); });
        searchInput.addEventListener('blur', function() {
            setTimeout(function() { if (pruebaListDropdown) pruebaListDropdown.style.display = 'none'; }, 200);
        });
        searchInput.addEventListener('input', function() {
            clearTimeout(pruebaSearchTimeout);
            var q = this.value.trim();
            if (q.length < 2) {
                if (pruebaListDropdown) { pruebaListDropdown.innerHTML = ''; pruebaListDropdown.style.display = 'none'; }
                return;
            }
            pruebaSearchTimeout = setTimeout(function() {
                fetch('<?= site_url('registers/search_prueba') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: 'prueba=' + encodeURIComponent(q)
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!pruebaListDropdown) return;
                    pruebaListDropdown.innerHTML = '';
                    if (!data || !data.length) {
                        pruebaListDropdown.style.display = 'none';
                        return;
                    }
                    data.forEach(function(item) {
                        var li = document.createElement('div');
                        li.className = 'list-group-item list-group-item-action';
                        li.style.cursor = 'pointer';
                        var label = item.value;
                        if (item.padre) label += ' <span class="text-muted small">(' + item.padre + ')</span>';
                        li.innerHTML = label + ' <span class="badge bg-secondary float-end">' + (item.cost || 0) + ' Bs</span>';
                        li.addEventListener('click', function() {
                            agregarPrueba(item);
                            searchInput.value = '';
                            pruebaListDropdown.style.display = 'none';
                        });
                        pruebaListDropdown.appendChild(li);
                    });
                    pruebaListDropdown.style.display = 'block';
                })
                .catch(function() { if (pruebaListDropdown) pruebaListDropdown.style.display = 'none'; });
            }, 300);
        });
    }
    document.addEventListener('click', function(e) {
        var c = document.getElementById('prueba_search_container');
        if (pruebaListDropdown && c && !c.contains(e.target)) pruebaListDropdown.style.display = 'none';
    });
});
</script>
<?= $this->endSection() ?>
