<?php
/** @var list<object> $envelope_templates */
/** @var int $active_envelope_template_id */
/** @var int $print_envelope_template_id */
$templates = $envelope_templates ?? [];
$activeId  = (int) ($active_envelope_template_id ?? 0);
$printId   = (int) ($print_envelope_template_id ?? 0);
if ($printId < 1 && $templates !== []) {
    $printId = (int) ($templates[0]->id ?? 0);
}
?>
<div class="card shadow-sm">
    <div class="card-header bg-secondary text-white d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="mb-0"><i class="fa-solid fa-envelope me-2"></i>Plantillas de sobres</h5>
    </div>
    <div class="card-body">
        <?= view('config/partials/config_section_guide', [
            'guide_key' => 'sobres',
            'title' => 'Sobres para resultados impresos',
            'body' => 'Diseñe la plantilla del sobre que se imprime desde el reporte de cada orden (datos del paciente, QR, logo, etc.).',
            'steps' => [
                'Elija qué plantilla se usa al pulsar <strong>Imprimir sobre</strong> en el reporte.',
                'Cree o edite plantillas con el editor visual (misma lógica que PDF de resultados).',
                'Marque una como activa para abrirla por defecto en el editor.',
            ],
        ]) ?>
        <?php if (! empty($envelope_db_error)): ?>
        <div class="alert alert-danger">
            <strong>Falta la tabla de sobres en la base de datos.</strong>
            Ejecute en la carpeta del proyecto:
            <code class="d-block mt-2 p-2 bg-dark text-white rounded">php spark migrate</code>
            <span class="small text-muted d-block mt-2"><?= esc($envelope_db_error) ?></span>
        </div>
        <?php else: ?>
        <p class="text-muted small mb-3">
            Diseñe la distribución de datos en el sobre (paciente, QR, logo, etc.). Los campos disponibles son los mismos que en las plantillas PDF de resultados.
        </p>

        <?php if ($templates !== []): ?>
        <div class="card border-primary mb-4">
            <div class="card-header bg-primary text-white py-2">
                <strong class="small text-uppercase"><i class="fa-solid fa-print me-1"></i> Plantilla para imprimir sobres</strong>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">
                    Esta plantilla se usa al pulsar <strong>Imprimir sobre</strong> en la vista del reporte de cada orden.
                    Puede ser distinta de la plantilla marcada como «Activa» en el listado (solo referencia del editor).
                </p>
                <?= form_open(site_url('config/sobres/set-print-template'), ['class' => 'row g-2 align-items-end']) ?>
                    <div class="col-md-8">
                        <label class="form-label small mb-1" for="print_envelope_template_id">Plantilla de impresión</label>
                        <select class="form-select form-select-sm" name="print_envelope_template_id" id="print_envelope_template_id" required>
                            <?php foreach ($templates as $t):
                                $tid = (int) ($t->id ?? 0);
                                ?>
                            <option value="<?= $tid ?>" <?= $printId === $tid ? 'selected' : '' ?>><?= esc($t->name ?? ('Plantilla #' . $tid)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Guardar para impresión
                        </button>
                    </div>
                <?= form_close() ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <?= form_open(site_url('config/sobres/create'), ['class' => 'd-flex flex-wrap gap-2 align-items-end']) ?>
                    <div class="flex-grow-1">
                        <label class="form-label small mb-1" for="new_envelope_name">Nueva plantilla</label>
                        <input type="text" class="form-control form-control-sm" name="name" id="new_envelope_name" maxlength="120" placeholder="Ej. Sobre ventana DL" required>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-plus me-1"></i> Crear</button>
                <?= form_close() ?>
            </div>
        </div>

        <?php if ($templates === []): ?>
            <div class="alert alert-warning mb-0">
                No hay plantillas de sobre. Ejecute las migraciones de base de datos o cree una plantilla nueva.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Tamaño</th>
                            <th>Matriz</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($templates as $t):
                            $tid = (int) ($t->id ?? 0);
                            $layoutRaw = json_decode((string) ($t->layout_json ?? '{}'), true);
                            $sizeKey = is_array($layoutRaw) ? (string) ($layoutRaw['size_key'] ?? 'dl') : 'dl';
                            $sizes = \App\Services\EnvelopeLayoutService::envelopeSizes();
                            $sizeLabel = $sizes[$sizeKey]['label'] ?? $sizeKey;
                            $cols = is_array($layoutRaw) ? (int) ($layoutRaw['columns'] ?? 4) : 4;
                            $rows = is_array($layoutRaw) ? (int) ($layoutRaw['rows'] ?? 3) : 3;
                            $itemCount = is_array($layoutRaw) && isset($layoutRaw['items']) && is_array($layoutRaw['items'])
                                ? count($layoutRaw['items']) : 0;
                            $isActive = $activeId === $tid;
                            $isPrint  = $printId === $tid;
                            ?>
                        <tr class="<?= $isActive ? 'table-primary' : '' ?>">
                            <td>
                                <?= esc($t->name ?? '') ?>
                                <?php if ($isPrint): ?>
                                    <span class="badge bg-primary ms-1">Impresión</span>
                                <?php endif; ?>
                                <?php if ($isActive): ?>
                                    <span class="badge bg-success ms-1">Activa editor</span>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= esc($sizeLabel) ?></td>
                            <td class="small text-muted"><?= (int) $cols ?>×<?= (int) $rows ?> · <?= (int) $itemCount ?> campo(s)</td>
                            <td class="text-end text-nowrap">
                                <a href="<?= site_url('config/sobres/edit/' . $tid) ?>" class="btn btn-sm btn-primary">
                                    <i class="fa-solid fa-table-cells me-1"></i> Editar matriz
                                </a>
                                <?php if (! $isActive): ?>
                                    <a href="<?= site_url('config/sobres/set-active/' . $tid) ?>" class="btn btn-sm btn-outline-success">Activar</a>
                                <?php endif; ?>
                                <?php if (count($templates) > 1): ?>
                                    <a href="<?= site_url('config/sobres/delete/' . $tid) ?>"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('¿Eliminar esta plantilla de sobre?');">Eliminar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
