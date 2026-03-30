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

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Plantillas del PDF de resultados</h3>
    <a href="<?= site_url('config') ?>?tab=sistema" class="btn btn-outline-secondary">Volver a configuración</a>
</div>

<p class="text-muted">Defina el orden y qué secciones incluir en el PDF descargado desde el registro. La plantilla activa se elige en <strong>Configuración del sistema</strong>.</p>

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
        <h5 class="mb-0">Nueva plantilla</h5>
    </div>
    <div class="card-body">
        <?= form_open(site_url('config/pdf-templates/create')) ?>
            <?= csrf_field() ?>
            <div class="row g-2 align-items-end">
                <div class="col-md-8">
                    <label class="form-label" for="new_tpl_name">Nombre</label>
                    <input type="text" class="form-control" id="new_tpl_name" name="name" required maxlength="120" placeholder="Ej. Formato con notas arriba">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">Crear y editar</button>
                </div>
            </div>
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
                            <a href="<?= site_url('config/pdf-templates/edit/' . (int)($t->id ?? 0)) ?>" class="btn btn-sm btn-primary">Editar diseño</a>
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
<?php endif; ?>
<?= $this->endSection() ?>
