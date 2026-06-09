<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_labotests'), 'url' => site_url('labotests')],
    ['label' => lang('Labotests.labotests_recomendaciones_previas'), 'url' => null],
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
    <h4 class="mb-2">
        <i class="fa-solid fa-clipboard-list me-2 text-warning"></i>
        <?= lang('Labotests.labotests_recomendaciones_previas') ?>: <?= esc($labotests_info->name ?? '') ?>
    </h4>
    <p class="text-muted mb-4"><?= lang('Labotests.labotests_recomendaciones_previas_desc') ?></p>

    <?= form_open('labotests/saverecomendacionesprevias', ['id' => 'form_recomendaciones']) ?>
    <?= csrf_field() ?>
    <input type="hidden" name="prianacategoria_id" value="<?= (int) ($prianacategoria_id ?? 0) ?>">

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-warning text-dark">
            <strong><i class="fa-solid fa-vial me-1"></i> <?= lang('Labotests.labotests_recomendaciones_previas_contenido') ?></strong>
        </div>
        <div class="card-body">
            <textarea name="recomendaciones_previas" id="recomendaciones_content" class="form-control" rows="14"><?= $recomendaciones_previas ?? '' ?></textarea>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <small class="text-muted"><?= lang('Labotests.labotests_recomendaciones_previas_hint') ?></small>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk me-1"></i> <?= lang('Labotests.labotests_recomendaciones_previas_guardar') ?>
            </button>
        </div>
    </div>
    <?= form_close() ?>

    <?php if (! empty(trim(strip_tags($recomendaciones_previas ?? '')))): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <strong><i class="fa-solid fa-eye me-1"></i> <?= lang('Labotests.labotests_recomendaciones_previas_vista') ?></strong>
        </div>
        <div class="card-body recomendaciones-previas-preview">
            <?= $recomendaciones_previas ?? '' ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="mt-3">
        <a href="<?= site_url('labotests') ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left me-1"></i> <?= lang('Labotests.labotests_recomendaciones_previas_volver') ?></a>
        <a href="<?= site_url('labotests/manuales/' . ($prianacategoria_id ?? 0)) ?>" class="btn btn-outline-info"><i class="fa-solid fa-book me-1"></i> Manuales</a>
        <a href="<?= site_url('labotests/detail/' . ($labotests_info->prianacategoria_id ?? 0)) ?>" class="btn btn-primary"><i class="fa-solid fa-gear me-1"></i> <?= lang('Labotests.labotests_config') ?></a>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var contentEl = document.getElementById('recomendaciones_content');
    var form = document.getElementById('form_recomendaciones');

    if (typeof jQuery !== 'undefined' && jQuery().summernote && contentEl) {
        jQuery(contentEl).summernote({
            height: 320,
            toolbar: [
                ['style', ['bold', 'italic', 'underline', 'strikethrough']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link']],
                ['view', ['codeview']]
            ],
            placeholder: 'Ej: Ayuno de 8 horas, evitar medicamentos X, recolectar muestra en la mañana...'
        });
    }

    form?.addEventListener('submit', function() {
        if (jQuery(contentEl).length && jQuery(contentEl).data('summernote')) {
            contentEl.value = jQuery(contentEl).summernote('code');
        }
    });
});
</script>
<?= $this->endSection() ?>
