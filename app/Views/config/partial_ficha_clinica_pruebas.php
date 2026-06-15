<?php
/**
 * Enlace de ficha clínica con pruebas de análisis (prianacategoria).
 *
 * @var int $ficha_clinica_id
 * @var array<int, array<string, mixed>> $pruebas_catalog
 * @var array<int, array<string, mixed>> $pruebas_linked
 */
$fichaId = (int) ($ficha_clinica_id ?? 0);
$catalog = $pruebas_catalog ?? [];
$linked = $pruebas_linked ?? [];
$linkedIds = [];
foreach ($linked as $row) {
    $pid = (int) ($row['prianacategoria_id'] ?? 0);
    if ($pid > 0) {
        $linkedIds[$pid] = true;
    }
}
$tipoLabels = [
    0 => 'Simple',
    1 => 'Compuesta',
    2 => 'Cultivo',
    3 => 'Personalizado',
];
?>
<div class="card shadow-sm mt-3" id="ficha_clinica_pruebas_card">
    <div class="card-header d-flex flex-wrap align-items-center gap-2">
        <strong><i class="fa-solid fa-link me-1"></i>Pruebas de análisis enlazadas</strong>
        <span class="badge bg-info text-dark" id="fc_pruebas_count_badge"><?= count($linkedIds) ?></span>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            Seleccione una o más pruebas del catálogo de análisis clínicos para referenciarlas en esta ficha.
            Más adelante podrá usar estos enlaces al capturar o reportar resultados.
        </p>

        <?php if ($linked !== []): ?>
        <div class="mb-3">
            <div class="small fw-semibold mb-1">Enlazadas actualmente</div>
            <div class="d-flex flex-wrap gap-1">
                <?php foreach ($linked as $lp):
                    $lpId = (int) ($lp['prianacategoria_id'] ?? 0);
                    if ($lpId < 1) continue;
                    $lpNombre = (string) ($lp['prueba_nombre'] ?? ('Prueba #' . $lpId));
                    $lpCat = trim((string) ($lp['categoria_nombre'] ?? ''));
                ?>
                <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle">
                    <?= esc($lpNombre) ?><?= $lpCat !== '' ? ' <span class="opacity-75">(' . esc($lpCat) . ')</span>' : '' ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?= form_open('config/savefichaclinicapruebas', ['id' => 'form_ficha_clinica_pruebas']) ?>
        <input type="hidden" name="ficha_clinica_id" value="<?= $fichaId ?>">
        <div class="mb-3">
            <label class="form-label" for="fc_pruebas_search">Buscar prueba</label>
            <input type="search" class="form-control form-control-sm" id="fc_pruebas_search"
                   placeholder="Filtrar por categoría o nombre de prueba" autocomplete="off">
        </div>

        <div class="border rounded p-2 bg-light-subtle" style="max-height: 420px; overflow-y: auto;" id="fc_pruebas_picker">
            <?php if ($catalog === []): ?>
            <p class="text-muted small mb-0 px-2 py-3 text-center">No hay pruebas de análisis disponibles en el catálogo.</p>
            <?php else: ?>
            <?php foreach ($catalog as $catIdx => $cat):
                $catName = (string) ($cat['name'] ?? '');
                $items = is_array($cat['items'] ?? null) ? $cat['items'] : [];
                if ($items === []) continue;
                $catKey = 'fc_cat_' . (int) ($cat['id'] ?? $catIdx);
            ?>
            <div class="fc-prueba-categoria-block mb-2" data-categoria="<?= esc(mb_strtolower($catName), 'attr') ?>">
                <div class="form-check border-bottom pb-1 mb-1">
                    <input class="form-check-input fc-prueba-categoria-check" type="checkbox"
                           id="<?= esc($catKey, 'attr') ?>"
                           data-cat-block="<?= esc($catKey, 'attr') ?>">
                    <label class="form-check-label fw-semibold" for="<?= esc($catKey, 'attr') ?>">
                        <?= esc($catName) ?>
                        <span class="text-muted fw-normal">(<?= count($items) ?>)</span>
                    </label>
                </div>
                <div class="ps-3">
                    <?php foreach ($items as $item):
                        $itemId = (int) ($item['id'] ?? 0);
                        if ($itemId < 1) continue;
                        $itemName = (string) ($item['name'] ?? '');
                        $compleja = (int) ($item['compleja'] ?? 0);
                        $tipoLabel = $tipoLabels[$compleja] ?? 'Prueba';
                        $checkId = 'fc_prueba_' . $itemId;
                        $isChecked = isset($linkedIds[$itemId]);
                    ?>
                    <div class="form-check fc-prueba-item-row py-1"
                         data-nombre="<?= esc(mb_strtolower($itemName), 'attr') ?>"
                         data-categoria="<?= esc(mb_strtolower($catName), 'attr') ?>">
                        <input class="form-check-input fc-prueba-item-check" type="checkbox"
                               name="prianacategoria_ids[]"
                               value="<?= $itemId ?>"
                               id="<?= esc($checkId, 'attr') ?>"
                               data-cat-block="<?= esc($catKey, 'attr') ?>"
                               <?= $isChecked ? 'checked' : '' ?>>
                        <label class="form-check-label d-flex flex-wrap align-items-center gap-2" for="<?= esc($checkId, 'attr') ?>">
                            <span><?= esc($itemName) ?></span>
                            <span class="badge bg-secondary"><?= esc($tipoLabel) ?></span>
                            <a href="<?= site_url('labotests/detail/' . $itemId) ?>" class="small" target="_blank" rel="noopener"
                               onclick="event.stopPropagation();">Ver prueba</a>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk me-1"></i>Guardar pruebas enlazadas
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="fc_pruebas_clear_all">
                Quitar todas
            </button>
        </div>
        <?= form_close() ?>
    </div>
</div>
<script>
(function() {
    var picker = document.getElementById('fc_pruebas_picker');
    var search = document.getElementById('fc_pruebas_search');
    var badge = document.getElementById('fc_pruebas_count_badge');
    var btnClear = document.getElementById('fc_pruebas_clear_all');
    if (!picker) return;

    function updateBadge() {
        if (!badge) return;
        var n = picker.querySelectorAll('.fc-prueba-item-check:checked').length;
        badge.textContent = String(n);
    }

    function updateCategoryState(catBlockId) {
        var block = picker.querySelector('.fc-prueba-categoria-block .fc-prueba-categoria-check[data-cat-block="' + catBlockId + '"]');
        if (!block) return;
        var parent = block.closest('.fc-prueba-categoria-block');
        if (!parent) return;
        var items = parent.querySelectorAll('.fc-prueba-item-check');
        var checked = 0;
        items.forEach(function(chk) { if (chk.checked) checked++; });
        block.checked = items.length > 0 && checked === items.length;
        block.indeterminate = checked > 0 && checked < items.length;
    }

    function filterPruebas(q) {
        var term = String(q || '').toLowerCase().trim();
        picker.querySelectorAll('.fc-prueba-categoria-block').forEach(function(catBlock) {
            var catName = catBlock.getAttribute('data-categoria') || '';
            var catVisible = false;
            catBlock.querySelectorAll('.fc-prueba-item-row').forEach(function(row) {
                var nombre = row.getAttribute('data-nombre') || '';
                var categoria = row.getAttribute('data-categoria') || '';
                var show = term === ''
                    || nombre.indexOf(term) !== -1
                    || categoria.indexOf(term) !== -1
                    || catName.indexOf(term) !== -1;
                row.style.display = show ? '' : 'none';
                if (show) catVisible = true;
            });
            catBlock.style.display = catVisible ? '' : 'none';
        });
    }

    picker.addEventListener('change', function(e) {
        if (e.target.classList.contains('fc-prueba-categoria-check')) {
            var catId = e.target.getAttribute('data-cat-block');
            var parent = e.target.closest('.fc-prueba-categoria-block');
            if (parent && catId) {
                parent.querySelectorAll('.fc-prueba-item-check[data-cat-block="' + catId + '"]').forEach(function(chk) {
                    if (chk.closest('.fc-prueba-item-row').style.display !== 'none') {
                        chk.checked = e.target.checked;
                    }
                });
            }
            updateBadge();
            return;
        }
        if (e.target.classList.contains('fc-prueba-item-check')) {
            var blockId = e.target.getAttribute('data-cat-block');
            if (blockId) updateCategoryState(blockId);
            updateBadge();
        }
    });

    if (search) {
        search.addEventListener('input', function() {
            filterPruebas(this.value);
        });
    }

    if (btnClear) {
        btnClear.addEventListener('click', function() {
            picker.querySelectorAll('.fc-prueba-item-check').forEach(function(chk) {
                chk.checked = false;
            });
            picker.querySelectorAll('.fc-prueba-categoria-check').forEach(function(chk) {
                chk.checked = false;
                chk.indeterminate = false;
            });
            updateBadge();
        });
    }

    picker.querySelectorAll('.fc-prueba-categoria-block').forEach(function(block) {
        var catChk = block.querySelector('.fc-prueba-categoria-check');
        if (catChk) updateCategoryState(catChk.getAttribute('data-cat-block'));
    });
    updateBadge();
})();
</script>
