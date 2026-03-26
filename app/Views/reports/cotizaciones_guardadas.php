<?= $this->extend('layouts/main') ?>

<?= $this->section('head_extra') ?>
<style>
.acciones-cotizacion .btn { font-size: 0.75rem; min-width: 1.75rem; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', ['items' => [
    ['label' => lang('Module.module_reports'), 'url' => site_url('reports')],
    ['label' => isset($title) ? $title : '', 'url' => null],
]]) ?>
<?php
helper('layout');
$layoutCfg = layout_config();
$currencySym = (isset($layoutCfg['currency_symbol']) && (string)$layoutCfg['currency_symbol'] !== '') ? $layoutCfg['currency_symbol'] : '$';
$currencySide = isset($layoutCfg['currency_side']) ? (string)$layoutCfg['currency_side'] : 'left';
$currencyIsRight = strtolower(trim($currencySide)) === 'right';
?>

<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <?= esc(session()->getFlashdata('error')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<h4><?= esc(isset($title) ? $title : '') ?></h4>
<p class="text-muted"><?= esc(isset($subtitle) ? $subtitle : '') ?></p>

<?php
$desde = $total > 0 ? (($page - 1) * $perPage) + 1 : 0;
$hasta = min($page * $perPage, $total);
?>
<p class="small text-muted">Mostrando <?= $desde ?>–<?= $hasta ?> de <?= (int) $total ?> cotización(es)</p>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th style="width:5%">#</th>
                <th>Fecha</th>
                <th>Usuario que cotizó</th>
                <th>Exámenes cotizados</th>
                <th class="text-end">Costo ref.</th>
                <th class="text-end">Total</th>
                <th style="width:10%">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ((isset($data) && is_array($data)) ? $data : [] as $row):
                $id = (int)(isset($row['toquotelogs_id']) ? $row['toquotelogs_id'] : 0);
                $itemsJson = isset($row['items_json']) ? $row['items_json'] : null;
                $items = $itemsJson ? json_decode($itemsJson, true) : [];
                $tieneItems = is_array($items) && !empty($items);
                $examenes = $tieneItems ? implode(', ', array_column($items, 'name')) : (isset($row['cotizo']) ? $row['cotizo'] : '-');
                $examenesResumen = mb_strlen($examenes) > 100 ? mb_substr($examenes, 0, 100) . '…' : $examenes;
            ?>
            <tr>
                <td><?= $id ?></td>
                <td><?= esc(date('d/m/Y H:i', strtotime(isset($row['fecha']) ? $row['fecha'] : ''))) ?></td>
                <td><?= esc(isset($row['usuario_cotizo']) ? $row['usuario_cotizo'] : '-') ?></td>
                <td>
                    <span class="cotizo-resumen" title="<?= esc($examenes) ?>"><?= esc($examenesResumen) ?></span>
                </td>
                <td class="text-end"><?= $currencyIsRight
                    ? (number_format((int)(isset($row['refe']) ? $row['refe'] : 0)) . ' ' . esc($currencySym))
                    : (esc($currencySym) . ' ' . number_format((int)(isset($row['refe']) ? $row['refe'] : 0))) ?></td>
                <td class="text-end"><?= $currencyIsRight
                    ? (number_format((int)(isset($row['costo']) ? $row['costo'] : 0)) . ' ' . esc($currencySym))
                    : (esc($currencySym) . ' ' . number_format((int)(isset($row['costo']) ? $row['costo'] : 0))) ?></td>
                <td class="text-nowrap">
                    <div class="d-flex flex-nowrap gap-1 align-items-center acciones-cotizacion">
                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-1 ver-detalle" data-id="<?= $id ?>" data-items="<?= esc(htmlspecialchars(isset($itemsJson) ? $itemsJson : '[]', ENT_QUOTES, 'UTF-8')) ?>" title="Ver detalle">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                        <?php if ($tieneItems): ?>
                        <a href="<?= site_url('toquotes/exportPdfById/' . $id) ?>" class="btn btn-outline-danger btn-sm py-0 px-1" target="_blank" title="PDF ambos">
                            <i class="fa-solid fa-file-pdf"></i>
                        </a>
                        <a href="<?= site_url('toquotes/exportPdfById/' . $id . '/refe') ?>" class="btn btn-outline-info btn-sm py-0 px-1" target="_blank" title="PDF solo ref.">
                            <i class="fa-solid fa-file-pdf"></i>
                        </a>
                        <a href="<?= site_url('toquotes/exportPdfById/' . $id . '/total') ?>" class="btn btn-outline-success btn-sm py-0 px-1" target="_blank" title="PDF solo total">
                            <i class="fa-solid fa-file-pdf"></i>
                        </a>
                        <?php else: ?>
                        <span class="badge bg-secondary" title="Sin detalle">-</span>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <tr class="detalle-row" id="detalle-<?= $id ?>" style="display:none;">
                <td colspan="7" class="bg-light py-3">
                    <strong>Detalle de la cotización:</strong>
                    <table class="table table-sm table-bordered mt-2 mb-0">
                        <thead><tr><th>#</th><th>Análisis</th><th class="text-end">Costo</th><th class="text-end">Ref.</th></tr></thead>
                        <tbody class="detalle-tbody" data-id="<?= $id ?>">
                            <?php if ($tieneItems): foreach ($items as $i => $it): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= esc(isset($it['name']) ? $it['name'] : '') ?></td>
                                <td class="text-end"><?= $currencyIsRight
                                    ? (number_format((int)(isset($it['cost']) ? $it['cost'] : 0)) . ' ' . esc($currencySym))
                                    : (esc($currencySym) . ' ' . number_format((int)(isset($it['cost']) ? $it['cost'] : 0))) ?></td>
                                <td class="text-end"><?= $currencyIsRight
                                    ? (number_format((int)(isset($it['refe']) ? $it['refe'] : 0)) . ' ' . esc($currencySym))
                                    : (esc($currencySym) . ' ' . number_format((int)(isset($it['refe']) ? $it['refe'] : 0))) ?></td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="4" class="text-muted">No hay detalle disponible (cotización antigua)</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (empty($data)): ?>
<p class="text-muted">No hay cotizaciones guardadas.</p>
<?php else: ?>
<nav aria-label="Paginación" class="mt-3">
    <ul class="pagination pagination-sm">
        <?php if ($page > 1): ?>
        <li class="page-item"><a class="page-link" href="<?= site_url('reports/cotizacionesGuardadas?page=' . ($page - 1)) ?>">Anterior</a></li>
        <?php endif; ?>
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= site_url('reports/cotizacionesGuardadas?page=' . $i) ?>"><?= $i ?></a></li>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
        <li class="page-item"><a class="page-link" href="<?= site_url('reports/cotizacionesGuardadas?page=' . ($page + 1)) ?>">Siguiente</a></li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.querySelectorAll('.ver-detalle').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = btn.dataset.id;
        var row = document.getElementById('detalle-' + id);
        if (row) {
            row.style.display = row.style.display === 'none' ? '' : 'none';
        }
    });
});
</script>
<?= $this->endSection() ?>
