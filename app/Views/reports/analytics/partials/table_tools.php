<?php
/**
 * Buscador + tamaño de página para una tabla analítica.
 * Variables: $table_id (obligatoria), $placeholder (opcional)
 */
$tableId     = $table_id ?? 'an-table';
$placeholder = $placeholder ?? 'Buscar en la tabla...';
?>
<div class="an-tools d-print-none row g-2 mb-2 align-items-center" data-table="<?= esc($tableId, 'attr') ?>">
    <div class="col-sm-6 col-md-4">
        <input type="search" class="form-control an-search" placeholder="<?= esc($placeholder, 'attr') ?>" aria-label="Buscar">
    </div>
    <div class="col-auto">
        <select class="form-select an-pagesize" aria-label="Filas por página">
            <option value="10">10 filas</option>
            <option value="25" selected>25 filas</option>
            <option value="50">50 filas</option>
            <option value="100">100 filas</option>
            <option value="0">Todas</option>
        </select>
    </div>
</div>
