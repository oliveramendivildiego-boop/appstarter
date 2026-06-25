<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Plantillas PDF de resultados<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_config'), 'url' => site_url('config')],
    ['label' => 'Plantillas PDF', 'url' => ''],
]]) ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show"><?= esc(session()->getFlashdata('success')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show"><?= esc(session()->getFlashdata('error')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h3 class="mb-1">Plantillas del PDF de resultados</h3>
        <p class="text-muted mb-0">Cree y personalice el formato del informe que se descarga desde cada registro.</p>
    </div>
    <a href="<?= site_url('config') ?>?tab=sistema" class="btn btn-outline-secondary">Volver a configuración</a>
</div>

<div class="alert alert-light border mb-4">
    <div class="d-flex gap-3">
        <div class="text-primary fs-4"><i class="fa-solid fa-circle-info"></i></div>
        <div class="small">
            <strong>¿Cómo empezar?</strong>
            <ol class="mb-0 mt-1 ps-3">
                <li>Cree una plantilla con un nombre descriptivo (ej. «Formato con firmas por área»).</li>
                <li>Use <strong>Editar diseño</strong> para configurar encabezado, paciente, resultados y firmas.</li>
                <li>Active la plantilla en <strong>Configuración → Sistema</strong> para que se use al descargar PDF.</li>
            </ol>
        </div>
    </div>
</div>

<?php if (!empty($db_error)): ?>
<div class="alert alert-danger">
    <strong>No se pudo leer la tabla de plantillas.</strong> ¿Ejecutó las migraciones?
    <code class="d-block mt-2 p-2 bg-dark text-white rounded">php spark migrate</code>
    <small class="d-block mt-2 text-muted"><?= esc($db_error) ?></small>
</div>
<?php elseif (empty($templates)): ?>
<div class="alert alert-warning">
    No hay plantillas. Si el sistema está actualizado, ejecute <code>php spark migrate</code> en la raíz del proyecto y recargue.
</div>
<?php endif; ?>

<?php if (empty($db_error)): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fa-solid fa-plus me-1"></i> Nueva plantilla</h5>
    </div>
    <div class="card-body">
        <?= form_open(site_url('config/pdf-templates/create')) ?>
            <?= csrf_field() ?>
            <div class="row g-2 align-items-end">
                <div class="col-md-8">
                    <label class="form-label" for="new_tpl_name">Nombre de la plantilla</label>
                    <input type="text" class="form-control" id="new_tpl_name" name="name" required maxlength="120" placeholder="Ej. Formato estándar con logo y QR">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100"><i class="fa-solid fa-pen me-1"></i> Crear y configurar</button>
                </div>
            </div>
            <p class="small text-muted mb-0 mt-2">Se abrirá el diseñador visual para personalizar el PDF paso a paso.</p>
        <?= form_close() ?>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th class="text-center">Activa</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($templates)): ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">Sin plantillas. Cree una arriba o ejecute migraciones.</td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($templates as $t): ?>
                    <tr>
                        <td><?= esc($t->name ?? '') ?></td>
                        <td class="text-center">
                            <?php if ((int)($active_template_id ?? 0) === (int)($t->id ?? 0)): ?>
                                <span class="badge bg-success">Sí</span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="<?= site_url('config/pdf-templates/edit/' . (int)($t->id ?? 0)) ?>" class="btn btn-sm btn-primary"><i class="fa-solid fa-pen me-1"></i> Editar diseño</a>
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary btn-pdf-tpl-duplicate"
                                    data-id="<?= (int)($t->id ?? 0) ?>"
                                    data-name="<?= esc($t->name ?? '', 'attr') ?>"
                                    title="Duplicar plantilla">
                                <i class="fa-solid fa-copy"></i> Duplicar
                            </button>
                            <?php if (count($templates) > 1): ?>
                            <a href="<?= site_url('config/pdf-templates/delete/' . (int)($t->id ?? 0)) ?>"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('¿Eliminar esta plantilla?');">Eliminar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDuplicatePdfTemplate" tabindex="-1" aria-labelledby="modalDuplicatePdfTemplateTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <?= form_open('', ['id' => 'form_duplicate_pdf_template']) ?>
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDuplicatePdfTemplateTitle">Duplicar plantilla</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">
                        Se copiará el diseño completo de <strong id="duplicate_pdf_tpl_source_name"></strong>
                        (bloques, estilos, márgenes y marca de agua).
                    </p>
                    <label class="form-label" for="duplicate_pdf_tpl_name">Nombre de la nueva plantilla</label>
                    <input type="text" class="form-control" id="duplicate_pdf_tpl_name" name="name" required maxlength="120">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Duplicar</button>
                </div>
            <?= form_close() ?>
        </div>
    </div>
</div>

<script>
(function () {
    var modalEl = document.getElementById('modalDuplicatePdfTemplate');
    var formEl = document.getElementById('form_duplicate_pdf_template');
    var nameInput = document.getElementById('duplicate_pdf_tpl_name');
    var sourceNameEl = document.getElementById('duplicate_pdf_tpl_source_name');
    var modal = (modalEl && typeof bootstrap !== 'undefined')
        ? (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl))
        : null;

    document.querySelectorAll('.btn-pdf-tpl-duplicate').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-id') || '0';
            var sourceName = btn.getAttribute('data-name') || '';
            if (formEl) {
                formEl.action = <?= json_encode(site_url('config/pdf-templates/duplicate/')) ?> + id;
            }
            if (sourceNameEl) {
                sourceNameEl.textContent = sourceName;
            }
            if (nameInput) {
                nameInput.value = sourceName ? ('Copia de ' + sourceName) : '';
            }
            if (modal) {
                modal.show();
                setTimeout(function () {
                    if (nameInput) {
                        nameInput.focus();
                        nameInput.select();
                    }
                }, 200);
            } else if (formEl) {
                formEl.submit();
            }
        });
    });
})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
