<?php
/**
 * Encabezado común: breadcrumb + acciones (imprimir, PDF, Excel).
 * Variables: $title, $subtitle, $pdf_url, $excel_url
 */
?>
<div class="d-print-none">
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_reports'), 'url' => site_url('reports')],
    ['label' => $title ?? '', 'url' => null],
]]) ?>
</div>

<div class="d-print-none mb-3 d-flex flex-wrap gap-2 align-items-center">
    <button type="button" class="btn btn-primary" onclick="window.print()" title="Imprimir">
        <i class="fas fa-print me-1"></i> Imprimir
    </button>
    <?php if (! empty($pdf_url)): ?>
        <a href="<?= esc($pdf_url) ?>" class="btn btn-danger" target="_blank" rel="noopener" title="Descargar PDF">
            <i class="fas fa-file-pdf me-1"></i> PDF
        </a>
    <?php endif; ?>
    <?php if (! empty($excel_url)): ?>
        <a href="<?= esc($excel_url) ?>" class="btn btn-success" title="Exportar a Excel (CSV)">
            <i class="fas fa-file-excel me-1"></i> Excel
        </a>
    <?php endif; ?>
</div>

<h4><?= esc($title ?? '') ?></h4>
<p class="text-muted"><?= esc($subtitle ?? '') ?></p>
