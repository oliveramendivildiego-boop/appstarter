<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Kardex de inventario<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_reports'), 'url' => site_url('reports')],
    ['label' => 'Kardex de inventario', 'url' => null],
]]) ?>
<?= view('reactivos/partials/kardex_content', get_defined_vars()) ?>
<?= $this->endSection() ?>
