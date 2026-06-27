<?php
/**
 * Modal para ordenar filas de referencia por criterios combinables.
 *
 * @var string $modal_id
 * @var string $btn_open_id
 * @var string $btn_apply_id
 * @var bool   $include_nombre
 * @var string $sort_url
 * @var int    $prianacategoria_id
 */
$modal_id = $modal_id ?? 'modalOrdenCriterios';
$btn_open_id = $btn_open_id ?? 'btn_abrir_modal_orden_criterios';
$btn_apply_id = $btn_apply_id ?? 'btn_aplicar_orden_criterios';
$include_nombre = ! empty($include_nombre);
$sort_url = $sort_url ?? '';
$prianacategoria_id = (int) ($prianacategoria_id ?? 0);

$opciones = ['' => '— No usar —'];
if ($include_nombre) {
    $opciones['nombre'] = 'Sub-clase (nombre)';
}
$opciones['poblacion'] = 'Población';
$opciones['sexo'] = 'Sexo';

$default1 = $include_nombre ? 'nombre' : 'poblacion';
$default2 = $include_nombre ? 'poblacion' : 'sexo';
$default3 = $include_nombre ? 'sexo' : '';
?>
<button type="button" class="btn btn-sm btn-outline-primary" id="<?= esc($btn_open_id, 'attr') ?>" title="Ordenar filas por criterios combinables">
    <i class="fa-solid fa-arrow-down-a-z me-1"></i>Ordenar
</button>

<div class="modal fade" id="<?= esc($modal_id, 'attr') ?>" tabindex="-1" aria-labelledby="<?= esc($modal_id, 'attr') ?>Title" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="<?= esc($modal_id, 'attr') ?>Title">Ordenar filas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    Elija uno o más criterios en el orden deseado. Por ejemplo: primero <strong>Sub-clase</strong>,
                    luego <strong>Población</strong> y después <strong>Sexo</strong> agrupa cada analito y ordena sus variantes.
                </p>
                <?php foreach ([1 => $default1, 2 => $default2, 3 => $default3] as $n => $def): ?>
                <div class="mb-2">
                    <label class="form-label mb-1" for="<?= esc($modal_id, 'attr') ?>_criterio_<?= $n ?>">
                        <?= $n === 1 ? 'Ordenar primero por' : ($n === 2 ? 'Luego por' : 'Después por') ?>
                        <?= $n === 1 ? ' <span class="text-danger">*</span>' : '' ?>
                    </label>
                    <select class="form-select form-select-sm criterio-orden-select" id="<?= esc($modal_id, 'attr') ?>_criterio_<?= $n ?>" data-nivel="<?= $n ?>">
                        <?php foreach ($opciones as $val => $lbl): ?>
                        <option value="<?= esc($val) ?>" <?= ($def !== '' && $val === $def) ? 'selected' : '' ?>><?= esc($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="<?= esc($btn_apply_id, 'attr') ?>">Aplicar orden</button>
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    var modalEl = document.getElementById(<?= json_encode($modal_id) ?>);
    var btnOpen = document.getElementById(<?= json_encode($btn_open_id) ?>);
    var btnApply = document.getElementById(<?= json_encode($btn_apply_id) ?>);
    if (!modalEl || !btnOpen || !btnApply) return;

    var modal = (typeof bootstrap !== 'undefined')
        ? (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl))
        : null;
    var prianacategoriaId = <?= (int) $prianacategoria_id ?>;
    var sortUrl = <?= json_encode($sort_url) ?>;

    function getCsrfData() {
        var csrf = document.querySelector('input[name="csrf_test_name"]') || document.querySelector('input[name*="csrf"]');
        var csrfName = (csrf && csrf.name) ? csrf.name : (typeof window.CI_CSRF_TOKEN_NAME !== 'undefined' ? window.CI_CSRF_TOKEN_NAME : 'csrf_test_name');
        var csrfVal = (csrf && csrf.value) ? csrf.value : (typeof window.CI_CSRF_TOKEN !== 'undefined' ? window.CI_CSRF_TOKEN : '');
        return { name: csrfName, value: csrfVal };
    }
    function applyCsrfFromJson(d) {
        if (!d || !d.csrf_token || !d.csrf_name) return;
        window.CI_CSRF_TOKEN = d.csrf_token;
        window.CI_CSRF_TOKEN_NAME = d.csrf_name;
        document.querySelectorAll('input[name="csrf_test_name"], input[name*="csrf"]').forEach(function(inp) {
            inp.name = d.csrf_name;
            inp.value = d.csrf_token;
        });
    }
    function collectCriteria() {
        var out = [];
        var seen = {};
        modalEl.querySelectorAll('.criterio-orden-select').forEach(function(sel) {
            var v = String(sel.value || '').trim();
            if (v === '' || seen[v]) return;
            seen[v] = true;
            out.push(v);
        });
        return out;
    }

    btnOpen.addEventListener('click', function() {
        if (modal) modal.show();
    });

    btnApply.addEventListener('click', function() {
        var criteria = collectCriteria();
        if (criteria.length < 1) {
            if (typeof showToast === 'function') {
                showToast('Seleccione al menos un criterio de orden', 'error');
            }
            return;
        }
        var fd = new FormData();
        fd.append('prianacategoria_id', String(prianacategoriaId));
        criteria.forEach(function(c) { fd.append('sort_criteria[]', c); });
        var csrfData = getCsrfData();
        if (csrfData.value) fd.append(csrfData.name, csrfData.value);
        var headers = { 'X-Requested-With': 'XMLHttpRequest' };
        if (csrfData.value) headers['X-CSRF-TOKEN'] = csrfData.value;
        btnApply.disabled = true;
        fetch(sortUrl, {
            method: 'POST',
            body: fd,
            headers: headers
        }).then(function(r) { return r.json(); }).then(function(d) {
            btnApply.disabled = false;
            applyCsrfFromJson(d);
            if (d.success) {
                if (typeof showToast === 'function') showToast(d.message || 'Orden aplicado', 'success');
                window.location.reload();
            } else if (typeof showToast === 'function') {
                showToast(d.message || 'No se pudo aplicar el orden', 'error');
            }
        }).catch(function() {
            btnApply.disabled = false;
            if (typeof showToast === 'function') showToast('No se pudo aplicar el orden', 'error');
        });
    });
})();
</script>
