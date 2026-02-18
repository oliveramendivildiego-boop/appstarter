<?= view('partial/header', ['allowed_modules' => $allowed_modules ?? [], 'user_info' => $user_info ?? null, 'current_module' => 'labotests']) ?>

<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_labotests'), 'url' => site_url('labotests')],
    ['label' => 'Manuales', 'url' => site_url('labotests/manuales')],
    ['label' => $labotests_info->name ?? '', 'url' => null],
]]) ?>

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
    <h4 class="mb-4 d-flex justify-content-between align-items-center">
        <span><i class="fa-solid fa-book me-2"></i>Manuales y notas: <?= esc($labotests_info->name ?? '') ?></span>
        <button type="button" class="btn btn-success btn-sm" id="btn_agregar_manual" data-pria="<?= (int)($prianacategoria_id ?? 0) ?>">
            <i class="fa-solid fa-plus me-1"></i> Agregar manual
        </button>
    </h4>

    <?php if (empty($manuals)): ?>
    <div class="alert alert-info">
        <i class="fa-solid fa-info-circle me-2"></i>No hay manuales o notas registradas. Use <strong>Agregar manual</strong> para crear uno.
    </div>
    <?php else:
        $proveedores = ['Human', 'Wiener Lab', 'Biomerieux', 'Randox', 'SpinReact'];
        $grupos = [];
        foreach ($manuals as $m) {
            $tit = $m['tittle'] ?? '';
            $prov = 'Otros';
            foreach ($proveedores as $p) {
                if (strpos($tit, $p) !== false) { $prov = $p; break; }
            }
            if (!isset($grupos[$prov])) $grupos[$prov] = [];
            $grupos[$prov][] = $m;
        }
        $orden = array_flip(array_merge($proveedores, ['Otros']));
        uksort($grupos, fn($a, $b) => ($orden[$a] ?? 99) - ($orden[$b] ?? 99));
        foreach ($grupos as $prov => $manualesProv):
    ?>
    <h5 class="mt-4 mb-2 text-primary"><i class="fa-solid fa-flask me-1"></i> <?= esc($prov) ?></h5>
    <div class="row mb-4">
        <?php foreach ($manualesProv as $m): ?>
        <div class="col-12 col-md-6 col-lg-4 mb-3">
            <div class="card h-100 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                    <strong class="small"><?= esc($m['tittle'] ?? 'Sin título') ?></strong>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-light btn-sm btn-ver-manual" title="Maximizar"
                            data-id="<?= (int)($m['manuals_id'] ?? 0) ?>">
                            <i class="fa-solid fa-maximize"></i>
                        </button>
                        <button type="button" class="btn btn-outline-light btn-sm btn-editar-manual" title="Editar"
                            data-id="<?= (int)($m['manuals_id'] ?? 0) ?>"
                            data-tittle="<?= esc($m['tittle'] ?? '') ?>">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <a href="<?= site_url('labotests/deletemanual/' . (int)($m['manuals_id'] ?? 0)) ?>" class="btn btn-outline-light btn-sm text-danger" title="Eliminar"
                            onclick="return confirm('¿Eliminar este manual?');">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body overflow-auto" style="max-height: 320px;">
                    <?= $m['manual'] ?? '' ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; endif; ?>

    <div class="mt-3">
        <a href="<?= site_url('labotests/manuales') ?>" class="btn btn-outline-secondary"><i class="fa-solid fa-book me-1"></i> Índice de manuales</a>
        <a href="<?= site_url('labotests') ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Exámenes</a>
        <a href="<?= site_url('labotests/detail/' . ($labotests_info->prianacategoria_id ?? 0)) ?>" class="btn btn-primary"><i class="fa-solid fa-gear me-1"></i> Configuración</a>
    </div>
</div>

<script type="application/json" id="manuals-data"><?= json_encode(array_map(fn($m) => ['id' => (int)($m['manuals_id'] ?? 0), 'tittle' => $m['tittle'] ?? '', 'manual' => $m['manual'] ?? ''], $manuals ?? [])) ?></script>

<!-- Modal Ver manual maximizado -->
<div class="modal fade" id="modalVerManual" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVerManualTitle">Manual</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body overflow-auto" id="modalVerManualBody" style="max-height: 80vh;">
            </div>
        </div>
    </div>
</div>

<!-- Modal Agregar/Editar manual -->
<div class="modal fade" id="modalManual" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalManualTitle">Agregar manual</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <?= form_open('labotests/savemanual', ['id' => 'form_manual']) ?>
            <?= csrf_field() ?>
            <div class="modal-body">
                <input type="hidden" name="prianacategoria_id" id="manual_prianacategoria_id" value="<?= (int)($prianacategoria_id ?? 0) ?>">
                <input type="hidden" name="manuals_id" id="manual_manuals_id" value="0">
                <div class="mb-3">
                    <label for="manual_tittle" class="form-label">Título <span class="text-danger">*</span></label>
                    <input type="text" name="tittle" id="manual_tittle" class="form-control" required placeholder="Ej: Toma de muestra">
                </div>
                <div class="mb-3">
                    <label for="manual_content" class="form-label">Contenido</label>
                    <textarea name="manual" id="manual_content" class="form-control" rows="12"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
            <?= form_close() ?>
        </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var modalEl = document.getElementById('modalManual');
    var modal = modalEl ? new bootstrap.Modal(modalEl) : null;
    var modalTitle = document.getElementById('modalManualTitle');
    var formManual = document.getElementById('form_manual');
    var manualContent = document.getElementById('manual_content');
    var manualTittle = document.getElementById('manual_tittle');
    var manualId = document.getElementById('manual_manuals_id');

    function initEditor() {
        if (typeof jQuery !== 'undefined' && jQuery().summernote && manualContent && !jQuery(manualContent).data('summernote')) {
            jQuery(manualContent).summernote({
                height: 280,
                toolbar: [
                    ['style', ['bold', 'italic', 'underline']],
                    ['para', ['ul', 'ol']],
                    ['insert', ['link']],
                    ['view', ['codeview']]
                ]
            });
        }
    }

    document.getElementById('btn_agregar_manual')?.addEventListener('click', function() {
        manualId.value = '0';
        manualTittle.value = '';
        if (jQuery(manualContent).length && jQuery(manualContent).data('summernote')) {
            jQuery(manualContent).summernote('code', '');
        } else {
            manualContent.value = '';
        }
        if (modalTitle) modalTitle.textContent = 'Agregar manual';
        modal?.show();
    });

    var manualsData = {};
    try {
        var dataEl = document.getElementById('manuals-data');
        if (dataEl) {
            (JSON.parse(dataEl.textContent) || []).forEach(function(m) {
                manualsData[m.id] = m;
            });
        }
    } catch (e) {}

    var modalVerEl = document.getElementById('modalVerManual');
    var modalVer = modalVerEl ? new bootstrap.Modal(modalVerEl) : null;
    var modalVerTitle = document.getElementById('modalVerManualTitle');
    var modalVerBody = document.getElementById('modalVerManualBody');

    document.querySelectorAll('.btn-ver-manual').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = parseInt(btn.dataset.id || '0', 10);
            var m = manualsData[id];
            if (modalVerTitle) modalVerTitle.textContent = m && m.tittle ? m.tittle : 'Manual';
            if (modalVerBody) modalVerBody.innerHTML = m && m.manual ? m.manual : '<p class="text-muted">Sin contenido</p>';
            modalVer?.show();
        });
    });

    document.querySelectorAll('.btn-editar-manual').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = parseInt(btn.dataset.id || '0', 10);
            var tittle = btn.dataset.tittle || '';
            var manualHtml = (manualsData[id] && manualsData[id].manual) ? manualsData[id].manual : '';
            manualId.value = id;
            manualTittle.value = tittle;
            if (jQuery(manualContent).length && jQuery(manualContent).data('summernote')) {
                jQuery(manualContent).summernote('code', manualHtml);
            } else {
                manualContent.value = manualHtml;
            }
            if (modalTitle) modalTitle.textContent = 'Editar manual';
            modal?.show();
        });
    });

    modalEl?.addEventListener('shown.bs.modal', function() {
        initEditor();
    });

    modalEl?.addEventListener('hidden.bs.modal', function() {
        if (jQuery(manualContent).length && jQuery(manualContent).data('summernote')) {
            manualContent.value = jQuery(manualContent).summernote('code');
            jQuery(manualContent).summernote('destroy');
        }
    });

    formManual?.addEventListener('submit', function() {
        if (jQuery(manualContent).length && jQuery(manualContent).data('summernote')) {
            manualContent.value = jQuery(manualContent).summernote('code');
        }
    });
});
</script>
<?= view('partial/footer') ?>
