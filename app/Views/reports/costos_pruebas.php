<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
helper('layout');
$layoutCfg = layout_config();
$currencySym = $layoutCfg['currency_symbol'] ?? '$';
$currencySide = isset($layoutCfg['currency_side']) ? (string)$layoutCfg['currency_side'] : 'left';
$currencyIsRight = strtolower(trim($currencySide)) === 'right';
?>
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h3 class="mb-1"><?= esc($title) ?></h3>
            <p class="text-muted"><?= esc($subtitle) ?></p>
        </div>
        <div class="d-print-none d-flex flex-wrap gap-2 align-items-center">
            <?= view('reports/partials/report_actions', [
                'pdf_url' => site_url('reports/costosPruebasPdf?' . http_build_query(['busqueda' => $busqueda ?? ''])),
            ]) ?>
            <?php if (! empty($data)): ?>
                <a href="<?= site_url('reports/exportCostosPruebas') ?>?<?= ! empty($busqueda) ? 'busqueda=' . urlencode($busqueda) : '' ?>"
                   class="btn btn-success">
                    <i class="fas fa-file-excel me-1"></i> Exportar a Excel
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4 d-print-none">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Búsqueda de pruebas</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="<?= site_url('reports/costosPruebas') ?>">
            <div class="row g-3">
                <div class="col-md-8">
                    <input type="text" name="busqueda" class="form-control" 
                           placeholder="Buscar por categoría o nombre de prueba..." 
                           value="<?= esc($busqueda) ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Buscar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Costos de pruebas</h5>
        <span class="badge bg-light text-dark">
            <?= count($data) ?> pruebas encontradas
        </span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($data)): ?>
            <div class="p-4 text-center text-muted">
                <i class="fas fa-search fa-3x mb-3"></i>
                <p>No se encontraron pruebas con los criterios de búsqueda.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Categoría</th>
                            <th>Prueba</th>
                            <th class="text-end">Precio</th>
                            <th class="text-end">Precio Derivado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $categoriaActual = null;
                        $totalPrecio = 0;
                        $totalDerivado = 0;
                        ?>
                        <?php foreach ($data as $item): ?>
                            <?php if ($categoriaActual !== $item['categoria']): ?>
                                <?php if ($categoriaActual !== null): ?>
                                    <tr class="table-secondary fw-bold">
                                        <td colspan="2" class="text-end">Subtotal <?= esc($categoriaActual) ?>:</td>
                                        <td class="text-end"><?= $currencyIsRight ? (number_format($subtotalPrecio, 2) . ' ' . esc($currencySym)) : (esc($currencySym) . ' ' . number_format($subtotalPrecio, 2)) ?></td>
                                        <td class="text-end"><?= $currencyIsRight ? (number_format($subtotalDerivado, 2) . ' ' . esc($currencySym)) : (esc($currencySym) . ' ' . number_format($subtotalDerivado, 2)) ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php 
                                $categoriaActual = $item['categoria'];
                                $subtotalPrecio = 0;
                                $subtotalDerivado = 0;
                                ?>
                                <tr class="table-info">
                                    <td colspan="4" class="fw-bold text-primary">
                                        <i class="fas fa-folder me-2"></i><?= esc($item['categoria']) ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            
                            <?php 
                            $precio = (float) ($item['precio'] ?? 0);
                            $derivado = (float) ($item['precio_derivado'] ?? 0);
                            
                            $subtotalPrecio += $precio;
                            $subtotalDerivado += $derivado;
                            
                            $totalPrecio += $precio;
                            $totalDerivado += $derivado;
                            ?>
                            
                            <tr>
                                <td></td>
                                <td><?= esc($item['prueba']) ?></td>
                                <td class="text-end"><?= $currencyIsRight ? (number_format($precio, 2) . ' ' . esc($currencySym)) : (esc($currencySym) . ' ' . number_format($precio, 2)) ?></td>
                                <td class="text-end"><?= $currencyIsRight ? (number_format($derivado, 2) . ' ' . esc($currencySym)) : (esc($currencySym) . ' ' . number_format($derivado, 2)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <!-- Último subtotal -->
                        <?php if ($categoriaActual !== null): ?>
                            <tr class="table-secondary fw-bold">
                                <td colspan="2" class="text-end">Subtotal <?= esc($categoriaActual) ?>:</td>
                                <td class="text-end"><?= $currencyIsRight ? (number_format($subtotalPrecio, 2) . ' ' . esc($currencySym)) : (esc($currencySym) . ' ' . number_format($subtotalPrecio, 2)) ?></td>
                                <td class="text-end"><?= $currencyIsRight ? (number_format($subtotalDerivado, 2) . ' ' . esc($currencySym)) : (esc($currencySym) . ' ' . number_format($subtotalDerivado, 2)) ?></td>
                            </tr>
                        <?php endif; ?>
                        
                        <!-- Total general -->
                        <tr class="table-dark fw-bold fs-6">
                            <td colspan="2" class="text-end">TOTAL GENERAL:</td>
                            <td class="text-end"><?= $currencyIsRight ? (number_format($totalPrecio, 2) . ' ' . esc($currencySym)) : (esc($currencySym) . ' ' . number_format($totalPrecio, 2)) ?></td>
                            <td class="text-end"><?= $currencyIsRight ? (number_format($totalDerivado, 2) . ' ' . esc($currencySym)) : (esc($currencySym) . ' ' . number_format($totalDerivado, 2)) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="mt-3 d-print-none">
    <a href="<?= site_url('reports') ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> Volver a reportes
    </a>
</div>

<style>
@media print {
    .btn, .card-header, .breadcrumb {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .table {
        font-size: 12px;
    }
}
</style>
<?= $this->endSection() ?>
