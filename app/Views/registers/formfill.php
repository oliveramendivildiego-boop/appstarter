<?= view('partial/header', ['allowed_modules' => $allowed_modules ?? [], 'user_info' => $user_info ?? null, 'current_module' => 'registers']) ?>
<style>
/* Badge "Calculada": fondo configurable vía variables CSS (--badge-calculada-bg / --badge-calculada-color) */
.badge-calculada { background-color: var(--badge-calculada-bg, #6c757d); color: var(--badge-calculada-color, #fff); }
.sugerencia-calculada { cursor: pointer; }
.sugerencia-calculada:hover { text-decoration: underline; }
.input-sugerencia-aplicada { border-color: var(--bs-success, #198754) !important; box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25); }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var decimalesSugerencia = <?= json_encode(max(0, min(10, (int)($decimales_sugerencia ?? 2)))) ?>;
    function formatearSugerencia(val) {
        if (val === '' || val == null) return '';
        var n = parseFloat(String(val).replace(',', '.'));
        if (isNaN(n)) return val;
        if (n === Math.round(n)) return String(Math.round(n));
        return decimalesSugerencia === 0 ? String(Math.round(n)) : n.toFixed(decimalesSugerencia);
    }
    function buscarInputPorPrueba(nombre, formOrDoc) {
        var root = formOrDoc && formOrDoc.nodeType === 9 ? formOrDoc : (formOrDoc || document);
        var key = (nombre || '').trim().toLowerCase();
        if (key === '') return null;
        var els = root.querySelectorAll('input[data-prueba], select[data-prueba]');
        for (var j = 0; j < els.length; j++) {
            var attr = (els[j].getAttribute('data-prueba') || '').trim().toLowerCase();
            if (attr === key) return els[j];
        }
        return null;
    }
    /**
     * Evalúa data-formula sustituyendo:
     * - [Nombre] por el valor del input con data-prueba="Nombre" (mismo texto, sin distinguir mayúsculas).
     * - [valor] por el valor del mismo input (inputActual).
     * Solo devuelve resultado cuando todas las variables tienen valor numérico.
     */
    function evaluarFormulaPorNombres(expr, inputActual) {
        if (!expr || typeof expr !== 'string') return '';
        expr = expr.replace(/\s+/g, ' ').replace(/×/g, '*').replace(/÷/g, '/');
        var nombres = (expr.match(/\[([^\]]+)\]/g) || []).map(function(m) { return m.slice(1, -1).trim(); });
        var unicos = nombres.filter(function(v, i, a) { return a.indexOf(v) === i; });
        var resultExpr = expr;
        var root = inputActual && inputActual.closest('form') ? inputActual.closest('form') : document;
        for (var i = 0; i < unicos.length; i++) {
            var nom = unicos[i];
            var val = '';
            if (nom.toLowerCase() === 'valor' && inputActual) {
                val = (inputActual.value || '').trim().replace(',', '.');
            } else {
                var inp = buscarInputPorPrueba(nom, root);
                val = inp ? (inp.value || '').trim().replace(',', '.') : '';
            }
            if (val === '' || isNaN(parseFloat(val))) return '';
            var numVal = parseFloat(val);
            var re = new RegExp('\\[' + nom.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\]', 'gi');
            resultExpr = resultExpr.replace(re, String(numVal));
        }
        resultExpr = resultExpr.replace(/\s+/g, ' ');
        if (!/^[\d\s\+\-\*\/\(\)\.]+$/.test(resultExpr)) return '';
        try {
            var r = eval(resultExpr);
            return (typeof r === 'number' && !isNaN(r)) ? String(r) : '';
        } catch (e) { return ''; }
    }
    function evaluarFormula(expr) {
        if (!expr || typeof expr !== 'string') return '';
        expr = expr.replace(/\s+/g, ' ').replace(/×/g, '*').replace(/÷/g, '/');
        var ids = (expr.match(/c_\d+/g) || []).filter(function(v,i,a){ return a.indexOf(v)===i; });
        var reemplazos = {};
        for (var i = 0; i < ids.length; i++) {
            var el = document.getElementById(ids[i]);
            var v = el ? (el.value || '').trim() : '';
            if (v === '' || isNaN(parseFloat(v))) return '';
            reemplazos[ids[i]] = parseFloat(v);
        }
        var resultExpr = expr;
        ids.sort(function(a,b){ return b.length - a.length; });
        for (var i = 0; i < ids.length; i++) {
            resultExpr = resultExpr.split(ids[i]).join(reemplazos[ids[i]]);
        }
        if (!/^[\d\s\+\-\*\/\(\)\.]+$/.test(resultExpr)) return '';
        try {
            var r = eval(resultExpr);
            return (typeof r === 'number' && !isNaN(r)) ? String(r) : '';
        } catch (e) { return ''; }
    }
    function actualizarCalculadas() {
        document.querySelectorAll('.formula-calculada').forEach(function(el) {
            var f = el.getAttribute('data-formula');
            var valorSugerido = '';
            if (f) valorSugerido = evaluarFormulaPorNombres(f, el);
            if (valorSugerido === '' && f) valorSugerido = evaluarFormula(f);
            var valorMostrar = valorSugerido !== '' ? formatearSugerencia(valorSugerido) : '';
            var spanSug = document.querySelector('[data-sugerencia-for="' + el.id + '"]');
            if (spanSug) {
                spanSug.textContent = valorMostrar !== '' ? 'Sugerencia: ' + valorMostrar : 'Sugerencia: —';
                spanSug.setAttribute('data-valor', valorMostrar);
            }
            validarInputAlEscribir(el);
        });
    }
    function validarInputAlEscribir(el) {
        var min = el.getAttribute('data-min');
        var max = el.getAttribute('data-max');
        var msgEl = document.querySelector('[data-msg-for="' + el.id + '"]');
        if (!msgEl) return;
        var val = (el.value || '').trim().replace(',', '.');
        var num = val !== '' && !isNaN(parseFloat(val)) ? parseFloat(val) : NaN;
        var valorMostrar = val !== '' ? val : '';
        msgEl.textContent = '';
        msgEl.innerHTML = '';
        el.classList.remove('is-invalid');
        if ((min === null || min === '') && (max === null || max === '')) return;
        if (isNaN(num)) return;
        var minNum = (min !== null && min !== '') ? parseFloat(String(min).replace(',', '.')) : null;
        var maxNum = (max !== null && max !== '') ? parseFloat(String(max).replace(',', '.')) : null;
        if (minNum !== null && !isNaN(minNum) && num < minNum) {
            msgEl.innerHTML = 'Valor: <strong>' + valorMostrar + '</strong>. Por debajo del rango de referencia (mín. ' + min + ').';
            el.classList.add('is-invalid');
        } else if (maxNum !== null && !isNaN(maxNum) && num > maxNum) {
            msgEl.innerHTML = 'Valor: <strong>' + valorMostrar + '</strong>. Por encima del rango de referencia (máx. ' + max + ').';
            el.classList.add('is-invalid');
        }
    }
    document.addEventListener('click', function(e) {
        var t = e.target.closest('.aplicar-valor');
        if (t && t.dataset.valor !== undefined && t.dataset.inputId) {
            var inp = document.getElementById(t.dataset.inputId);
            if (inp) {
                inp.value = t.dataset.valor;
                inp.classList.remove('is-invalid');
                var msgEl = document.querySelector('[data-msg-for="' + inp.id + '"]');
                if (msgEl) { msgEl.textContent = ''; msgEl.innerHTML = ''; }
                actualizarCalculadas();
                validarInputAlEscribir(inp);
            }
            return;
        }
        var sug = e.target.closest('.sugerencia-calculada');
        if (sug && sug.dataset.sugerenciaFor && sug.dataset.valor !== undefined && sug.dataset.valor !== '') {
            var inp = document.getElementById(sug.dataset.sugerenciaFor);
            if (inp) {
                inp.value = sug.dataset.valor;
                inp.classList.remove('is-invalid');
                var msgEl = document.querySelector('[data-msg-for="' + inp.id + '"]');
                if (msgEl) { msgEl.textContent = ''; msgEl.innerHTML = ''; }
                actualizarCalculadas();
                validarInputAlEscribir(inp);
                sug.textContent = 'Sugerencia: —';
                sug.removeAttribute('data-valor');
                inp.classList.add('input-sugerencia-aplicada');
            }
        }
    });
    document.querySelectorAll('.sugerencia-calculada').forEach(function(el) {
        el.addEventListener('keydown', function(e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); this.click(); } });
    });
    document.querySelectorAll('.form-control:not(.formula-calculada)').forEach(function(el) {
        el.addEventListener('input', function() { actualizarCalculadas(); validarInputAlEscribir(this); });
        el.addEventListener('change', function() { actualizarCalculadas(); validarInputAlEscribir(this); });
    });
    document.querySelectorAll('.formula-calculada').forEach(function(el) {
        el.addEventListener('input', function() { actualizarCalculadas(); validarInputAlEscribir(this); });
        el.addEventListener('change', function() { actualizarCalculadas(); validarInputAlEscribir(this); });
    });
    document.querySelectorAll('.input-con-ref').forEach(function(el) {
        el.addEventListener('input', function() { validarInputAlEscribir(this); });
        el.addEventListener('change', function() { validarInputAlEscribir(this); });
    });
    function validarAntesDeEnviar() {
        var faltantes = [];
        var fueraRango = [];
        document.querySelectorAll('.form-control').forEach(function(el) {
            var min = el.getAttribute('data-min');
            var max = el.getAttribute('data-max');
            var labelEl = document.querySelector('label[for="' + el.id + '"]');
            var label = labelEl ? (labelEl.textContent || el.id).trim() : el.id;
            var val = (el.value || '').trim();
            if (min !== null && min !== '' || max !== null && max !== '') {
                if (val === '') {
                    faltantes.push(label);
                    return;
                }
                var num = parseFloat(val.replace(',', '.'));
                if (!isNaN(num)) {
                    var minNum = (min !== null && min !== '') ? parseFloat(String(min).replace(',', '.')) : null;
                    var maxNum = (max !== null && max !== '') ? parseFloat(String(max).replace(',', '.')) : null;
                    if (minNum !== null && !isNaN(minNum) && num < minNum) fueraRango.push(label + ' (valor ' + val + ' < ' + min + ')');
                    else if (maxNum !== null && !isNaN(maxNum) && num > maxNum) fueraRango.push(label + ' (valor ' + val + ' > ' + max + ')');
                }
            }
        });
        if (faltantes.length || fueraRango.length) {
            var msg = '';
            if (faltantes.length) msg += 'Complete los siguientes campos: ' + faltantes.join(', ') + '.\n';
            if (fueraRango.length) msg += 'Valores fuera del rango de referencia: ' + fueraRango.join('; ') + '.\n';
            msg += '¿Desea enviar igualmente?';
            return window.confirm(msg + '\n\nCancelar = corregir datos. Aceptar = enviar de todos modos.');
        }
        return true;
    }

    var submitBtn = document.getElementById('submit');
    if (!submitBtn) return;
    submitBtn.addEventListener('click', function() {
        if (!validarAntesDeEnviar()) return;
        var datos = [];
        document.querySelectorAll('.form-control').forEach(function(el) {
            var valor = (el.value || '').trim();
            var registroId = document.getElementById('registro_id').value;
            if (!registroId || valor === '') return;
            var priId = el.getAttribute('data-prianacategoria-id');
            var nombrePrueba = el.getAttribute('data-prueba');
            var id = (priId && nombrePrueba) ? (priId + '|' + nombrePrueba) : el.id;
            datos.push({ id: id, valor: valor, registro_id: registroId });
        });
        var csrfName = (typeof window.CI_CSRF_TOKEN_NAME !== 'undefined' ? window.CI_CSRF_TOKEN_NAME : null) || (document.querySelector('meta[name="csrf-token-name"]') && document.querySelector('meta[name="csrf-token-name"]').getAttribute('content'));
        var csrfVal = (typeof window.CI_CSRF_TOKEN !== 'undefined' ? window.CI_CSRF_TOKEN : null) || (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        var body = 'data=' + encodeURIComponent(JSON.stringify(datos));
        if (csrfName && csrfVal) body += '&' + encodeURIComponent(csrfName) + '=' + encodeURIComponent(csrfVal);
        var headers = { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' };
        if (csrfVal) headers['X-CSRF-TOKEN'] = csrfVal;
        fetch('<?= site_url('registers/saveregvalues') ?>', {
            method: 'POST',
            headers: headers,
            body: body
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res && res.success) {
                var rid = document.getElementById('registro_id').value;
                window.location.href = '<?= site_url('registers/viewreport') ?>/' + rid;
            } else {
                alert(res && res.message ? res.message : 'Error al guardar');
            }
        })
        .catch(function() { alert('Error al guardar'); });
    });
    actualizarCalculadas();
});
</script>

<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_registers'), 'url' => site_url('registers')],
    ['label' => ($register_info->first_name ?? '') . ' ' . ($register_info->last_name_fa ?? ''), 'url' => site_url('registers/view/' . ($register_info->registro_id ?? ''))],
]]) ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<?php
$estadosMuestra = [0 => 'Tomada', 1 => 'Recibida', 2 => 'Procesada', 3 => 'Validada'];
if (!empty($muestra)): ?>
<div class="card mb-3 border-info">
    <div class="card-header bg-info text-white py-2"><i class="fa-solid fa-vial-circle-check me-2"></i> Muestra</div>
    <div class="card-body py-2">
        <div class="row align-items-center">
            <div class="col-md-4"><strong>Código:</strong> <?= esc($muestra['codigo_barras'] ?? '') ?></div>
            <div class="col-md-3"><strong>Estado:</strong> <?= esc($estadosMuestra[(int)($muestra['estado'] ?? 0)] ?? '-') ?></div>
            <div class="col-md-5">
                <?php if ((int)($muestra['estado'] ?? 0) < 3): ?>
                <a href="<?= site_url('registers/cambiarestadomuestra/' . (int)($muestra['muestra_id'] ?? 0) . '/' . ((int)($muestra['estado'] ?? 0) + 1)) ?>" class="btn btn-sm btn-primary">→ <?= esc($estadosMuestra[(int)($muestra['estado'] ?? 0) + 1] ?? 'Siguiente') ?></a>
                <?php endif; ?>
                <a href="<?= base_url('qr/generate?data=' . urlencode($muestra['codigo_barras'] ?? '') . '&size=150') ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Ver QR</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<fieldset id="customer_basic_info">
<input type="hidden" name="registro_id" id="registro_id" value="<?= (int)($labotests_namecate ?? 0) ?>">
<?php
$registerModel = $registerModel ?? null;
$last_padre = '';
if (empty($pruebas_info)):
?>
<div class="alert alert-warning">No hay pruebas para completar en este registro. Verifique que se hayan seleccionado pruebas al crear la orden. <a href="<?= site_url('registers') ?>">Volver a registros</a></div>
<?php
else:
foreach ($pruebas_info ?? [] as $prueba):
    if (($prueba['padre'] ?? '') != $last_padre):
        if ($last_padre !== '') echo '</div>';
        echo '<div class="row mb-3"><div class="col-12"><strong class="text-uppercase">' . esc($prueba['padre'] ?? '') . '</strong></div>';
        $last_padre = $prueba['padre'] ?? '';
    endif;

    if (($prueba['compleja'] ?? 0) == 0):
        if (($prueba['opcion_id'] ?? '') != 3 && ($prueba['opcion_id'] ?? '') != ''):
            $pMin = trim($prueba['valor_min'] ?? ''); $pMax = trim($prueba['valor_max'] ?? ''); $pUmed = trim($prueba['umedida'] ?? '');
            $pRef = ($pMin !== '' || $pMax !== '') ? ' <small class="text-muted">(Ref: ' . ($pMin ?: '…') . ' - ' . ($pMax ?: '…') . ($pUmed ? ' ' . $pUmed : '') . ')</small>' : '';
            $valores = $registerModel ? $registerModel->getOpciones((int)$prueba['opcion_id']) : [];
            echo '<div class="col-md-6 mb-3"><div class="mb-3">';
            $nocId = 'noc_' . ($prueba['priresultados_id'] ?? '');
            echo '<label for="' . esc($nocId) . '" class="form-label">' . esc($prueba['hijo'] ?? '') . $pRef . ':</label>';
            $extra = 'id="' . esc($nocId) . '" class="form-control input-con-ref"';
            if ($pMin !== '') $extra .= ' data-min="' . esc($pMin) . '"'; if ($pMax !== '') $extra .= ' data-max="' . esc($pMax) . '"';
            echo build_select($nocId, $valores, '', $extra);
            echo '<span class="invalid-feedback d-block" data-msg-for="' . esc($nocId) . '"></span></div></div>';
        elseif (($prueba['opcion_id'] ?? '') == 3):
            $rid = $prueba['priresultados_id'] ?? $prueba['prianacategoria_id'] ?? '';
            $pMin = trim($prueba['valor_min'] ?? ''); $pMax = trim($prueba['valor_max'] ?? ''); $pUmed = trim($prueba['umedida'] ?? '');
            $pRef = ($pMin !== '' || $pMax !== '') ? ' <small class="text-muted">(Ref: ' . ($pMin ?: '…') . ' - ' . ($pMax ?: '…') . ($pUmed ? ' ' . $pUmed : '') . ')</small>' : '';
            echo '<div class="col-md-6 mb-3"><div class="mb-3">';
            echo '<label for="noc_' . esc($rid) . '" class="form-label">' . esc($prueba['hijo'] ?? '') . $pRef . ':</label>';
            $attrs = 'name="noc_' . esc($rid) . '" id="noc_' . esc($rid) . '" class="form-control input-con-ref" value=""';
            if ($pMin !== '') $attrs .= ' data-min="' . esc($pMin) . '"'; if ($pMax !== '') $attrs .= ' data-max="' . esc($pMax) . '"';
            echo '<input type="text" ' . $attrs . '><span class="invalid-feedback d-block" data-msg-for="noc_' . esc($rid) . '"></span>';
            echo '</div></div>';
        else:
            $rid = $prueba['priresultados_id'] ?? $prueba['prianacategoria_id'] ?? '';
            $pMin = trim($prueba['valor_min'] ?? ''); $pMax = trim($prueba['valor_max'] ?? ''); $pUmed = trim($prueba['umedida'] ?? '');
            $pRef = ($pMin !== '' || $pMax !== '') ? ' <small class="text-muted">(Ref: ' . ($pMin ?: '…') . ' - ' . ($pMax ?: '…') . ($pUmed ? ' ' . $pUmed : '') . ')</small>' : ' <small class="text-muted">(Por favor revise los valores de referencia en Análisis clínico)</small>';
            echo '<div class="col-md-6 mb-3"><div class="mb-3">';
            echo '<label for="noc_' . esc($rid) . '" class="form-label">' . esc($prueba['hijo'] ?? '') . $pRef . ':</label>';
            $attrs = 'name="noc_' . esc($rid) . '" id="noc_' . esc($rid) . '" class="form-control input-con-ref" value=""';
            if ($pMin !== '') $attrs .= ' data-min="' . esc($pMin) . '"'; if ($pMax !== '') $attrs .= ' data-max="' . esc($pMax) . '"';
            echo '<input type="text" ' . $attrs . '><span class="invalid-feedback d-block" data-msg-for="noc_' . esc($rid) . '"></span>';
            echo '</div></div>';
        endif;
    else:
        $prianacategoriaId = (int)($prueba['prianacategoria_id'] ?? 0);
        $valores = $registerModel && isset($register_info->paciente)
            ? $registerModel->getValoresComplejaSiempre($prianacategoriaId, (int)$register_info->paciente, isset($register_info->gender) ? (int)$register_info->gender : null)
            : [];
        $nombreToCid = [];
        foreach ($valores as $vv) {
            $nom = trim($vv['nombre'] ?? '');
            if ($nom !== '') $nombreToCid[$nom] = 'c_' . ($vv['secanacategoria_id'] ?? '');
        }
        $cidToNombre = array_flip($nombreToCid);
        foreach ($valores as $v):
            $vMin = trim($v['valor_min'] ?? '');
            $vMax = trim($v['valor_max'] ?? '');
            $umedida = trim($v['umedida'] ?? '');
            $refText = ($vMin !== '' || $vMax !== '') ? ' <small class="text-muted">(Ref: ' . ($vMin !== '' ? $vMin : '…') . ' - ' . ($vMax !== '' ? $vMax : '…') . ($umedida !== '' ? ' ' . $umedida : '') . ')</small>' : '';
            if (($v['opcion_id'] ?? 0) != 3):
                $opts = $registerModel ? $registerModel->getOpciones((int)($v['opcion_id'] ?? 0)) : [];
                $cId = 'c_' . ($v['secanacategoria_id'] ?? '');
                echo '<div class="col-md-6 mb-3"><div class="mb-3">';
                echo '<label for="' . esc($cId) . '" class="form-label">' . esc($v['nombre'] ?? '') . $refText . ':</label>';
                $nombrePrueba = trim($v['nombre'] ?? '');
                $extra = 'id="' . esc($cId) . '" class="form-control input-con-ref"';
                if ($prianacategoriaId > 0) $extra .= ' data-prianacategoria-id="' . $prianacategoriaId . '"';
                if ($nombrePrueba !== '') $extra .= ' data-prueba="' . esc($nombrePrueba) . '"';
                if ($vMin !== '') $extra .= ' data-min="' . esc($vMin) . '"';
                if ($vMax !== '') $extra .= ' data-max="' . esc($vMax) . '"';
                echo build_select($cId, $opts, '', $extra);
                echo '<span class="invalid-feedback d-block" data-msg-for="' . esc($cId) . '"></span></div></div>';
            else:
                $cId = 'c_' . ($v['secanacategoria_id'] ?? '');
                $expresion = trim($v['formula_expresion'] ?? '');
                if ($expresion !== '' && !empty($nombreToCid)) {
                    uksort($nombreToCid, function ($a, $b) { return strlen($b) - strlen($a); });
                    foreach ($nombreToCid as $nom => $cid) {
                        $expresion = str_replace('[' . $nom . ']', $cid, $expresion);
                    }
                }
                $formulaConNombres = '';
                if ($expresion !== '' && !empty($cidToNombre)) {
                    $formulaConNombres = preg_replace('/\b1\b/', '[valor]', $expresion);
                    $cids = array_keys($cidToNombre);
                    usort($cids, function ($a, $b) { return strlen($b) - strlen($a); });
                    foreach ($cids as $cid) {
                        $formulaConNombres = str_replace($cid, '[' . $cidToNombre[$cid] . ']', $formulaConNombres);
                    }
                } else {
                    $formulaConNombres = $expresion;
                }
                $formulasId = (int)($v['formulas_id'] ?? 1);
                $esCalculada = $expresion !== '' && $formulasId !== 1;
                $idsEnFormula = $esCalculada && preg_match_all('/c_\d+/', $expresion, $m) ? array_unique($m[0]) : [];
                $esFormulaValor = $esCalculada && count($idsEnFormula) === 1 && in_array($cId, $idsEnFormula, true);
                $nombrePrueba = trim($v['nombre'] ?? '');
                echo '<div class="col-md-6 mb-3"><div class="mb-3">';
                echo '<label for="' . esc($cId) . '" class="form-label">' . esc($v['nombre'] ?? '') . $refText . ($esCalculada ? ' <span class="badge badge-calculada">' . ($esFormulaValor ? 'Fórmula (valor × expresión)' : 'Calculada') . '</span>' : '') . ':</label>';
                if ($esCalculada) {
                    if ($esFormulaValor) {
                        $attrs = 'name="' . esc($cId) . '" id="' . esc($cId) . '" class="form-control formula-calculada input-con-ref" value="" data-formula="' . esc($formulaConNombres) . '" placeholder="Escriba el valor (ej. 50)"';
                        if ($formulasId > 1) $attrs .= ' data-formula-id="' . (int)$formulasId . '"';
                        if ($prianacategoriaId > 0) $attrs .= ' data-prianacategoria-id="' . $prianacategoriaId . '"';
                        if ($nombrePrueba !== '') $attrs .= ' data-prueba="' . esc($nombrePrueba) . '"';
                        if ($vMin !== '') $attrs .= ' data-min="' . esc($vMin) . '"'; if ($vMax !== '') $attrs .= ' data-max="' . esc($vMax) . '"';
                        if ($umedida !== '') $attrs .= ' data-umedida="' . esc($umedida) . '"';
                        echo '<input type="text" ' . $attrs . '>';
                        echo '<span class="sugerencia-calculada small text-muted mt-1 d-block" data-sugerencia-for="' . esc($cId) . '" role="button" tabindex="0" title="Clic para usar este valor">Sugerencia: —</span>';
                        echo '<span class="invalid-feedback d-block" data-msg-for="' . esc($cId) . '"></span>';
                    } else {
                        $attrs = 'id="' . esc($cId) . '" class="form-control formula-calculada input-con-ref" value="" data-formula="' . esc($formulaConNombres) . '" placeholder="Escriba o use la sugerencia"';
                        if ($formulasId > 1) $attrs .= ' data-formula-id="' . (int)$formulasId . '"';
                        if ($prianacategoriaId > 0) $attrs .= ' data-prianacategoria-id="' . $prianacategoriaId . '"';
                        if ($nombrePrueba !== '') $attrs .= ' data-prueba="' . esc($nombrePrueba) . '"';
                        if ($vMin !== '') $attrs .= ' data-min="' . esc($vMin) . '"'; if ($vMax !== '') $attrs .= ' data-max="' . esc($vMax) . '"';
                        if ($umedida !== '') $attrs .= ' data-umedida="' . esc($umedida) . '"';
                        echo '<input type="text" ' . $attrs . '>';
                        echo '<span class="sugerencia-calculada small text-muted mt-1 d-block" data-sugerencia-for="' . esc($cId) . '" role="button" tabindex="0" title="Clic para usar este valor">Sugerencia: —</span>';
                        echo '<span class="invalid-feedback d-block" data-msg-for="' . esc($cId) . '"></span>';
                    }
                } else {
                    $attrs = 'name="' . esc($cId) . '" id="' . esc($cId) . '" class="form-control input-con-ref" value=""';
                    if ($prianacategoriaId > 0) $attrs .= ' data-prianacategoria-id="' . $prianacategoriaId . '"';
                    if ($nombrePrueba !== '') $attrs .= ' data-prueba="' . esc($nombrePrueba) . '"';
                    if ($vMin !== '') $attrs .= ' data-min="' . esc($vMin) . '"'; if ($vMax !== '') $attrs .= ' data-max="' . esc($vMax) . '"';
                    if ($umedida !== '') $attrs .= ' data-umedida="' . esc($umedida) . '"';
                    echo '<input type="text" ' . $attrs . '><span class="invalid-feedback d-block" data-msg-for="' . esc($cId) . '"></span>';
                }
                echo '</div></div>';
            endif;
        endforeach;
    endif;
endforeach;
if ($last_padre !== '') echo '</div>';
endif;
?>
<?php if (!empty($pruebas_info)): ?>
<div class="mt-3">
    <button type="button" id="submit" name="btn_submit" class="btn btn-primary"><?= lang('Common.common_submit') ?></button>
</div>
<?php endif; ?>
</fieldset>

<?= view('partial/footer') ?>
