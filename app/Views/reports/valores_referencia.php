<?php
$poblacionLabels = $poblacion_labels ?? [];
$pobLabel       = static function ($id) use ($poblacionLabels): string {
    if ($id === null || $id === '') {
        return '—';
    }
    $i = (int) $id;
    if ($i === 0) {
        return '—';
    }

    return $poblacionLabels[$i] ?? (string) $i;
};

function getSexoLabel($sexo) {
    if ($sexo === 'masculino') return 'Masculino';
    if ($sexo === 'femenino') return 'Femenino';
    return 'Ambos';
}
?>

<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h3 class="mb-1"><?= esc($title) ?></h3>
            <p class="text-muted"><?= esc($subtitle) ?></p>
        </div>
        <div class="d-print-none d-flex flex-wrap gap-2 align-items-center">
            <?= view('reports/partials/report_actions', [
                'pdf_url' => site_url('reports/valoresReferenciaPdf?' . http_build_query(['busqueda' => $busqueda ?? ''])),
            ]) ?>
            <?php if (! empty($data)): ?>
                <a href="<?= site_url('reports/exportValoresReferencia') ?>?<?= ! empty($busqueda) ? 'busqueda=' . urlencode($busqueda) : '' ?>"
                   class="btn btn-success">
                    <i class="fas fa-file-excel me-1"></i> Exportar a Excel
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Búsqueda de pruebas</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="<?= site_url('reports/valoresReferencia') ?>">
            <div class="row g-3">
                <div class="col-md-8">
                    <input type="text" name="busqueda" class="form-control" 
                           placeholder="Buscar por categoría, prueba o análisis..." 
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
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Valores de referencia</h5>
        <span class="badge bg-light text-dark">
            <?= count($data) ?> análisis encontrados
        </span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($data)): ?>
            <div class="p-4 text-center text-muted">
                <i class="fas fa-search fa-3x mb-3"></i>
                <p>No se encontraron análisis con los criterios de búsqueda.</p>
            </div>
        <?php else: ?>
            <?php 
            $categoriaActual = null;
            $pruebaActual = null;
            $categoriaIndex = 0;
            ?>
            <?php foreach ($data as $item): ?>
                <?php if ($categoriaActual !== $item['categoria']): ?>
                    <?php if ($categoriaActual !== null): ?>
                        </tbody>
                    </table>
                    <hr class="my-4">
                    <?php endif; ?>
                    <?php 
                    $categoriaActual = $item['categoria'];
                    $pruebaActual = null;
                    $categoriaIndex++;
                    ?>
                    <div class="mb-4">
                        <div class="text-center mb-4">
                            <h3 class="category-title">
                                <i class="fas fa-folder-open me-2"></i><?= esc($item['categoria']) ?>
                            </h3>
                        </div>
                        <table class="table table-hover table-striped mb-0" id="tabla-categoria-<?= $categoriaIndex ?>">
                            <tbody>
                <?php endif; ?>
                
                <?php if ($pruebaActual !== $item['prueba'] && !empty($item['prueba'])): ?>
                    <?php if ($pruebaActual !== null): ?>
                        </tbody>
                    <?php endif; ?>
                    <?php $pruebaActual = $item['prueba']; ?>
                    <?php $hasValues = !empty($item['valor_min']) || !empty($item['valor_max']); ?>
                    <tr class="table-primary <?= $hasValues ? 'subcategoria-header' : '' ?>" <?= $hasValues ? 'data-prueba="' . esc($item['prueba']) . '"' : '' ?>>
                        <td colspan="6" class="fw-bold <?= $hasValues ? 'cursor-pointer' : '' ?>">
                            <?php if ($hasValues): ?>
                                <i class="fas fa-vial me-2"></i>
                            <?php else: ?>
                                <i class="fas fa-vial me-2" style="opacity: 0.5;"></i>
                            <?php endif; ?>
                            <?= esc($item['prueba']) ?>
                            <?php if (!$hasValues): ?>
                                <span class="ms-2 text-muted">(sin valores configurados)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($hasValues): ?>
                    <tr class="table-white">
                        <th>Análisis</th>
                        <th class="text-center">Población</th>
                        <th class="text-center">Sexo</th>
                        <th class="text-center">Valor Mínimo</th>
                        <th class="text-center">Valor Máximo</th>
                        <th class="text-center">Unidad</th>
                    </tr>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if (!empty($item['valor_min']) || !empty($item['valor_max'])): ?>
                <tr>
                    <td><?= esc($item['analisis'] ?? 'N/A') ?></td>
                    <td class="text-center">
                        <span class="badge bg-info">
                            <?= esc($pobLabel($item['poblacion'] ?? null)) ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-secondary">
                            <?= getSexoLabel($item['sexo'] ?? 'ambos') ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <?php if (!empty($item['valor_min'])): ?>
                            <span class="badge bg-info"><?= esc($item['valor_min']) ?></span>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if (!empty($item['valor_max'])): ?>
                            <span class="badge bg-info"><?= esc($item['valor_max']) ?></span>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if (!empty($item['umedida'])): ?>
                            <span class="badge bg-secondary"><?= esc($item['umedida']) ?></span>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endif; ?>
            <?php endforeach; ?>
            
            <?php if ($categoriaActual !== null): ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <!-- Resumen estadístico -->
            <div class="card-footer bg-light">
                <div class="row text-center">
                    <div class="col-md-3">
                        <strong>Categorías:</strong><br>
                        <span class="badge bg-primary"><?= count(array_unique(array_column($data, 'categoria'))) ?></span>
                    </div>
                    <div class="col-md-3">
                        <strong>Pruebas:</strong><br>
                        <span class="badge bg-success"><?= count(array_unique(array_filter(array_column($data, 'prueba')))) ?></span>
                    </div>
                    <div class="col-md-3">
                        <strong>Análisis totales:</strong><br>
                        <span class="badge bg-info"><?= count($data) ?></span>
                    </div>
                    <div class="col-md-3">
                        <strong>Con rango definido:</strong><br>
                        <span class="badge bg-warning">
                            <?= count(array_filter($data, fn($item) => !empty($item['valor_min']) && !empty($item['valor_max']))) ?>
                        </span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="mt-3 d-print-none">
    <a href="<?= site_url('reports') ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> Volver a reportes
    </a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Convertir pruebas (subcategoria-header) en acordeones
    const pruebas = document.querySelectorAll('.subcategoria-header');
    
    pruebas.forEach(function(prueba) {
        prueba.style.cursor = 'pointer';
        prueba.addEventListener('click', function(e) {
            e.stopPropagation();
            const pruebaNombre = this.dataset.prueba;
            togglePrueba(this);
        });
    });
    
    function togglePrueba(pruebaHeader) {
        let isVisible = pruebaHeader.dataset.expanded !== 'false';
        
        // Encontrar todas las filas hasta el siguiente header o fin de tabla
        let nextElement = pruebaHeader.nextElementSibling;
        let filasOcultar = [];
        
        // Recolectar todas las filas que pertenecen a esta prueba
        while (nextElement) {
            // Detenerse si encontramos otra prueba
            if (nextElement.classList.contains('subcategoria-header')) {
                break;
            }
            
            // Agregar a la lista de filas a ocultar/mostrar
            filasOcultar.push(nextElement);
            nextElement = nextElement.nextElementSibling;
            
            // Seguridad para evitar bucles infinitos
            if (filasOcultar.length > 100) {
                break;
            }
        }
        
        // Ocultar o mostrar todas las filas
        filasOcultar.forEach(function(fila) {
            if (isVisible) {
                fila.style.display = 'none';
            } else {
                fila.style.display = '';
            }
        });
        
        // Cambiar el icono
        const icon = pruebaHeader.querySelector('i');
        if (icon) {
            if (isVisible) {
                icon.classList.remove('fa-vial');
                icon.classList.add('fa-chevron-down');
            } else {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-vial');
            }
        }
        
        // Alternar el estado
        pruebaHeader.dataset.expanded = isVisible ? 'false' : 'true';
    }
    
    // Inicializar todas las pruebas como contraídas excepto la primera
    const allPruebas = document.querySelectorAll('.subcategoria-header');
    allPruebas.forEach(function(prueba, index) {
        if (index === 0) {
            // Primera prueba expandida
            prueba.dataset.expanded = 'true';
        } else {
            // Resto contraídas
            prueba.dataset.expanded = 'false';
            
            // Ocultar las filas de las pruebas contraídas
            let nextElement = prueba.nextElementSibling;
            let filasOcultar = [];
            
            while (nextElement) {
                if (nextElement.classList.contains('subcategoria-header')) break;
                filasOcultar.push(nextElement);
                nextElement = nextElement.nextElementSibling;
                if (filasOcultar.length > 100) break;
            }
            
            // Ocultar las filas
            filasOcultar.forEach(function(fila) {
                fila.style.display = 'none';
            });
            
            // Cambiar icono a contraído
            const icon = prueba.querySelector('i');
            if (icon) {
                icon.classList.remove('fa-vial');
                icon.classList.add('fa-chevron-down');
            }
        }
    });
});
</script>

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
    /* Mostrar todo al imprimir */
    .table tbody tr {
        display: table-row !important;
    }
}

/* Corregir el ancho de las filas resaltadas */
.table-info {
    background-color: #e3f2fd !important;
    cursor: pointer !important;
    transition: background-color 0.2s ease;
}

.table-primary {
    background-color: #e8f5e8 !important;
    cursor: pointer !important;
    transition: background-color 0.2s ease;
}

.table-primary:hover {
    background-color: #d1e7dd !important;
}

.table-primary td {
    user-select: none;
}

.table-light {
    background-color: #f8f9fa !important;
}

.table-secondary {
    background-color: #e9ecef !important;
}

/* Mejorar contraste para números */
.table .badge {
    font-weight: 600;
    background-color: #ffffff !important;
    color: #212529 !important;
    border: 1px solid #dee2e6 !important;
}

.table-info .badge {
    background-color: #ffffff !important;
    color: #1976d2 !important;
    border: 1px solid #1976d2 !important;
}

.table-primary .badge {
    background-color: #ffffff !important;
    color: #2e7d32 !important;
    border: 1px solid #2e7d32 !important;
}

.table-secondary .badge {
    background-color: #ffffff !important;
    color: #6c757d !important;
    border: 1px solid #6c757d !important;
}

/* Aumentar tamaño de cabeceras */
.table-white {
    background-color: #ffffff !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    border-bottom: 2px solid #dee2e6 !important;
}

.table-white th {
    background-color: #ffffff !important;
    color: #212529 !important;
    padding: 12px 8px !important;
    vertical-align: middle !important;
    border-bottom: 2px solid #dee2e6 !important;
}

/* Mejorar contraste en filas grises */
.table-secondary td {
    color: #212529 !important;
    font-weight: 500 !important;
}

/* Asegurar que las filas resaltadas ocupen todo el ancho */
.table tr td,
.table tr th {
    border-left: 1px solid #dee2e6;
    border-right: 1px solid #dee2e6;
}

.table-info td,
.table-primary td,
.table-light td,
.table-secondary td {
    border-left: 1px solid #dee2e6;
    border-right: 1px solid #dee2e6;
}

/* Quitar bordes dobles en filas resaltadas */
.table-info td:first-child,
.table-primary td:first-child,
.table-light td:first-child,
.table-secondary td:first-child {
    border-left: none;
}

.table-info td:last-child,
.table-primary td:last-child,
.table-light td:last-child,
.table-secondary td:last-child {
    border-right: none;
}

/* Estilos para títulos de categoría */
.category-title {
    color: #2c3e50;
    font-weight: 700;
    font-size: 1.75rem;
    margin: 0;
    padding: 15px 0;
    border-bottom: 3px solid #3498db;
    display: inline-block;
    position: relative;
    text-transform: uppercase;
    letter-spacing: 1px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
}

.category-title i {
    color: #3498db;
    -webkit-text-fill-color: initial;
    text-shadow: none;
}

.category-title::after {
    content: '';
    position: absolute;
    bottom: -3px;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #3498db, #e74c3c, #f39c12);
    border-radius: 2px;
}

.category-title:hover {
    transform: translateY(-2px);
    transition: all 0.3s ease;
}

.category-title:hover i {
    color: #e74c3c;
    transition: color 0.3s ease;
}

/* Estilos para acordeón */
.table-info:hover {
    background-color: #d1e7dd !important;
}

.table-info td {
    user-select: none;
}

/* Animación suave para expandir/contraer */
.table tbody tr {
    transition: all 0.3s ease;
}

/* Estilo para categorías colapsadas */
.table-info[data-expanded="false"] {
    background-color: #f8f9fa !important;
}

.table-info[data-expanded="false"]:hover {
    background-color: #e9ecef !important;
}
</style>
<?= $this->endSection() ?>
