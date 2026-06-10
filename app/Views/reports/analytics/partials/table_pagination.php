<?php
/**
 * Contenedor de paginación de la tabla analítica.
 * Variables: $table_id (obligatoria)
 */
$tableId = $table_id ?? 'an-table';
?>
<div class="an-pagination d-print-none d-flex flex-wrap justify-content-between align-items-center mb-3" data-table="<?= esc($tableId, 'attr') ?>">
    <span class="an-info small text-muted"></span>
    <ul class="an-nav pagination pagination-sm mb-0"></ul>
</div>
