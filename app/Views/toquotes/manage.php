<?= view('partial/header', ['allowed_modules' => $allowed_modules ?? [], 'user_info' => $user_info ?? null, 'current_module' => 'toquotes']) ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var cotizarBtn = document.getElementById('cotizarBtn');
    var searchBtn = document.getElementById('searchBtn');
    var searchInput = document.getElementById('searchInput');

    function actualizarResumen() {
        var totalCost = 0, totalRefe = 0, totalSeleccionados = 0, items = [];
        document.querySelectorAll('.contador:checked').forEach(function(cb) {
            totalSeleccionados++;
            totalCost += parseFloat(cb.getAttribute('cost') || 0);
            totalRefe += parseFloat(cb.getAttribute('refe') || 0);
            items.push(cb.closest('li').textContent.trim());
        });
        var el = document.getElementById('valorcotizado');
        el.innerHTML = totalSeleccionados > 0
            ? '✅ <strong>Seleccionados:</strong> ' + totalSeleccionados + ' | 💰 <strong>Costo Total:</strong> ' + totalCost + ' Bs | 📦 <strong>Ref:</strong> ' + totalRefe + ' Bs'
            : '';
    }

    document.querySelectorAll('.contador').forEach(function(cb) {
        cb.addEventListener('change', actualizarResumen);
    });

    cotizarBtn.addEventListener('click', function() {
        var totalCost = 0, totalRefe = 0, items = [];
        document.querySelectorAll('.contador:checked').forEach(function(cb) {
            totalCost += parseFloat(cb.getAttribute('cost') || 0);
            totalRefe += parseFloat(cb.getAttribute('refe') || 0);
            items.push(cb.closest('li').textContent.trim());
        });
        if (items.length === 0) {
            alert('<?= lang('Toquotes.toquotes_select_items') ?>');
            return;
        }
        var data = new URLSearchParams();
        data.append('cotizo', items.join(','));
        data.append('costo', totalCost);
        fetch('<?= site_url('toquotes/savetoquotelog') ?>', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: data.toString()
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) alert('<?= lang('Toquotes.toquotes_saved') ?>');
            actualizarResumen();
        })
        .catch(function() { alert('<?= lang('Toquotes.toquotes_error') ?>'); });
    });

    searchBtn.addEventListener('click', function() {
        var q = searchInput.value.trim().toLowerCase();
        if (!q) { alert('<?= lang('Toquotes.toquotes_search_hint') ?>'); return; }
        var items = document.querySelectorAll('.list-group-item');
        var found = null;
        items.forEach(function(item) {
            if (item.textContent.toLowerCase().includes(q)) {
                if (!found) found = item;
                item.classList.add('highlight-red');
            } else {
                item.classList.remove('highlight-red');
            }
        });
        if (found) found.scrollIntoView({ behavior: 'smooth', block: 'center' });
        else alert('<?= lang('Toquotes.toquotes_not_found') ?>');
    });
});
</script>
<style>.highlight-red{background-color:#dc3545!important;color:#fff!important;}</style>

<?= view('partial/breadcrumb_nav', [
    'items' => [['label' => lang('Module.module_toquotes'), 'url' => site_url('toquotes')]],
    'right' => '<input type="text" id="searchInput" name="search" class="form-control form-control-sm" placeholder="' . lang('Toquotes.toquotes_search') . '" style="width:180px">' .
        '<button id="searchBtn" class="btn btn-primary btn-sm">' . lang('Toquotes.toquotes_search_btn') . '</button>' .
        '<button id="cotizarBtn" class="btn btn-success btn-sm">' . lang('Toquotes.toquotes_quote_btn') . '</button>',
]) ?>
<div class="mb-3" id="valorcotizado"></div>

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
                    <input type="checkbox" id="cotizo_<?= (int)($item['id'] ?? 0) ?>" name="cotizo_<?= (int)($item['id'] ?? 0) ?>" class="form-check-input contador me-2" cost="<?= (int)$item['cost'] ?>" refe="<?= (int)$item['refe'] ?>">
                    <span class="flex-grow-1"><?= esc($item['name']) ?></span>
                    <span class="badge bg-secondary"><?= (int)$item['cost'] ?> Bs</span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (empty($categories)): ?>
<div class="alert alert-info"><?= lang('Toquotes.toquotes_no_data') ?></div>
<?php endif; ?>

<?= view('partial/footer') ?>
