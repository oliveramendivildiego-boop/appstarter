<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$labRight = '<a href="' . site_url('labotests/perfiles') . '" class="btn btn-outline-info btn-sm me-1">Perfiles</a>' .
    '<a href="' . site_url('labotests/opciones') . '" class="btn btn-outline-secondary btn-sm me-1"><i class="fa-solid fa-list-check me-1"></i> Tipos resultado</a>' .
    '<a href="' . site_url('labotests/manuales') . '" class="btn btn-outline-success btn-sm me-1"><i class="fa-solid fa-book me-1"></i> Manuales</a>' .
    '<a href="' . site_url('labotests/view') . '" class="btn btn-success btn-sm"><i class="fa-solid fa-plus me-1"></i> ' . lang('Labotests.labotests_new_group') . '</a>';
?>
<?= view('partial/breadcrumb_nav', [
    'items' => [['label' => lang('Module.module_labotests'), 'url' => null]],
    'right' => $labRight,
]) ?>

<div class="mb-3">
    <?= form_open(site_url('labotests'), ['method' => 'get', 'class' => 'd-flex gap-2 flex-wrap align-items-center', 'role' => 'search']) ?>
    <input type="hidden" name="page" value="1">
    <input type="search" name="q" class="form-control form-control-sm" style="max-width:280px" placeholder="Buscar examen o grupo..." value="<?= esc($search ?? '') ?>">
    <button type="submit" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-search"></i> Buscar</button>
    <?php if (!empty($search)): ?>
    <a href="<?= site_url('labotests') ?>" class="btn btn-outline-secondary btn-sm">Limpiar</a>
    <?php endif; ?>
    <?= form_close() ?>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?= esc(session()->getFlashdata('success')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <?= esc(session()->getFlashdata('error')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h4 class="mb-0"><?= lang('Module.module_labotests') ?></h4>
        <?php if (empty($search) && ! empty($all_categories)): ?>
        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#reorderGroupsModal">
            <i class="fa-solid fa-arrow-down-up-across-line me-1"></i> Ordenar grupos (<?= count($all_categories) ?>)
        </button>
        <?php endif; ?>
    </div>

    <div class="row labotests-groups-row">
        <?php foreach ($categories ?? [] as $cat): ?>
        <div class="col-12 col-md-6 col-lg-4 mb-4">
            <div class="card shadow-sm h-100 labotests-group-card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center gap-2 min-w-0">
                    <h6 class="mb-0 d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                        <a href="<?= site_url('labotests/view/' . $cat['id']) ?>" class="text-white text-decoration-none text-truncate">
                            <i class="fa-solid fa-flask-vial me-2"></i><?= esc($cat['name']) ?>
                        </a>
                    </h6>
                    <div class="btn-group btn-group-sm">
                        <a href="<?= site_url('labotests/subview/' . $cat['id']) ?>" class="btn btn-light" title="<?= lang('Labotests.labotests_new_analysis') ?>">
                            <i class="fa-solid fa-plus"></i>
                        </a>
                        <a href="<?= site_url('labotests/deletecategory/' . $cat['id']) ?>" class="btn btn-outline-light" title="Eliminar categoría y todos sus análisis" onclick="return uiConfirmLink(this, '¿Eliminar la categoría y todos sus análisis con configuraciones? Esta acción no se puede deshacer.');">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body p-0 labotests-analysis-body">
                    <ul class="list-group list-group-flush labotests-analysis-list" data-category-id="<?= (int) $cat['id'] ?>">
                        <?php foreach ($cat['items'] ?? [] as $item): ?>
                        <li class="list-group-item labotests-analysis-item" data-analysis-id="<?= (int) $item['id'] ?>">
                            <div class="labotests-analysis-item__row">
                                <span class="labotests-analysis-item__label">
                                    <span class="labotests-analysis-drag" title="Arrastrar para mover o reordenar"><i class="fa-solid fa-grip-vertical"></i></span>
                                    <span class="labotests-analysis-item__name" title="<?= esc($item['name'], 'attr') ?>"><?= esc($item['name']) ?></span>
                                </span>
                                <div class="btn-group btn-group-sm labotests-analysis-item__actions" role="group" aria-label="Acciones del análisis">
                                    <a href="<?= site_url('labotests/detail/' . $item['id']) ?>" class="btn btn-outline-primary" title="<?= lang('Labotests.labotests_config') ?>">
                                        <i class="fa-solid fa-gear"></i>
                                    </a>
                                    <a href="<?= site_url('labotests/manuales/' . $item['id']) ?>" class="btn btn-outline-info" title="Ver manuales y notas">
                                        <i class="fa-solid fa-book"></i>
                                    </a>
                                    <a href="<?= site_url('labotests/recomendacionesprevias/' . $item['id']) ?>" class="btn btn-outline-warning" title="<?= lang('Labotests.labotests_recomendaciones_previas') ?>">
                                        <i class="fa-solid fa-clipboard-list"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-secondary btn-duplicate-analysis" title="Duplicar prueba a otro padre" data-bs-toggle="modal" data-bs-target="#duplicateAnalysisModal" data-analysis-id="<?= (int) $item['id'] ?>" data-analysis-name="<?= esc($item['name'], 'attr') ?>" data-current-parent="<?= (int) $cat['id'] ?>">
                                        <i class="fa-solid fa-copy"></i>
                                    </button>
                                    <a href="<?= site_url('labotests/deleteprianacategoria/' . $item['id']) ?>" class="btn btn-outline-danger" title="Eliminar análisis y configuraciones" onclick="return uiConfirmLink(this, '¿Eliminar este análisis y todas sus configuraciones? Esta acción no se puede deshacer.');">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                        <?php if (empty($cat['items'])): ?>
                        <li class="list-group-item text-muted labotests-empty-item"><?= lang('Labotests.labotests_no_items') ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($categories)): ?>
    <div class="alert alert-info">
        <i class="fa-solid fa-info-circle"></i>
        <?php if (!empty($search)): ?>
            No se encontraron grupos o exámenes para "<?= esc($search) ?>".
        <?php else: ?>
            <?= lang('Labotests.labotests_no_categories') ?>
            <a href="<?= site_url('labotests/view') ?>" class="alert-link"><?= lang('Labotests.labotests_new_group') ?></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (($total_pages ?? 1) > 1): ?>
    <?php
    $p = $page ?? 1;
    $tp = $total_pages ?? 1;
    $prevPage = max(1, $p - 1);
    $nextPage = min($tp, $p + 1);
    $params = ['page' => 1];
    if (!empty($search)) {
        $params['q'] = $search;
    }
    ?>
    <nav class="mt-4" aria-label="Paginación">
        <ul class="pagination justify-content-center flex-wrap">
            <li class="page-item <?= $p <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $p <= 1 ? '#' : site_url('labotests?' . http_build_query(array_merge($params, ['page' => $prevPage]))) ?>">&laquo;</a>
            </li>
            <?php for ($i = 1; $i <= $tp; $i++): ?>
            <li class="page-item <?= ($i === $p) ? 'active' : '' ?>">
                <a class="page-link" href="<?= site_url('labotests?' . http_build_query(array_merge($params, ['page' => $i]))) ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?= $p >= $tp ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $p >= $tp ? '#' : site_url('labotests?' . http_build_query(array_merge($params, ['page' => $nextPage]))) ?>">&raquo;</a>
            </li>
        </ul>
        <p class="text-center text-muted small">Página <?= $p ?> de <?= $tp ?> (<?= $total ?? 0 ?> grupos)</p>
    </nav>
    <?php endif; ?>
</div>

<?php if (empty($search) && ! empty($all_categories)): ?>
<div class="modal fade" id="reorderGroupsModal" tabindex="-1" aria-labelledby="reorderGroupsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reorderGroupsModalLabel">Ordenar grupos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Arrastra los grupos para definir el orden global. El cambio se guarda automaticamente al soltar.</p>
                <div id="labotests-reorder-groups-status" class="small text-muted mb-2"></div>
                <ul class="list-group labotests-reorder-groups-list" id="labotests-reorder-groups-list">
                    <?php foreach ($all_categories as $cat): ?>
                    <li class="list-group-item d-flex align-items-center gap-2 labotests-reorder-group-item" data-category-id="<?= (int) $cat['id'] ?>">
                        <span class="labotests-reorder-group-drag" title="Arrastrar para reordenar"><i class="fa-solid fa-grip-vertical"></i></span>
                        <span class="flex-grow-1 text-truncate" title="<?= esc($cat['name'], 'attr') ?>"><?= esc($cat['name']) ?></span>
                        <span class="badge text-bg-light border"><?= (int) ($cat['items_count'] ?? 0) ?> analisis</span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="modal fade" id="duplicateAnalysisModal" tabindex="-1" aria-labelledby="duplicateAnalysisModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <?= form_open('labotests/duplicateanalysis', ['id' => 'duplicateAnalysisForm']) ?>
            <div class="modal-header">
                <h5 class="modal-title" id="duplicateAnalysisModalLabel">Duplicar prueba</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="prianacategoria_id" id="duplicate_prianacategoria_id" value="">
                <p class="mb-3">
                    Se copiara <strong id="duplicate_analysis_name">esta prueba</strong> con sus valores y configuraciones al padre seleccionado.
                </p>
                <label for="duplicate_target_anacategoria_id" class="form-label">Padre destino</label>
                <select name="target_anacategoria_id" id="duplicate_target_anacategoria_id" class="form-select" required>
                    <option value="">Seleccione un padre</option>
                    <?php foreach (($category_options ?? []) as $option): ?>
                    <option value="<?= (int) ($option['anacategoria_id'] ?? 0) ?>"><?= esc($option['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">La prueba original no se modifica.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Duplicar</button>
            </div>
            <?= form_close() ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/vendor/sortable.min.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var lists = Array.prototype.slice.call(document.querySelectorAll('.labotests-analysis-list'));
    var reorderList = document.getElementById('labotests-reorder-groups-list');
    if (!lists.length && !reorderList) {
        return;
    }

    var status = document.createElement('div');
    status.className = 'small text-muted mt-2';
    status.id = 'labotests-sort-status';
    status.textContent = reorderList
        ? 'Puedes arrastrar un analisis para cambiarlo de grupo o reordenarlo. Usa "Ordenar grupos" para ver y mover todos los grupos.'
        : 'Puedes arrastrar un analisis para cambiarlo de grupo o reordenarlo.';
    var wrapper = document.querySelector('.py-3 .d-flex');
    if (wrapper && wrapper.parentNode) {
        wrapper.parentNode.insertBefore(status, wrapper.nextSibling);
    }

    function getCsrfData() {
        return {
            name: window.CI_CSRF_TOKEN_NAME || 'csrf_test_name',
            value: window.CI_CSRF_TOKEN || ''
        };
    }

    function applyCsrfData(data) {
        if (!data || !data.csrf_token || !data.csrf_name) {
            return;
        }
        window.CI_CSRF_TOKEN = data.csrf_token;
        window.CI_CSRF_TOKEN_NAME = data.csrf_name;
    }

    function setStatus(message, className) {
        if (!status) {
            return;
        }
        status.className = 'small mt-2 ' + (className || 'text-muted');
        status.textContent = message;
    }

    function buildFetchHeaders(csrf) {
        var headers = { 'X-Requested-With': 'XMLHttpRequest' };
        if (csrf.value) {
            headers['X-CSRF-TOKEN'] = csrf.value;
        }
        return headers;
    }

    function saveGroupOrder(modalStatusEl) {
        if (!reorderList) {
            return Promise.resolve();
        }

        if (modalStatusEl) {
            modalStatusEl.className = 'small text-muted mb-2';
            modalStatusEl.textContent = 'Guardando orden de grupos...';
        } else {
            setStatus('Guardando orden de grupos...', 'text-muted');
        }

        var csrf = getCsrfData();
        var fd = new FormData();
        fd.append('ordered_ids', JSON.stringify(
            Array.prototype.slice.call(reorderList.querySelectorAll('.labotests-reorder-group-item')).map(function(item) {
                return parseInt(item.getAttribute('data-category-id') || '0', 10);
            }).filter(function(id) {
                return id > 0;
            })
        ));
        if (csrf.value) {
            fd.append(csrf.name, csrf.value);
        }

        return fetch('<?= site_url('labotests/reordercategories') ?>', {
            method: 'POST',
            headers: buildFetchHeaders(csrf),
            body: fd
        })
            .then(function(response) {
                return response.json().then(function(data) {
                    return { ok: response.ok, data: data };
                });
            })
            .then(function(result) {
                applyCsrfData(result.data);
                if (!result.ok || !result.data || !result.data.success) {
                    throw new Error((result.data && result.data.message) || 'No se pudo guardar');
                }
                if (modalStatusEl) {
                    modalStatusEl.className = 'small text-success mb-2';
                    modalStatusEl.textContent = 'Orden de grupos guardado.';
                }
                setStatus('Orden de grupos guardado.', 'text-success');
            })
            .catch(function(error) {
                if (modalStatusEl) {
                    modalStatusEl.className = 'small text-danger mb-2';
                    modalStatusEl.textContent = error.message + '. Se recargara la pagina.';
                }
                setStatus(error.message + '. Se recargara la pagina para conservar los datos.', 'text-danger');
                window.setTimeout(function() {
                    window.location.reload();
                }, 1600);
                throw error;
            });
    }

    if (reorderList && typeof Sortable !== 'undefined') {
        var reorderItems = reorderList.querySelectorAll('.labotests-reorder-group-item');
        var modalStatusEl = document.getElementById('labotests-reorder-groups-status');
        var groupsOrderSaved = false;
        var reorderModal = document.getElementById('reorderGroupsModal');
        if (reorderItems.length > 1) {
            new Sortable(reorderList, {
                animation: 150,
                handle: '.labotests-reorder-group-drag',
                draggable: '.labotests-reorder-group-item',
                ghostClass: 'labotests-reorder-group-ghost',
                chosenClass: 'labotests-reorder-group-chosen',
                onEnd: function() {
                    saveGroupOrder(modalStatusEl).then(function() {
                        groupsOrderSaved = true;
                    }).catch(function() {
                        groupsOrderSaved = false;
                    });
                }
            });
        }
        if (reorderModal) {
            reorderModal.addEventListener('hidden.bs.modal', function() {
                if (groupsOrderSaved) {
                    window.location.reload();
                }
            });
        }
    }

    if (!lists.length || typeof Sortable === 'undefined') {
        return;
    }

    function removeEmptyItems() {
        document.querySelectorAll('.labotests-empty-item').forEach(function(item) {
            item.remove();
        });
    }

    function refreshEmptyItems() {
        lists.forEach(function(list) {
            list.querySelectorAll('.labotests-empty-item').forEach(function(item) {
                item.remove();
            });
            if (!list.querySelector('.labotests-analysis-item')) {
                var empty = document.createElement('li');
                empty.className = 'list-group-item text-muted labotests-empty-item';
                empty.textContent = 'Suelta aquí un análisis';
                list.appendChild(empty);
            }
        });
    }

    function buildPayload() {
        return lists.map(function(list) {
            return {
                parent_id: parseInt(list.getAttribute('data-category-id') || '0', 10),
                children: Array.prototype.slice.call(list.querySelectorAll('.labotests-analysis-item')).map(function(item) {
                    return parseInt(item.getAttribute('data-analysis-id') || '0', 10);
                }).filter(function(id) {
                    return id > 0;
                })
            };
        });
    }

    function savePlacement() {
        removeEmptyItems();
        setStatus('Guardando nuevo orden...', 'text-muted');

        var csrf = getCsrfData();
        var fd = new FormData();
        fd.append('groups', JSON.stringify(buildPayload()));
        if (csrf.value) {
            fd.append(csrf.name, csrf.value);
        }

        fetch('<?= site_url('labotests/reorderanalysis') ?>', {
            method: 'POST',
            headers: buildFetchHeaders(csrf),
            body: fd
        })
            .then(function(response) {
                return response.json().then(function(data) {
                    return { ok: response.ok, data: data };
                });
            })
            .then(function(result) {
                applyCsrfData(result.data);
                if (!result.ok || !result.data || !result.data.success) {
                    throw new Error((result.data && result.data.message) || 'No se pudo guardar');
                }
                refreshEmptyItems();
                setStatus('Orden guardado.', 'text-success');
            })
            .catch(function(error) {
                refreshEmptyItems();
                setStatus(error.message + '. Se recargara la pagina para conservar los datos.', 'text-danger');
                window.setTimeout(function() {
                    window.location.reload();
                }, 1600);
            });
    }

    lists.forEach(function(list) {
        new Sortable(list, {
            group: 'labotests-analysis',
            animation: 150,
            handle: '.labotests-analysis-drag',
            draggable: '.labotests-analysis-item',
            filter: '.labotests-empty-item',
            ghostClass: 'labotests-sortable-ghost',
            onStart: removeEmptyItems,
            onEnd: savePlacement
        });
    });

    refreshEmptyItems();
});

document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('duplicateAnalysisModal');
    if (!modal) {
        return;
    }

    modal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;
        if (!button) {
            return;
        }

        var analysisId = button.getAttribute('data-analysis-id') || '';
        var analysisName = button.getAttribute('data-analysis-name') || 'esta prueba';
        var currentParent = button.getAttribute('data-current-parent') || '';
        var input = document.getElementById('duplicate_prianacategoria_id');
        var nameLabel = document.getElementById('duplicate_analysis_name');
        var select = document.getElementById('duplicate_target_anacategoria_id');

        if (input) {
            input.value = analysisId;
        }
        if (nameLabel) {
            nameLabel.textContent = analysisName;
        }
        if (select) {
            select.value = '';
            Array.prototype.slice.call(select.options).forEach(function(option) {
                option.disabled = option.value !== '' && option.value === currentParent;
            });
        }
    });
});
</script>
<?= $this->endSection() ?>
