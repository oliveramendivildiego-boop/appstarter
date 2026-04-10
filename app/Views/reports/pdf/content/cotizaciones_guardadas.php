<?php
helper('layout');
$layoutCfg       = layout_config();
$currencySym     = $layoutCfg['currency_symbol'] ?? '$';
$currencySide    = isset($layoutCfg['currency_side']) ? (string) $layoutCfg['currency_side'] : 'left';
$currencyIsRight = strtolower(trim($currencySide)) === 'right';
$data            = $data ?? [];
?>
<p class="small mb-1">Total listado: <?= count($data) ?> cotización(es).</p>
<table class="pdf-t">
    <thead>
        <tr>
            <th>#</th>
            <th>Fecha</th>
            <th>Usuario</th>
            <th>Exámenes</th>
            <th class="text-end">Ref.</th>
            <th class="text-end">Total</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $row):
            $itemsJson = $row['items_json'] ?? null;
            $items     = $itemsJson ? json_decode($itemsJson, true) : [];
            $tieneItems = is_array($items) && $items !== [];
            $examenes  = $tieneItems ? implode(', ', array_column($items, 'name')) : (string) ($row['cotizo'] ?? '-');
            if (mb_strlen($examenes) > 180) {
                $examenes = mb_substr($examenes, 0, 177) . '…';
            }
            ?>
            <tr>
                <td><?= (int) ($row['toquotelogs_id'] ?? 0) ?></td>
                <td><?= esc(date('d/m/Y H:i', strtotime($row['fecha'] ?? ''))) ?></td>
                <td class="small"><?= esc($row['usuario_cotizo'] ?? '-') ?></td>
                <td class="small"><?= esc($examenes) ?></td>
                <td class="text-end"><?= $currencyIsRight
                    ? (number_format((int) ($row['refe'] ?? 0)) . ' ' . esc($currencySym))
                    : (esc($currencySym) . ' ' . number_format((int) ($row['refe'] ?? 0))) ?></td>
                <td class="text-end"><?= $currencyIsRight
                    ? (number_format((int) ($row['costo'] ?? 0)) . ' ' . esc($currencySym))
                    : (esc($currencySym) . ' ' . number_format((int) ($row['costo'] ?? 0))) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($data === []): ?>
            <tr><td colspan="6" class="small">Sin cotizaciones.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
