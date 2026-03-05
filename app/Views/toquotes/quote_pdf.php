<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización - <?= esc($lab_config['company'] ?? 'Laboratorio') ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; padding: 20px; }
        .header { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #333; }
        .header h1 { font-size: 18pt; margin-bottom: 5px; }
        .header p { font-size: 9pt; color: #555; }
        h2 { font-size: 14pt; margin: 20px 0 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px 10px; text-align: left; }
        th { background: #f0f0f0; font-weight: bold; }
        .text-right { text-align: right; }
        .totales { margin-top: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; }
        .totales .row { display: flex; justify-content: space-between; margin: 5px 0; font-size: 12pt; }
        .totales .row strong { font-size: 13pt; }
        .fecha { margin-top: 25px; font-size: 9pt; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1><?= esc($lab_config['company'] ?? 'Laboratorio') ?></h1>
        <?php if (!empty($lab_config['address'])): ?>
            <p><?= esc($lab_config['address']) ?></p>
        <?php endif; ?>
        <?php if (!empty($lab_config['phone'])): ?>
            <p>Tel: <?= esc($lab_config['phone']) ?></p>
        <?php endif; ?>
        <?php if (!empty($lab_config['email'])): ?>
            <p>Email: <?= esc($lab_config['email']) ?></p>
        <?php endif; ?>
    </div>

    <?php
    $mostrarRefe = ($precioTipo ?? null) === 'total' ? false : true;
    $mostrarCost = ($precioTipo ?? null) === 'refe' ? false : true;
    ?>
    <h2>COTIZACIÓN DE ANÁLISIS CLÍNICOS</h2>

    <table>
        <thead>
            <tr>
                <th style="width:5%">#</th>
                <th>Análisis</th>
                <?php if ($mostrarCost): ?><th class="text-right" style="width:18%">Costo (Bs)</th><?php endif; ?>
                <?php if ($mostrarRefe): ?><th class="text-right" style="width:18%">Ref. (Bs)</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php $n = 1; foreach ($items as $it): ?>
            <tr>
                <td><?= $n++ ?></td>
                <td><?= esc($it['name'] ?? '') ?></td>
                <?php if ($mostrarCost): ?><td class="text-right"><?= number_format((int)($it['cost'] ?? 0)) ?></td><?php endif; ?>
                <?php if ($mostrarRefe): ?><td class="text-right"><?= number_format((int)($it['refe'] ?? 0)) ?></td><?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totales">
        <?php if ($mostrarCost): ?>
        <div class="row">
            <span>Costo total:</span>
            <strong><?= number_format($totalCost) ?> Bs</strong>
        </div>
        <?php endif; ?>
        <?php if ($mostrarRefe): ?>
        <div class="row">
            <span>Costo referencia:</span>
            <strong><?= number_format($totalRefe) ?> Bs</strong>
        </div>
        <?php endif; ?>
    </div>

    <p class="fecha">Generado el <?= esc($fecha) ?></p>
</body>
</html>
