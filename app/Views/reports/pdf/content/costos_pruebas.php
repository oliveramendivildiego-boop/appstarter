<?php
helper('layout');
$layoutCfg       = layout_config();
$currencySym     = $layoutCfg['currency_symbol'] ?? '$';
$currencySide    = isset($layoutCfg['currency_side']) ? (string) $layoutCfg['currency_side'] : 'left';
$currencyIsRight = strtolower(trim($currencySide)) === 'right';
$data            = $data ?? [];
?>
<table class="pdf-t">
    <thead>
        <tr>
            <th>Categoría</th>
            <th>Prueba</th>
            <th class="text-end">Precio</th>
            <th class="text-end">Derivado</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $categoriaHeaderKey = null;
        $categoriaActualNombre = null;
        $totalPrecio     = 0.0;
        $totalDerivado   = 0.0;
        $subtotalPrecio  = 0.0;
        $subtotalDerivado = 0.0;
        ?>
        <?php foreach ($data as $item): ?>
            <?php
            $catNombre = (string) ($item['categoria'] ?? '');
            $catHeaderKey = mb_strtolower($catNombre, 'UTF-8');
            ?>
            <?php if ($categoriaHeaderKey !== $catHeaderKey): ?>
                <?php if ($categoriaHeaderKey !== null): ?>
                    <tr>
                        <td colspan="2" class="text-end"><strong>Subtotal <?= esc($categoriaActualNombre) ?></strong></td>
                        <td class="text-end"><?= $currencyIsRight ? (number_format($subtotalPrecio, 2) . ' ' . esc($currencySym)) : (esc($currencySym) . ' ' . number_format($subtotalPrecio, 2)) ?></td>
                        <td class="text-end"><?= $currencyIsRight ? (number_format($subtotalDerivado, 2) . ' ' . esc($currencySym)) : (esc($currencySym) . ' ' . number_format($subtotalDerivado, 2)) ?></td>
                    </tr>
                <?php endif; ?>
                <?php
                $categoriaHeaderKey = $catHeaderKey;
                $categoriaActualNombre = $catNombre;
                $subtotalPrecio   = 0.0;
                $subtotalDerivado = 0.0;
                ?>
                <tr>
                    <td colspan="4"><strong><?= esc($catNombre) ?></strong></td>
                </tr>
            <?php endif; ?>
            <?php
            $precio   = (float) ($item['precio'] ?? 0);
            $derivado = (float) ($item['precio_derivado'] ?? 0);
            $subtotalPrecio += $precio;
            $subtotalDerivado += $derivado;
            $totalPrecio += $precio;
            $totalDerivado += $derivado;
            ?>
            <tr>
                <td></td>
                <td><?= esc($item['prueba'] ?? '') ?></td>
                <td class="text-end"><?= $currencyIsRight ? (number_format($precio, 2) . ' ' . esc($currencySym)) : (esc($currencySym) . ' ' . number_format($precio, 2)) ?></td>
                <td class="text-end"><?= $currencyIsRight ? (number_format($derivado, 2) . ' ' . esc($currencySym)) : (esc($currencySym) . ' ' . number_format($derivado, 2)) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($categoriaHeaderKey !== null): ?>
            <tr>
                <td colspan="2" class="text-end"><strong>Subtotal <?= esc($categoriaActualNombre) ?></strong></td>
                <td class="text-end"><?= $currencyIsRight ? (number_format($subtotalPrecio, 2) . ' ' . esc($currencySym)) : (esc($currencySym) . ' ' . number_format($subtotalPrecio, 2)) ?></td>
                <td class="text-end"><?= $currencyIsRight ? (number_format($subtotalDerivado, 2) . ' ' . esc($currencySym)) : (esc($currencySym) . ' ' . number_format($subtotalDerivado, 2)) ?></td>
            </tr>
        <?php endif; ?>
        <?php if ($data !== []): ?>
            <tr>
                <td colspan="2" class="text-end"><strong>TOTAL</strong></td>
                <td class="text-end"><?= $currencyIsRight ? (number_format($totalPrecio, 2) . ' ' . esc($currencySym)) : (esc($currencySym) . ' ' . number_format($totalPrecio, 2)) ?></td>
                <td class="text-end"><?= $currencyIsRight ? (number_format($totalDerivado, 2) . ' ' . esc($currencySym)) : (esc($currencySym) . ' ' . number_format($totalDerivado, 2)) ?></td>
            </tr>
        <?php else: ?>
            <tr><td colspan="4" class="small">Sin datos.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
