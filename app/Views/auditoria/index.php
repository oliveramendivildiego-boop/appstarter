<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?= view('partial/breadcrumb_nav', ['items' => [['label' => 'Auditoría', 'url' => site_url('auditoria')]]]) ?>

<?php
$totalReg = (int) ($total ?? 0);
$pageNum  = (int) ($page ?? 1);
$perPage  = (int) ($perPage ?? 30);
$desde    = $totalReg > 0 ? (($pageNum - 1) * $perPage) + 1 : 0;
$hasta    = min($pageNum * $perPage, $totalReg);
$f        = $filters ?? [];

$accionBadges = [
    'crear' => 'bg-success', 'crear_muestra' => 'bg-success',
    'actualizar' => 'bg-primary', 'editar_orden' => 'bg-primary',
    'eliminar' => 'bg-danger',
    'guardar_resultados' => 'bg-info', 'validar_tecnico' => 'bg-info', 'validar_medico' => 'bg-info',
    'agregar_pago' => 'bg-warning text-dark',
    'enviar_whatsapp' => 'bg-success',
    'cambiar_estado_muestra' => 'bg-secondary',
    'tipo_muestra_crear' => 'bg-success', 'tipo_muestra_actualizar' => 'bg-primary', 'tipo_muestra_eliminar' => 'bg-danger',
    'desactivar' => 'bg-danger', 'activar' => 'bg-success',
];
?>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="get" action="<?= site_url('auditoria') ?>" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-0">Módulo</label>
                <select name="modulo" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($modulos_list ?? [] as $m): ?>
                    <option value="<?= esc($m) ?>" <?= ($f['modulo'] ?? '') === $m ? 'selected' : '' ?>><?= esc(ucfirst($m)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Acción</label>
                <select name="accion" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    <?php foreach ($acciones_list ?? [] as $a): ?>
                    <option value="<?= esc($a) ?>" <?= ($f['accion'] ?? '') === $a ? 'selected' : '' ?>><?= esc(ucfirst(str_replace('_', ' ', $a))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Desde</label>
                <input type="date" name="fecha_desde" class="form-control form-control-sm" value="<?= esc($f['fecha_desde'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Hasta</label>
                <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="<?= esc($f['fecha_hasta'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Buscar</label>
                <input type="text" name="buscar" class="form-control form-control-sm" placeholder="Texto libre..." value="<?= esc($f['buscar'] ?? '') ?>">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-filter me-1"></i>Filtrar</button>
                <a href="<?= site_url('auditoria') ?>" class="btn btn-sm btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<p class="text-muted small">Mostrando <?= $desde ?>–<?= $hasta ?> de <?= $totalReg ?> registro(s)</p>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong><i class="fa-solid fa-clipboard-list me-2"></i>Registro de acciones</strong>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-striped table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width:150px">Fecha</th>
                        <th>Usuario</th>
                        <th>Módulo</th>
                        <th>Acción</th>
                        <th>Orden</th>
                        <th>IP</th>
                        <th style="width:40px" class="text-center">+</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registros ?? [] as $idx => $r): ?>
                    <?php
                    $datosRaw = trim((string)($r['datos'] ?? ''));
                    $datosArr = null;
                    if ($datosRaw !== '') {
                        $decoded = json_decode($datosRaw, true);
                        if (is_array($decoded)) $datosArr = $decoded;
                    }
                    $hasDatos = ($datosArr !== null && !empty($datosArr)) || ($datosRaw !== '' && $datosArr === null);
                    $accion = $r['accion'] ?? '';
                    $badgeClass = $accionBadges[$accion] ?? 'bg-secondary';
                    ?>
                    <tr>
                        <td><small><?= esc(lab_dt_short(isset($r['fecha']) ? (string) $r['fecha'] : null)) ?></small></td>
                        <td><?= esc(trim(($r['first_name'] ?? '') . ' ' . ($r['last_name_fa'] ?? '')) ?: '<em class="text-muted">Sistema</em>') ?></td>
                        <td><span class="badge bg-dark"><?= esc($r['modulo'] ?? '') ?></span></td>
                        <td><span class="badge <?= esc($badgeClass) ?>"><?= esc(ucfirst(str_replace('_', ' ', $accion))) ?></span></td>
                        <td>
                            <?php $rid = $r['registro_id'] ?? ''; ?>
                            <?php if ($rid !== '' && $rid !== null && ($r['modulo'] ?? '') === 'registers'): ?>
                                <a href="<?= site_url('registers/view/' . (int)$rid) ?>">#<?= esc($rid) ?></a>
                            <?php else: ?>
                                <?= esc($rid ?: '-') ?>
                            <?php endif; ?>
                        </td>
                        <td><small><?= esc($r['ip'] ?? '-') ?></small></td>
                        <td class="text-center">
                            <?php if ($hasDatos): ?>
                            <button class="btn btn-sm btn-outline-secondary py-0 px-1 btn-toggle-detail" data-target="detail-<?= $idx ?>" title="Ver detalles">
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($hasDatos): ?>
                    <tr id="detail-<?= $idx ?>" class="audit-detail-row" style="display:none;">
                        <td colspan="7" class="bg-light px-3 py-2">
                            <?php if ($datosArr !== null): ?>
                                <div class="row small">
                                <?php
                                $infoText = $datosArr['_info'] ?? null;
                                unset($datosArr['_info']);
                                ?>
                                <?php if ($infoText): ?>
                                    <div class="col-12 mb-1"><em class="text-primary"><?= esc($infoText) ?></em></div>
                                <?php endif; ?>
                                <?php foreach ($datosArr as $key => $val): ?>
                                    <div class="col-md-4 col-lg-3 mb-1">
                                        <strong><?= esc(ucfirst(str_replace('_', ' ', (string)$key))) ?>:</strong>
                                        <?php if (is_bool($val)): ?>
                                            <?= $val ? 'Sí' : 'No' ?>
                                        <?php elseif (is_array($val)): ?>
                                            <?= esc(json_encode($val, JSON_UNESCAPED_UNICODE)) ?>
                                        <?php else: ?>
                                            <?= esc((string)$val) ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <pre class="mb-0 small"><?= esc($datosRaw) ?></pre>
                            <?php endif; ?>
                            <?php $ua = trim((string)($r['user_agent'] ?? '')); ?>
                            <?php if ($ua !== ''): ?>
                                <div class="text-muted small mt-1"><i class="fa-solid fa-globe me-1"></i><?= esc(mb_strimwidth($ua, 0, 120, '...')) ?></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (empty($registros)): ?>
        <p class="text-muted p-3 mb-0">No hay registros de auditoría que coincidan con los filtros.</p>
        <?php endif; ?>
    </div>
</div>

<?php if (($totalPages ?? 1) > 1): ?>
<?php
$queryParams = array_filter($f, fn($v) => $v !== '');
?>
<nav class="mt-3">
    <ul class="pagination justify-content-center flex-wrap">
        <?php if ($pageNum > 1): ?>
        <li class="page-item"><a class="page-link" href="<?= site_url('auditoria') . '?' . http_build_query(array_merge($queryParams, ['page' => $pageNum - 1])) ?>">&laquo;</a></li>
        <?php endif; ?>
        <?php
        $start = max(1, $pageNum - 3);
        $end = min($totalPages, $pageNum + 3);
        ?>
        <?php if ($start > 1): ?>
        <li class="page-item"><a class="page-link" href="<?= site_url('auditoria') . '?' . http_build_query(array_merge($queryParams, ['page' => 1])) ?>">1</a></li>
        <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
        <?php endif; ?>
        <?php for ($i = $start; $i <= $end; $i++): ?>
        <li class="page-item <?= ($i === $pageNum) ? 'active' : '' ?>">
            <a class="page-link" href="<?= site_url('auditoria') . '?' . http_build_query(array_merge($queryParams, ['page' => $i])) ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
        <?php if ($end < $totalPages): ?>
        <?php if ($end < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
        <li class="page-item"><a class="page-link" href="<?= site_url('auditoria') . '?' . http_build_query(array_merge($queryParams, ['page' => $totalPages])) ?>"><?= $totalPages ?></a></li>
        <?php endif; ?>
        <?php if ($pageNum < $totalPages): ?>
        <li class="page-item"><a class="page-link" href="<?= site_url('auditoria') . '?' . http_build_query(array_merge($queryParams, ['page' => $pageNum + 1])) ?>">&raquo;</a></li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-toggle-detail').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var target = document.getElementById(this.getAttribute('data-target'));
            if (!target) return;
            var icon = this.querySelector('i');
            if (target.style.display === 'none') {
                target.style.display = '';
                if (icon) { icon.classList.remove('fa-chevron-down'); icon.classList.add('fa-chevron-up'); }
            } else {
                target.style.display = 'none';
                if (icon) { icon.classList.remove('fa-chevron-up'); icon.classList.add('fa-chevron-down'); }
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
