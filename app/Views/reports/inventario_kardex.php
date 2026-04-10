<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Kardex de inventario<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="d-print-none">
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_reports'), 'url' => site_url('reports')],
    ['label' => 'Kardex de inventario', 'url' => null],
]]) ?>
<?php if (! empty($kardex_pdf_url)): ?>
    <?= view('reports/partials/report_actions', ['pdf_url' => $kardex_pdf_url]) ?>
<?php endif; ?>
</div>
<?= view('reactivos/partials/kardex_content', get_defined_vars()) ?>
<?= $this->endSection() ?>
