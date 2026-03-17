<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h3 class="mb-1"><?= esc($title) ?></h3>
            <p class="text-muted"><?= esc($subtitle) ?></p>
        </div>
        <?php if (!empty($data)): ?>
            <a href="<?= site_url('reports/exportCostosPruebas') ?>?<?= !empty($busqueda) ? 'busqueda=' . urlencode($busqueda) : '' ?>" 
               class="btn btn-success">
                <i class="fas fa-file-excel me-1"></i> Exportar a Excel
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm mb-4">
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
                            <th class="text-center">Diferencia</th>
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
                                        <td class="text-end">$<?= number_format($subtotalPrecio, 2) ?></td>
                                        <td class="text-end">$<?= number_format($subtotalDerivado, 2) ?></td>
                                        <td class="text-center">$<?= number_format($subtotalDiferencia, 2) ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php 
                                $categoriaActual = $item['categoria'];
                                $subtotalPrecio = 0;
                                $subtotalDerivado = 0;
                                $subtotalDiferencia = 0;
                                ?>
                                <tr class="table-info">
                                    <td colspan="5" class="fw-bold text-primary">
                                        <i class="fas fa-folder me-2"></i><?= esc($item['categoria']) ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            
                            <?php 
                            $precio = (float) ($item['precio'] ?? 0);
                            $derivado = (float) ($item['precio_derivado'] ?? 0);
                            $diferencia = $derivado - $precio;
                            
                            $subtotalPrecio += $precio;
                            $subtotalDerivado += $derivado;
                            $subtotalDiferencia += $diferencia;
                            
                            $totalPrecio += $precio;
                            $totalDerivado += $derivado;
                            ?>
                            
                            <tr>
                                <td></td>
                                <td><?= esc($item['prueba']) ?></td>
                                <td class="text-end">$<?= number_format($precio, 2) ?></td>
                                <td class="text-end">$<?= number_format($derivado, 2) ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $diferencia > 0 ? 'bg-success' : ($diferencia < 0 ? 'bg-danger' : 'bg-secondary') ?>">
                                        $<?= number_format($diferencia, 2) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <!-- Último subtotal -->
                        <?php if ($categoriaActual !== null): ?>
                            <tr class="table-secondary fw-bold">
                                <td colspan="2" class="text-end">Subtotal <?= esc($categoriaActual) ?>:</td>
                                <td class="text-end">$<?= number_format($subtotalPrecio, 2) ?></td>
                                <td class="text-end">$<?= number_format($subtotalDerivado, 2) ?></td>
                                <td class="text-center">$<?= number_format($subtotalDiferencia, 2) ?></td>
                            </tr>
                        <?php endif; ?>
                        
                        <!-- Total general -->
                        <tr class="table-dark fw-bold fs-6">
                            <td colspan="2" class="text-end">TOTAL GENERAL:</td>
                            <td class="text-end">$<?= number_format($totalPrecio, 2) ?></td>
                            <td class="text-end">$<?= number_format($totalDerivado, 2) ?></td>
                            <td class="text-center">$<?= number_format($totalDerivado - $totalPrecio, 2) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="mt-3">
    <a href="<?= site_url('reports') ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> Volver a reportes
    </a>
    <?php if (!empty($data)): ?>
        <a href="<?= site_url('reports/exportCostosPruebas') ?>?<?= !empty($busqueda) ? 'busqueda=' . urlencode($busqueda) : '' ?>" 
           class="btn btn-success">
            <i class="fas fa-file-excel me-1"></i> Exportar a Excel
        </a>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
