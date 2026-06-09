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
        <div class="d-flex flex-wrap align-items-center gap-2">
            <?php if (empty($search)): ?>
            <div class="dropdown">
                <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="labotestsTransformNamesBtn">
                    <i class="fa-solid fa-font me-1"></i> Formato de nombres
                </button>
                <ul class="dropdown-menu dropdown-menu-end labotests-transform-menu">
                    <li><h6 class="dropdown-header">Aplicar a todos los grupos y análisis</h6></li>
                    <li>
                        <button type="button" class="dropdown-item labotests-transform-action" data-mode="uppercase">
                            <i class="fa-solid fa-text-height me-2 text-muted"></i> TODO EN MAYÚSCULAS
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item labotests-transform-action" data-mode="sentence">
                            <i class="fa-solid fa-a me-2 text-muted"></i> Primera letra en mayúscula
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item labotests-transform-action" data-mode="title">
                            <i class="fa-solid fa-heading me-2 text-muted"></i> Primera letra de cada palabra
                        </button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <button type="button" class="dropdown-item labotests-transform-action" data-mode="spell">
                            <i class="fa-solid fa-spell-check me-2 text-muted"></i> Corrección ortográfica
                        </button>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
            <?php if (empty($search)): ?>
            <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#duplicateAnalysesModal">
                <i class="fa-solid fa-clone me-1"></i> Análisis duplicados
            </button>
            <?php endif; ?>
            <?php if (empty($search) && ! empty($all_categories)): ?>
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#reorderGroupsModal">
                <i class="fa-solid fa-arrow-down-up-across-line me-1"></i> Ordenar grupos (<?= count($all_categories) ?>)
            </button>
            <?php endif; ?>
        </div>
    </div>
    <div id="labotests-transform-status" class="small text-muted mb-3 d-none"></div>

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
                                    <button type="button"
                                        class="btn btn-outline-danger btn-delete-analysis"
                                        title="Eliminar análisis y configuraciones"
                                        data-analysis-id="<?= (int) $item['id'] ?>"
                                        data-analysis-name="<?= esc($item['name'], 'attr') ?>">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
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

<div class="modal fade" id="duplicateAnalysesModal" tabindex="-1" aria-labelledby="duplicateAnalysesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="duplicateAnalysesModalLabel">
                    <i class="fa-solid fa-clone me-2"></i>Análisis duplicados
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    Muestra análisis con el mismo nombre en distintos grupos e indica en qué perfiles de exámenes está cada registro.
                </p>
                <div id="labotests-duplicates-status" class="small text-muted mb-3">Cargando...</div>
                <div id="labotests-duplicates-content"></div>
            </div>
            <div class="modal-footer">
                <a href="<?= site_url('labotests/perfiles') ?>" class="btn btn-outline-info btn-sm me-auto">
                    <i class="fa-solid fa-layer-group me-1"></i> Gestionar perfiles
                </a>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="labotestsDeleteAnalysisModal" tabindex="-1" aria-labelledby="labotestsDeleteAnalysisModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="labotestsDeleteAnalysisModalLabel">Eliminar análisis</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="labotestsDeleteAnalysisModalBody">
                <div class="text-muted small">Cargando impacto en órdenes registradas...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="labotestsDeleteAnalysisConfirmBtn" disabled>Eliminar</button>
            </div>
        </div>
    </div>
</div>

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
    var transformStatus = document.getElementById('labotests-transform-status');
    var transformActions = document.querySelectorAll('.labotests-transform-action');
    if (!transformActions.length) {
        return;
    }

    var modeLabels = {
        uppercase: 'convertir todos los nombres a MAYÚSCULAS',
        sentence: 'poner la primera letra en mayúscula y el resto en minúsculas',
        title: 'poner la primera letra de cada palabra en mayúscula',
        spell: 'corregir la ortografía de todos los nombres'
    };
    var transformBusy = false;
    var transformToggle = document.getElementById('labotestsTransformNamesBtn');

    function setTransformBusy(busy) {
        transformBusy = !!busy;
        if (transformToggle) {
            transformToggle.disabled = transformBusy;
            transformToggle.classList.toggle('disabled', transformBusy);
            transformToggle.setAttribute('aria-busy', transformBusy ? 'true' : 'false');
        }
    }

    function parseJsonResponse(response) {
        return response.text().then(function(text) {
            var data = null;
            if (text) {
                try {
                    data = JSON.parse(text);
                } catch (error) {
                    throw new Error('Respuesta inválida del servidor');
                }
            }
            return { ok: response.ok, data: data };
        });
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

    function setTransformStatus(message, className, show) {
        if (!transformStatus) {
            return;
        }
        transformStatus.className = 'small mb-3 ' + (className || 'text-muted') + (show ? '' : ' d-none');
        transformStatus.textContent = message || '';
    }

    function runTransform(mode) {
        if (transformBusy) {
            return;
        }
        var label = modeLabels[mode] || 'transformar los nombres';
        var confirmMessage = '¿Confirma ' + label + ' en todos los grupos y análisis? Esta acción modifica la base de datos.';
        var proceed = function() {
            setTransformStatus('Aplicando formato...', 'text-muted', true);
            setTransformBusy(true);

            var csrf = getCsrfData();
            var fd = new FormData();
            fd.append('mode', mode);
            if (csrf.value) {
                fd.append(csrf.name, csrf.value);
            }

            var willReload = false;
            fetch('<?= site_url('labotests/transformnames') ?>', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf.value || ''
                },
                body: fd
            })
                .then(parseJsonResponse)
                .then(function(result) {
                    applyCsrfData(result.data);
                    if (!result.ok || !result.data || !result.data.success) {
                        throw new Error((result.data && result.data.message) || 'No se pudo aplicar el formato');
                    }
                    setTransformStatus(result.data.message, 'text-success', true);
                    willReload = true;
                    window.setTimeout(function() {
                        window.location.reload();
                    }, 900);
                })
                .catch(function(error) {
                    setTransformStatus(error.message, 'text-danger', true);
                })
                .finally(function() {
                    if (!willReload) {
                        setTransformBusy(false);
                    } else {
                        window.setTimeout(function() {
                            setTransformBusy(false);
                        }, 4000);
                    }
                });
        };

        if (typeof uiConfirm === 'function') {
            uiConfirm(confirmMessage, 'Confirmar formato').then(function(ok) {
                if (ok) {
                    proceed();
                }
            });
            return;
        }
        if (window.confirm(confirmMessage)) {
            proceed();
        }
    }

    transformActions.forEach(function(button) {
        button.addEventListener('click', function() {
            var mode = button.getAttribute('data-mode') || '';
            if (!mode) {
                return;
            }
            runTransform(mode);
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    var duplicatesModal = document.getElementById('duplicateAnalysesModal');
    if (duplicatesModal) {
        var duplicatesStatus = document.getElementById('labotests-duplicates-status');
        var duplicatesContent = document.getElementById('labotests-duplicates-content');
        var duplicatesLoaded = false;
        var duplicatesLoading = false;

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function renderDuplicates(data) {
            if (!duplicatesContent || !duplicatesStatus) {
                return;
            }

            var groups = (data && data.duplicates) || [];
            if (!groups.length) {
                duplicatesStatus.className = 'small text-success mb-3';
                duplicatesStatus.textContent = 'No se encontraron análisis con nombre duplicado.';
                duplicatesContent.innerHTML = '';
                return;
            }

            var totalGroups = data.total_groups || groups.length;
            var totalEntries = data.total_entries || 0;
            duplicatesStatus.className = 'small text-warning mb-3';
            duplicatesStatus.textContent = totalGroups + ' nombre(s) duplicado(s), ' + totalEntries + ' registros en total.';

            var html = '<div class="accordion labotests-duplicates-accordion" id="labotestsDuplicatesAccordion">';
            groups.forEach(function(group, index) {
                var collapseId = 'labotests-dup-' + index;
                var headingId = 'labotests-dup-heading-' + index;
                var entries = group.entries || [];
                var rows = entries.map(function(entry) {
                    var perfiles = entry.perfiles || [];
                    var perfilesHtml = perfiles.length
                        ? perfiles.map(function(perfil) {
                            return '<span class="badge text-bg-light border me-1 mb-1">' + escapeHtml(perfil) + '</span>';
                        }).join('')
                        : '<span class="text-muted small">Sin perfiles</span>';
                    var detailUrl = '<?= site_url('labotests/detail/') ?>' + entry.id;
                    return '<tr>'
                        + '<td><a href="' + detailUrl + '">#' + entry.id + '</a></td>'
                        + '<td>' + escapeHtml(entry.name) + '</td>'
                        + '<td>' + escapeHtml(entry.category_name || '—') + '</td>'
                        + '<td>' + perfilesHtml + '</td>'
                        + '</tr>';
                }).join('');

                html += '<div class="accordion-item">'
                    + '<h2 class="accordion-header" id="' + headingId + '">'
                    + '<button class="accordion-button' + (index > 0 ? ' collapsed' : '') + '" type="button" data-bs-toggle="collapse" data-bs-target="#' + collapseId + '" aria-expanded="' + (index === 0 ? 'true' : 'false') + '" aria-controls="' + collapseId + '">'
                    + '<span class="fw-semibold me-2">' + escapeHtml(group.name) + '</span>'
                    + '<span class="badge text-bg-warning">' + (group.count || entries.length) + ' registros</span>'
                    + '</button>'
                    + '</h2>'
                    + '<div id="' + collapseId + '" class="accordion-collapse collapse' + (index === 0 ? ' show' : '') + '" aria-labelledby="' + headingId + '" data-bs-parent="#labotestsDuplicatesAccordion">'
                    + '<div class="accordion-body p-0">'
                    + '<div class="table-responsive">'
                    + '<table class="table table-sm table-striped mb-0 labotests-duplicates-table">'
                    + '<thead><tr><th>ID</th><th>Nombre</th><th>Grupo</th><th>Perfiles</th></tr></thead>'
                    + '<tbody>' + rows + '</tbody>'
                    + '</table>'
                    + '</div>'
                    + '</div>'
                    + '</div>'
                    + '</div>';
            });
            html += '</div>';
            duplicatesContent.innerHTML = html;
        }

        function loadDuplicates(force) {
            if (!duplicatesStatus || !duplicatesContent) {
                return;
            }
            if (duplicatesLoading) {
                return;
            }
            if (duplicatesLoaded && !force) {
                return;
            }

            duplicatesLoading = true;
            duplicatesStatus.className = 'small text-muted mb-3';
            duplicatesStatus.textContent = 'Cargando análisis duplicados...';
            duplicatesContent.innerHTML = '';

            fetch('<?= site_url('labotests/duplicados') ?>', {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function(response) {
                    return response.json().then(function(data) {
                        return { ok: response.ok, data: data };
                    });
                })
                .then(function(result) {
                    if (!result.ok || !result.data || !result.data.success) {
                        throw new Error((result.data && result.data.message) || 'No se pudo cargar el reporte');
                    }
                    renderDuplicates(result.data);
                    duplicatesLoaded = true;
                })
                .catch(function(error) {
                    duplicatesStatus.className = 'small text-danger mb-3';
                    duplicatesStatus.textContent = error.message;
                    duplicatesContent.innerHTML = '';
                })
                .finally(function() {
                    duplicatesLoading = false;
                });
        }

        duplicatesModal.addEventListener('show.bs.modal', function() {
            loadDuplicates(false);
        });
    }

    var deleteModal = document.getElementById('labotestsDeleteAnalysisModal');
    var deleteModalBody = document.getElementById('labotestsDeleteAnalysisModalBody');
    var deleteConfirmBtn = document.getElementById('labotestsDeleteAnalysisConfirmBtn');
    var pendingDelete = null;

    function escapeDeleteHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function getDeleteCsrfData() {
        return {
            name: window.CI_CSRF_TOKEN_NAME || 'csrf_test_name',
            value: window.CI_CSRF_TOKEN || ''
        };
    }

    function renderDeleteImpact(data) {
        if (!deleteModalBody || !deleteConfirmBtn) {
            return;
        }

        var analysisName = escapeDeleteHtml(data.analysis_name || 'este análisis');
        var categoryName = escapeDeleteHtml(data.category_name || '');
        var orders = parseInt(data.registros_count || 0, 10);
        var values = parseInt(data.regvalues_with_data || 0, 10);
        var html = '<p class="mb-2">Va a eliminar <strong>' + analysisName + '</strong>';
        if (categoryName) {
            html += ' <span class="text-muted">(' + categoryName + ')</span>';
        }
        html += '.</p>';

        if (data.can_migrate) {
            html += '<div class="alert alert-info py-2 mb-3">'
                + '<i class="fa-solid fa-arrow-right-arrow-left me-1"></i> '
                + 'Existe un análisis duplicado de respaldo: <strong>' + escapeDeleteHtml(data.migrate_to_name || '') + '</strong>'
                + (data.migrate_to_category ? ' <span class="text-muted">(' + escapeDeleteHtml(data.migrate_to_category) + ')</span>' : '')
                + ' <span class="badge text-bg-light border">#' + parseInt(data.migrate_to_id || 0, 10) + '</span>.';
            if (orders > 0 || values > 0) {
                html += '<br><span class="small">Se actualizarán automáticamente '
                    + orders + ' orden(es) registrada(s)';
                if (values > 0) {
                    html += ' y ' + values + ' valor(es) guardado(s)';
                }
                html += ' en <strong>/registers/</strong> para apuntar al análisis que permanece.</span>';
            } else {
                html += '<br><span class="small">Las futuras referencias usarán el análisis que permanece.</span>';
            }
            html += '</div>';
        } else if (data.has_registered_values) {
            html += '<div class="alert alert-warning py-2 mb-3">'
                + '<i class="fa-solid fa-triangle-exclamation me-1"></i> '
                + 'Este análisis tiene '
                + orders + ' orden(es) registrada(s)';
            if (values > 0) {
                html += ' con ' + values + ' valor(es) guardado(s)';
            }
            html += ' y <strong>no hay otro duplicado</strong> al cual migrar.<br>'
                + '<span class="small">Se retirará del catálogo y de nuevas órdenes, pero <strong>las órdenes y reportes históricos conservarán sus datos</strong> en solo lectura (no se podrán editar).</span>'
                + '</div>';
        } else {
            html += '<p class="text-muted small mb-0">No hay órdenes registradas que dependan de este análisis.</p>';
        }

        html += '<p class="text-danger small mb-0">Esta acción no se puede deshacer.</p>';
        deleteModalBody.innerHTML = html;
        deleteConfirmBtn.disabled = false;
        deleteConfirmBtn.textContent = data.can_migrate ? 'Eliminar y migrar' : 'Eliminar';
        deleteConfirmBtn.className = 'btn btn-danger';
    }

    function submitDeleteAnalysis() {
        if (!pendingDelete || !deleteConfirmBtn) {
            return;
        }

        deleteConfirmBtn.disabled = true;
        deleteConfirmBtn.textContent = 'Eliminando...';

        var csrf = getDeleteCsrfData();
        var fd = new FormData();
        if (pendingDelete.migrateToId > 0) {
            fd.append('migrate_to_id', String(pendingDelete.migrateToId));
        }
        if (csrf.value) {
            fd.append(csrf.name, csrf.value);
        }

        fetch('<?= site_url('labotests/deleteprianacategoria/') ?>' + pendingDelete.analysisId, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf.value || ''
            },
            body: fd
        })
            .then(function(response) {
                if (response.redirected) {
                    window.location.href = response.url;
                    return null;
                }
                return response.text().then(function(text) {
                    throw new Error(text || 'No se pudo eliminar el análisis');
                });
            })
            .catch(function(error) {
                if (deleteModalBody) {
                    deleteModalBody.innerHTML = '<div class="alert alert-danger py-2 mb-0">' + escapeDeleteHtml(error.message) + '</div>';
                }
                if (deleteConfirmBtn) {
                    deleteConfirmBtn.disabled = false;
                    deleteConfirmBtn.textContent = 'Eliminar';
                }
            });
    }

    if (deleteModal && deleteModalBody && deleteConfirmBtn) {
        document.querySelectorAll('.btn-delete-analysis').forEach(function(button) {
            button.addEventListener('click', function() {
                var analysisId = parseInt(button.getAttribute('data-analysis-id') || '0', 10);
                if (analysisId < 1) {
                    return;
                }

                pendingDelete = {
                    analysisId: analysisId,
                    migrateToId: 0
                };
                deleteModalBody.innerHTML = '<div class="text-muted small">Cargando impacto en órdenes registradas...</div>';
                deleteConfirmBtn.disabled = true;
                deleteConfirmBtn.textContent = 'Eliminar';

                var modalInstance = bootstrap.Modal.getOrCreateInstance(deleteModal);
                modalInstance.show();

                fetch('<?= site_url('labotests/prianacategoriadeletepreview/') ?>' + analysisId, {
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(function(response) {
                        return response.json().then(function(data) {
                            return { ok: response.ok, data: data };
                        });
                    })
                    .then(function(result) {
                        if (!result.ok || !result.data || !result.data.success) {
                            throw new Error((result.data && result.data.message) || 'No se pudo evaluar el impacto');
                        }
                        pendingDelete.migrateToId = result.data.can_migrate
                            ? parseInt(result.data.migrate_to_id || 0, 10)
                            : 0;
                        renderDeleteImpact(result.data);
                    })
                    .catch(function(error) {
                        deleteModalBody.innerHTML = '<div class="alert alert-danger py-2 mb-0">' + escapeDeleteHtml(error.message) + '</div>';
                    });
            });
        });

        deleteConfirmBtn.addEventListener('click', submitDeleteAnalysis);
    }

    var modal = document.getElementById('duplicateAnalysisModal');
    if (modal) {
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
    }
});
</script>
<?= $this->endSection() ?>
