<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Orden de trabajo</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; }
        .row { width: 100%; }
        .muted { color: #666; }
        h1 { font-size: 16px; margin: 0 0 10px 0; }
        .meta { margin-bottom: 10px; }
        .meta div { margin: 2px 0; }
        .section { margin-top: 12px; }
        .section-title { font-weight: 700; text-transform: uppercase; border-bottom: 1px solid #ddd; padding-bottom: 4px; margin-bottom: 6px; }
        ul { margin: 0; padding-left: 18px; }
        li { margin: 2px 0; }
    </style>
</head>
<body>
    <h1>Orden de trabajo</h1>
    <div class="meta">
        <div><strong>Orden:</strong> <?= esc(registro_orden_display($register_info)) ?></div>
        <div><strong>Fecha:</strong> <?= esc($fecha ?? '') ?></div>
        <div><strong>Paciente:</strong> <?= esc(($register_info->first_name ?? '') . ' ' . ($register_info->last_name_fa ?? '') . ' ' . ($register_info->last_name_mom ?? '')) ?></div>
        <div><strong>Doctor:</strong> <?= esc($register_info->doctor_name ?? $register_info->doctor ?? '') ?></div>
    </div>

    <?php if (!empty($grupos_pruebas ?? [])): ?>
        <?php foreach (($grupos_pruebas ?? []) as $padre => $items): ?>
            <div class="section">
                <div class="section-title"><?= esc($padre) ?></div>
                <ul>
                    <?php foreach (($items ?? []) as $it): ?>
                        <li><?= esc($it['hijo'] ?? '') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="muted">No hay pruebas para esta orden.</p>
    <?php endif; ?>
</body>
</html>

