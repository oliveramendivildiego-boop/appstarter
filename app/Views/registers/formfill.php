<?= view('partial/header', ['allowed_modules' => $allowed_modules ?? [], 'user_info' => $user_info ?? null, 'current_module' => 'registers']) ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    function evaluarFormula(expr) {
        if (!expr || typeof expr !== 'string') return '';
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
            if (f) el.value = evaluarFormula(f);
        });
    }
    document.querySelectorAll('.form-control:not(.formula-calculada)').forEach(function(el) {
        el.addEventListener('input', actualizarCalculadas);
        el.addEventListener('change', actualizarCalculadas);
    });
    var submitBtn = document.getElementById('submit');
    if (!submitBtn) return;
    submitBtn.addEventListener('click', function() {
        document.querySelectorAll('.formula-calculada').forEach(function(el) {
            var f = el.getAttribute('data-formula');
            if (f) el.value = evaluarFormula(f);
        });
        var datos = [];
        document.querySelectorAll('.form-control').forEach(function(el) {
            var id = el.id;
            var valor = el.value;
            var registroId = document.getElementById('registro_id').value;
            if (id && registroId) {
                datos.push({ id: id, valor: valor, registro_id: registroId });
            }
        });
        fetch('<?= site_url('registers/saveregvalues') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: 'data=' + encodeURIComponent(JSON.stringify(datos))
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            var rid = document.getElementById('registro_id').value;
            window.location.href = '<?= site_url('registers/viewreport') ?>/' + rid;
        })
        .catch(function() { alert('Error al guardar'); });
    });
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
            $valores = $registerModel ? $registerModel->getOpciones((int)$prueba['opcion_id']) : [];
            echo '<div class="col-md-6 mb-3"><div class="mb-3">';
            $nocId = 'noc_' . ($prueba['priresultados_id'] ?? '');
            echo '<label for="' . esc($nocId) . '" class="form-label">' . esc($prueba['hijo'] ?? '') . ':</label>';
            echo build_select($nocId, $valores, '', 'id="' . esc($nocId) . '" class="form-control"');
            echo '</div></div>';
        elseif (($prueba['opcion_id'] ?? '') == 3):
            $rid = $prueba['priresultados_id'] ?? $prueba['prianacategoria_id'] ?? '';
            echo '<div class="col-md-6 mb-3"><div class="mb-3">';
            echo '<label for="noc_' . esc($rid) . '" class="form-label">' . esc($prueba['hijo'] ?? '') . ':</label>';
            echo '<input type="text" name="noc_' . $rid . '" id="noc_' . $rid . '" class="form-control" value="">';
            echo '</div></div>';
        else:
            echo '<div class="col-md-6 mb-3"><div class="mb-3"><label class="form-label">' . esc($prueba['hijo'] ?? '') . ':</label><br><small>Por favor revise los valores de referencia</small></div></div>';
        endif;
    else:
        $valores = $registerModel && isset($register_info->paciente)
            ? $registerModel->getValoresCompleja((int)($prueba['prianacategoria_id'] ?? 0), (int)$register_info->paciente)
            : [];
        foreach ($valores as $v):
            if (($v['opcion_id'] ?? 0) != 3):
                $opts = $registerModel ? $registerModel->getOpciones((int)($v['opcion_id'] ?? 0)) : [];
                $cId = 'c_' . ($v['secanacategoria_id'] ?? '');
                echo '<div class="col-md-6 mb-3"><div class="mb-3">';
                echo '<label for="' . esc($cId) . '" class="form-label">' . esc($v['nombre'] ?? '') . ':</label>';
                echo build_select($cId, $opts, '', 'id="' . esc($cId) . '" class="form-control"');
                echo '</div></div>';
            else:
                $cId = 'c_' . ($v['secanacategoria_id'] ?? '');
                $expresion = trim($v['formula_expresion'] ?? '');
                $esCalculada = $expresion !== '';
                echo '<div class="col-md-6 mb-3"><div class="mb-3">';
                echo '<label for="' . esc($cId) . '" class="form-label">' . esc($v['nombre'] ?? '') . ($esCalculada ? ' <span class="badge bg-info">Calculada</span>' : '') . ':</label>';
                if ($esCalculada) {
                    echo '<input type="text" id="' . esc($cId) . '" class="form-control formula-calculada" value="" readonly data-formula="' . esc($expresion) . '" title="Se calcula automáticamente">';
                } else {
                    echo '<input type="text" name="' . esc($cId) . '" id="' . esc($cId) . '" class="form-control" value="">';
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
