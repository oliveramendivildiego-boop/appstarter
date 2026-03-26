<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización - <?= esc(isset($lab_config['company']) ? $lab_config['company'] : 'Laboratorio') ?></title>
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
    <?php
    helper('layout');
    $layoutCfg = layout_config();
    $currencySym = (isset($layoutCfg['currency_symbol']) && (string)$layoutCfg['currency_symbol'] !== '') ? $layoutCfg['currency_symbol'] : '$';
    $currencySide = isset($layoutCfg['currency_side']) ? (string)$layoutCfg['currency_side'] : 'left';
    $currencyIsRight = strtolower(trim($currencySide)) === 'right';
    ?>
    <div class="header">
        <h1><?= esc(isset($lab_config['company']) ? $lab_config['company'] : 'Laboratorio') ?></h1>
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
    $precioTipoVal = isset($precioTipo) ? $precioTipo : null;
    $mostrarRefe = ($precioTipoVal === 'total') ? false : true;
    $mostrarCost = ($precioTipoVal === 'refe') ? false : true;
    ?>
    <h2>COTIZACIÓN DE ANÁLISIS CLÍNICOS</h2>

    <table>
        <thead>
            <tr>
                <th style="width:5%">#</th>
                <th>Análisis</th>
                <?php if ($mostrarCost): ?><th class="text-right" style="width:18%">Costo (<?= esc($currencySym) ?>)</th><?php endif; ?>
                <?php if ($mostrarRefe): ?><th class="text-right" style="width:18%">Ref. (<?= esc($currencySym) ?>)</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php $n = 1; foreach ($items as $it): ?>
            <tr>
                <td><?= $n++ ?></td>
                <td><?= esc(isset($it['name']) ? $it['name'] : '') ?></td>
                <?php if ($mostrarCost): ?><td class="text-right"><?= number_format((int)(isset($it['cost']) ? $it['cost'] : 0)) ?></td><?php endif; ?>
                <?php if ($mostrarRefe): ?><td class="text-right"><?= number_format((int)(isset($it['refe']) ? $it['refe'] : 0)) ?></td><?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totales">
        <?php if ($mostrarCost): ?>
        <div class="row">
            <span>Costo total:</span>
            <strong>
                <?php if ($currencyIsRight): ?>
                    <?= number_format($totalCost) ?> <?= esc($currencySym) ?>
                <?php else: ?>
                    <?= esc($currencySym) ?> <?= number_format($totalCost) ?>
                <?php endif; ?>
            </strong>
        </div>
        <?php endif; ?>
        <?php if ($mostrarRefe): ?>
        <div class="row">
            <span>Costo referencia:</span>
            <strong>
                <?php if ($currencyIsRight): ?>
                    <?= number_format($totalRefe) ?> <?= esc($currencySym) ?>
                <?php else: ?>
                    <?= esc($currencySym) ?> <?= number_format($totalRefe) ?>
                <?php endif; ?>
            </strong>
        </div>
        <?php endif; ?>
    </div>

    <p class="fecha">Generado el <?= esc($fecha) ?></p>
</body>
</html>
