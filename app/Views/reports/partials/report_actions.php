<?php
$pdfUrl = $pdf_url ?? null;
$containerClass = $container_class ?? 'd-print-none mb-3 d-flex flex-wrap gap-2 align-items-center';
?>
<div class="<?= esc($containerClass) ?>">
    <button type="button" class="btn btn-primary" onclick="window.print()" title="Imprimir">
        <i class="fas fa-print me-1"></i> Imprimir
    </button>
    <?php if (! empty($pdfUrl)): ?>
        <a href="<?= esc($pdfUrl) ?>" class="btn btn-danger" target="_blank" rel="noopener" title="Descargar PDF">
            <i class="fas fa-file-pdf me-1"></i> PDF
        </a>
    <?php endif; ?>
</div>
<style>
@media print {
    a[href]:after { content: none !important; }
    .table { font-size: 9pt; }
}
</style>
