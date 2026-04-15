<?php
/** @var \App\Models\FacturaComprobanteModel $doc */
$sym = $doc->monedaSimbolo;
$fmt = static function (float $n): string {
    return number_format($n, 2, ',', '.');
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura — Orden <?= esc($doc->ordenNumero) ?></title>
    <?= view('registers/billing/_comprobante_estilos') ?>
</head>
<body>
    <h1>FACTURA / COMPROBANTE DE VENTA</h1>
    <p class="muted" style="text-align:center;margin:0 0 6px 0;"><?= esc($doc->razonSocial) ?></p>
    <?php if (trim($doc->nitEmpresa) !== ''): ?>
        <p class="muted" style="text-align:center;margin:0 0 14px 0;">NIT: <?= esc($doc->nitEmpresa) ?>
            <?php if (trim($doc->codigoSucursal) !== ''): ?> — Sucursal <?= esc($doc->codigoSucursal) ?><?php endif; ?>
        </p>
    <?php else: ?>
        <p class="muted" style="text-align:center;margin:0 0 14px 0;">Complete NIT en Configuración → Facturación SIN</p>
    <?php endif; ?>

    <?php if (trim($doc->codigoActividad) !== ''): ?>
        <p class="muted" style="text-align:center;margin:-8px 0 14px 0;">Actividad (CAEN): <?= esc($doc->codigoActividad) ?></p>
    <?php endif; ?>

    <div class="box">
        <div class="box-row"><strong>N.º documento (orden):</strong> <?= esc($doc->ordenNumero) ?></div>
        <div class="box-row"><strong>Fecha de emisión:</strong> <?= esc($doc->fechaEmision) ?></div>
        <p class="muted" style="margin:10px 0 6px 0;font-weight:bold;">Cliente y atención</p>
        <?= view('registers/billing/_datos_cliente_atencion_pdf', ['doc' => $doc]) ?>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>Detalle</th>
                <th style="width:24%;text-align:right;">Importe (<?= esc($sym) ?>)</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($doc->lineas as $ln): ?>
            <tr>
                <td><?= esc($ln['descripcion'] ?? '') ?></td>
                <td class="num"><?= esc($fmt((float) ($ln['importe'] ?? 0))) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <table class="totals">
        <?php if ($doc->totalRecomendado > 0): ?>
            <tr>
                <td class="lbl">Subtotal referencial</td>
                <td class="val"><?= esc($sym) ?> <?= esc($fmt($doc->totalRecomendado)) ?></td>
            </tr>
        <?php endif; ?>
        <?php if (($doc->institucionDescuentoPct ?? 0) > 0): ?>
            <tr>
                <td class="lbl">Descuento institución (<?= esc($doc->institucionNombre ?: 'Paciente') ?>, <?= esc(number_format((float) $doc->institucionDescuentoPct, 2, '.', '')) ?>%)</td>
                <td class="val">- <?= esc($sym) ?> <?= esc($fmt((float) ($doc->institucionDescuentoMonto ?? 0))) ?></td>
            </tr>
        <?php endif; ?>
        <tr>
            <td class="lbl">Total</td>
            <td class="val"><?= esc($sym) ?> <?= esc($fmt($doc->total)) ?></td>
        </tr>
        <tr>
            <td class="lbl">Importe pagado</td>
            <td class="val"><?= esc($sym) ?> <?= esc($fmt($doc->montoPagado)) ?></td>
        </tr>
        <tr>
            <td class="lbl">Saldo pendiente</td>
            <td class="val"><?= esc($sym) ?> <?= esc($fmt($doc->saldo)) ?></td>
        </tr>
        <tr>
            <td class="lbl">Método de pago</td>
            <td class="val"><?= esc($doc->formaPagoEtiqueta) ?></td>
        </tr>
    </table>

    <div class="foot">
        Documento de respaldo con fines informativos. La factura electrónica válida ante el SIN se genera mediante el sistema
        de facturación autorizado cuando la integración esté operativa.
    </div>
</body>
</html>
