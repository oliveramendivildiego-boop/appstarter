<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
helper('layout');
$layoutCfg = layout_config();
$currencySym = (isset($layoutCfg['currency_symbol']) && (string)$layoutCfg['currency_symbol'] !== '') ? $layoutCfg['currency_symbol'] : '$';
$currencySide = isset($layoutCfg['currency_side']) ? (string)$layoutCfg['currency_side'] : 'left';
$currencyIsRight = strtolower(trim($currencySide)) === 'right';
?>
<?= view('partial/breadcrumb_nav', [
    'items' => [['label' => lang('Module.module_toquotes'), 'url' => site_url('toquotes')]],
]) ?>
<div id="toquotesFeedback" class="alert alert-dismissible fade" role="alert" style="display:none;">
    <span id="toquotesFeedbackMsg"></span>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show"><?= esc(session()->getFlashdata('error')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white py-2">
        <h5 class="mb-0"><i class="fa-solid fa-magnifying-glass me-2"></i>Buscar análisis</h5>
    </div>
    <div class="card-body">
        <div class="position-relative">
            <label for="analisisInput" class="form-label">Escriba para buscar y seleccionar</label>
            <input type="text" id="analisisInput" class="form-control" placeholder="Escriba para buscar análisis (ej: hemo, glicemia)..." autocomplete="off">
            <div id="autocompleteDropdown" class="list-group position-absolute w-100 shadow-sm" style="top:100%; left:0; z-index:1000; max-height:280px; overflow-y:auto; display:none;"></div>
        </div>
    </div>
</div>

<div class="card shadow-sm" id="cotizacionCard">
    <div class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fa-solid fa-file-invoice-dollar me-2"></i>Análisis seleccionados</h5>
        <div>
            <button id="guardarBtn" class="btn btn-light btn-sm me-1" disabled title="Guardar cotización">
                <i class="fa-solid fa-floppy-disk me-1"></i>Guardar
            </button>
            <div class="btn-group" role="group" aria-label="Exportar cotización a PDF">
                <button type="button" class="btn btn-warning btn-sm pdf-export-btn" data-pdf-tipo="costo" disabled title="<?= esc(lang('Toquotes.toquotes_pdf_costo_hint')) ?>">
                    <i class="fa-solid fa-file-pdf me-1"></i><?= esc(lang('Toquotes.toquotes_pdf_costo')) ?>
                </button>
                <button type="button" class="btn btn-warning btn-sm pdf-export-btn" data-pdf-tipo="refe" disabled title="<?= esc(lang('Toquotes.toquotes_pdf_refe_hint')) ?>">
                    <i class="fa-solid fa-file-pdf me-1"></i><?= esc(lang('Toquotes.toquotes_pdf_refe')) ?>
                </button>
                <button type="button" class="btn btn-warning btn-sm pdf-export-btn" data-pdf-tipo="ambos" disabled title="<?= esc(lang('Toquotes.toquotes_pdf_ambos_hint')) ?>">
                    <i class="fa-solid fa-file-pdf me-1"></i><?= esc(lang('Toquotes.toquotes_pdf_ambos')) ?>
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div id="selectedEmpty" class="text-muted text-center py-4">
            <i class="fa-solid fa-cart-plus fa-2x mb-2"></i>
            <p>Seleccione análisis arriba y agréguelos a la cotización.</p>
        </div>
        <div id="selectedList" style="display:none;">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width:5%">#</th>
                            <th>Análisis</th>
                            <th class="text-end" style="width:15%">Costo (<?= esc($currencySym) ?>)</th>
                            <th class="text-end" style="width:15%">Ref. (<?= esc($currencySym) ?>)</th>
                            <th style="width:8%"></th>
                        </tr>
                    </thead>
                    <tbody id="selectedTableBody"></tbody>
                </table>
            </div>
            <div class="row mt-3 border-top pt-3">
                <div class="col-md-6">
                    <strong>Costo total:</strong>
                    <?php if ($currencyIsRight): ?>
                        <span id="totalCost" class="fs-5 text-primary">0</span> <?= esc($currencySym) ?>
                    <?php else: ?>
                        <?= esc($currencySym) ?> <span id="totalCost" class="fs-5 text-primary">0</span>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <strong>Costo referencia:</strong>
                    <?php if ($currencyIsRight): ?>
                        <span id="totalRefe" class="fs-5 text-secondary">0</span> <?= esc($currencySym) ?>
                    <?php else: ?>
                        <?= esc($currencySym) ?> <span id="totalRefe" class="fs-5 text-secondary">0</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function() {
    var selectedItems = [];
    var currentQuoteId = null;
    var currencySym = <?= json_encode($currencySym) ?>;
    var currencyIsRight = <?= json_encode($currencyIsRight) ?>;
    var analisisInput = document.getElementById('analisisInput');
    var autocompleteDropdown = document.getElementById('autocompleteDropdown');
    var selectedEmpty = document.getElementById('selectedEmpty');
    var selectedList = document.getElementById('selectedList');
    var selectedTableBody = document.getElementById('selectedTableBody');
    var guardarBtn = document.getElementById('guardarBtn');
    var pdfExportBtns = document.querySelectorAll('.pdf-export-btn');
    var feedbackEl = document.getElementById('toquotesFeedback');
    var feedbackMsg = document.getElementById('toquotesFeedbackMsg');

    function showFeedback(msg, isError) {
        feedbackMsg.textContent = msg;
        feedbackEl.className = 'alert alert-dismissible fade show ' + (isError ? 'alert-danger' : 'alert-success');
        feedbackEl.style.display = 'block';
        feedbackEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function addItem(item) {
        if (selectedItems.some(function(x) { return x.id === item.id; })) return;
        selectedItems.push({ id: item.id, name: item.name, cost: item.cost, refe: item.refe });
        currentQuoteId = null;
        renderSelected();
    }

    function removeItem(id) {
        selectedItems = selectedItems.filter(function(x) { return x.id !== id; });
        currentQuoteId = null;
        renderSelected();
    }

    function renderSelected() {
        var tbody = selectedTableBody;
        tbody.innerHTML = '';
        var totalCost = 0, totalRefe = 0;
        selectedItems.forEach(function(it, i) {
            totalCost += it.cost;
            totalRefe += it.refe;
            var tr = document.createElement('tr');
            tr.innerHTML = '<td>' + (i + 1) + '</td><td>' + escapeHtml(it.name) + '</td><td class="text-end">' + it.cost + '</td><td class="text-end">' + it.refe + '</td><td><button type="button" class="btn btn-outline-danger btn-sm py-0 px-1 remove-item" data-id="' + it.id + '" title="Quitar"><i class="fa-solid fa-times"></i></button></td>';
            tbody.appendChild(tr);
        });
        document.getElementById('totalCost').textContent = totalCost;
        document.getElementById('totalRefe').textContent = totalRefe;

        if (selectedItems.length > 0) {
            selectedEmpty.style.display = 'none';
            selectedList.style.display = 'block';
            guardarBtn.disabled = false;
            pdfExportBtns.forEach(function(b) { b.disabled = false; });
        } else {
            selectedEmpty.style.display = 'block';
            selectedList.style.display = 'none';
            guardarBtn.disabled = true;
            pdfExportBtns.forEach(function(b) { b.disabled = true; });
        }

        tbody.querySelectorAll('.remove-item').forEach(function(btn) {
            btn.addEventListener('click', function() { removeItem(parseInt(btn.dataset.id, 10)); });
        });
    }

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function formatCurrencyAmount(amount) {
        // Asegura espacio y orden según currency_side
        return currencyIsRight ? (amount + ' ' + currencySym) : (currencySym + ' ' + amount);
    }

    var searchTimeout;
    function doSearch() {
        var q = analisisInput.value.trim();
        if (q.length < 1) {
            autocompleteDropdown.style.display = 'none';
            autocompleteDropdown.innerHTML = '';
            return;
        }
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            fetch('<?= site_url('toquotes/search') ?>?q=' + encodeURIComponent(q))
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    autocompleteDropdown.innerHTML = '';
                    if (!d.items || d.items.length === 0) {
                        autocompleteDropdown.innerHTML = '<div class="list-group-item text-muted"><?= lang('Toquotes.toquotes_not_found') ?></div>';
                    } else {
                        d.items.forEach(function(it) {
                            var li = document.createElement('div');
                            li.className = 'list-group-item list-group-item-action';
                            li.style.cursor = 'pointer';
                            li.innerHTML = '<strong>' + escapeHtml(it.name) + '</strong><br><small class="text-muted">' + escapeHtml(it.cat_name) + ' &middot; ' + formatCurrencyAmount(it.cost) + ' / Ref: ' + formatCurrencyAmount(it.refe) + '</small>';
                            li.dataset.id = it.id;
                            li.dataset.name = it.name;
                            li.dataset.cost = it.cost;
                            li.dataset.refe = it.refe;
                            li.addEventListener('click', function() {
                                addItem({ id: parseInt(li.dataset.id,10), name: li.dataset.name, cost: parseInt(li.dataset.cost,10), refe: parseInt(li.dataset.refe,10) });
                                analisisInput.value = '';
                                autocompleteDropdown.style.display = 'none';
                                autocompleteDropdown.innerHTML = '';
                            });
                            autocompleteDropdown.appendChild(li);
                        });
                    }
                    autocompleteDropdown.style.display = 'block';
                })
                .catch(function() {
                    autocompleteDropdown.innerHTML = '<div class="list-group-item text-danger">Error al buscar</div>';
                    autocompleteDropdown.style.display = 'block';
                });
        }, 200);
    }

    analisisInput.addEventListener('input', doSearch);
    analisisInput.addEventListener('focus', function() { if (autocompleteDropdown.children.length) autocompleteDropdown.style.display = 'block'; });
    analisisInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            autocompleteDropdown.style.display = 'none';
        } else if (e.key === 'Enter') {
            var first = autocompleteDropdown.querySelector('.list-group-item-action');
            if (first && !first.classList.contains('text-muted')) first.click();
        }
    });
    document.addEventListener('click', function(e) {
        if (!analisisInput.contains(e.target) && !autocompleteDropdown.contains(e.target)) {
            autocompleteDropdown.style.display = 'none';
        }
    });

    guardarBtn.addEventListener('click', function() {
        if (selectedItems.length === 0) {
            showFeedback('<?= lang('Toquotes.toquotes_select_items') ?>', true);
            return;
        }
        var cotizo = selectedItems.map(function(x) { return x.name; }).join(',');
        var costo = selectedItems.reduce(function(a, x) { return a + x.cost; }, 0);
        var refe = selectedItems.reduce(function(a, x) { return a + x.refe; }, 0);
        var itemsJson = JSON.stringify(selectedItems);

        var data = new URLSearchParams();
        data.append('cotizo', cotizo);
        data.append('costo', costo);
        data.append('refe', refe);
        data.append('items_json', itemsJson);
        if (currentQuoteId) data.append('quote_id', currentQuoteId);
        if (typeof window.CI_CSRF_TOKEN_NAME !== 'undefined' && window.CI_CSRF_TOKEN) {
            data.append(window.CI_CSRF_TOKEN_NAME, window.CI_CSRF_TOKEN);
        }

        var headers = { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' };
        if (typeof window.CI_CSRF_TOKEN !== 'undefined') {
            headers['X-CSRF-TOKEN'] = window.CI_CSRF_TOKEN;
        }

        fetch('<?= site_url('toquotes/savetoquotelog') ?>', {
            method: 'POST',
            headers: headers,
            body: data.toString()
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                if (d.quote_id) currentQuoteId = d.quote_id;
                var msg = d.message || '<?= lang('Toquotes.toquotes_saved') ?>';
                if (currentQuoteId) msg += ' (No. ' + currentQuoteId + ')';
                showFeedback(msg, false);
            } else {
                showFeedback(d.message || '<?= lang('Toquotes.toquotes_error') ?>', true);
            }
        })
        .catch(function() { showFeedback('<?= lang('Toquotes.toquotes_error') ?>', true); });
    });

    function exportPdfConTipo(pdfTipo) {
        if (selectedItems.length === 0) {
            showFeedback('<?= lang('Toquotes.toquotes_select_items') ?>', true);
            return;
        }
        var data = new URLSearchParams();
        data.append('items_json', JSON.stringify(selectedItems));
        data.append('pdf_tipo', pdfTipo);
        if (currentQuoteId) data.append('quote_id', currentQuoteId);
        if (typeof window.CI_CSRF_TOKEN_NAME !== 'undefined' && window.CI_CSRF_TOKEN) {
            data.append(window.CI_CSRF_TOKEN_NAME, window.CI_CSRF_TOKEN);
        }
        var headers = { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' };
        if (typeof window.CI_CSRF_TOKEN !== 'undefined') headers['X-CSRF-TOKEN'] = window.CI_CSRF_TOKEN;

        fetch('<?= site_url('toquotes/exportPdf') ?>', {
            method: 'POST',
            headers: headers,
            body: data.toString(),
            credentials: 'same-origin'
        })
        .then(function(r) {
            var ct = (r.headers.get('Content-Type') || '').toLowerCase();
            var quoteHeader = r.headers.get('X-Cotizacion-Id');
            if (!r.ok) {
                return r.text().then(function(t) { throw new Error('HTTP ' + r.status); });
            }
            if (ct.indexOf('application/pdf') === -1) {
                return r.text().then(function() { throw new Error('Respuesta no es PDF'); });
            }
            return r.blob().then(function(blob) {
                return { blob: blob, quoteId: quoteHeader ? parseInt(quoteHeader, 10) : null };
            });
        })
        .then(function(result) {
            if (result.quoteId) currentQuoteId = result.quoteId;
            var url = URL.createObjectURL(result.blob);
            var a = document.createElement('a');
            a.href = url;
            var idPart = currentQuoteId ? ('_' + currentQuoteId) : '';
            a.download = 'cotizacion' + idPart + '_' + pdfTipo + '_' + new Date().toISOString().slice(0,10) + '.pdf';
            a.click();
            URL.revokeObjectURL(url);
            var okMsg = '<?= lang('Toquotes.toquotes_pdf_ok') ?>';
            if (currentQuoteId) okMsg += ' No. ' + currentQuoteId + '.';
            showFeedback(okMsg, false);
        })
        .catch(function(err) {
            var msg = '<?= lang('Toquotes.toquotes_pdf_error') ?>';
            if (err && err.message) {
                if (err.message.indexOf('HTTP 403') !== -1) msg = '<?= lang('Toquotes.toquotes_pdf_err_403') ?>';
                else if (err.message.indexOf('HTTP 500') !== -1) msg = '<?= lang('Toquotes.toquotes_pdf_err_500') ?>';
                else if (err.message.indexOf('Respuesta no es PDF') !== -1) msg = '<?= lang('Toquotes.toquotes_pdf_err_notpdf') ?>';
            }
            showFeedback(msg, true);
        });
    }

    pdfExportBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            exportPdfConTipo(btn.getAttribute('data-pdf-tipo') || 'ambos');
        });
    });
})();
</script>
<?= $this->endSection() ?>
