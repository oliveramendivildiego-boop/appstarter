<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Kardex de inventario<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="d-print-none">
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_reactivos'), 'url' => site_url('inventario')],
    ['label' => 'Kardex', 'url' => null],
]]) ?>
<?= view('reports/partials/report_actions', [
    'pdf_url' => $kardex_pdf_url ?? null,
]) ?>
</div>
<?= view('reactivos/partials/kardex_content', get_defined_vars()) ?>
<?= $this->endSection() ?>
