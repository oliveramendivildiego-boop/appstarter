<?php
/** @var \App\Models\ReciboComprobanteModel $doc */
$sym = $doc->monedaSimbolo;
$fmt = static function (float $n): string {
    return number_format($n, 2, ',', '.');
};
$descuentoMonto = (float) ($doc->institucionDescuentoMonto ?? 0);
$descuentoLabel = 'Descuento';
if (($doc->institucionDescuentoPct ?? 0) > 0) {
    $descuentoLabel = 'Descuento institución (' . ($doc->institucionNombre ?: 'Paciente') . ', ' . number_format((float) $doc->institucionDescuentoPct, 2, '.', '') . '%)';
}
$comprobante_style = isset($comprobante_style) && is_array($comprobante_style) ? $comprobante_style : [];
$primaryColor = $comprobante_style['primary'] ?? '#0f766e';
$secondaryColor = $comprobante_style['secondary'] ?? '#134e4a';
$textColor = $comprobante_style['text'] ?? '#1e293b';
$tagline = trim((string) ($comprobante_style['tagline'] ?? 'Constancia de pago'));
$footerNote = trim((string) ($comprobante_style['footer_note'] ?? 'Documento interno de constancia de pago emitido por el laboratorio. No reemplaza un comprobante fiscal electrónico ni factura validada ante el SIN.'));
$showDoctor = (bool) ($comprobante_style['show_doctor'] ?? true);
$layout = is_array($comprobante_style['layout'] ?? null) ? $comprobante_style['layout'] : (new \App\Services\ComprobanteLayoutService())->getDefaultLayout();
$layoutSvc = new \App\Services\ComprobanteLayoutService();
$pdfCss = $layoutSvc->buildPdfCss($layout, [
    'primary'   => $primaryColor,
    'secondary' => $secondaryColor,
    'text'      => $textColor,
]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo — Orden <?= esc($doc->ordenNumero) ?></title>
    <style><?= $pdfCss ?></style>
</head>
<body>
    <div class="accent-bar"></div>

    <table class="doc-header">
        <tr>
            <td>
                <p class="brand-name"><?= esc($doc->empresaNombre) ?></p>
                <p class="brand-tagline"><?= esc($tagline) ?></p>
            </td>
            <td class="receipt-badge">
                <div class="receipt-badge-inner">
                    <p class="receipt-badge-title">Recibo de pago</p>
                    <p class="receipt-badge-orden">N.º <?= esc($doc->ordenNumero) ?></p>
                    <p class="receipt-badge-fecha"><?= esc($doc->fechaEmision) ?></p>
                </div>
            </td>
        </tr>
    </table>

    <p class="section-title">Cliente y atención</p>
    <div class="panel">
        <?= $layoutSvc->renderClientGridHtml($layout, $doc, $showDoctor) ?>
    </div>

    <p class="section-title">Detalle de conceptos</p>
    <table class="tbl-items">
        <thead>
            <tr>
                <th>Descripción</th>
                <th style="width:26%;">Importe (<?= esc($sym) ?>)</th>
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

    <table class="totals-wrap"><tr><td>
        <div class="totals-box">
            <table>
                <?php if ($descuentoMonto > 0 && $doc->totalRecomendado > 0): ?>
                <tr>
                    <td class="t-lbl">Total recomendado</td>
                    <td class="t-val"><?= esc($sym) ?> <?= esc($fmt($doc->totalRecomendado)) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($descuentoMonto > 0): ?>
                <tr>
                    <td class="t-lbl"><?= esc($descuentoLabel) ?></td>
                    <td class="t-val">- <?= esc($sym) ?> <?= esc($fmt($descuentoMonto)) ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="t-lbl">Total orden</td>
                    <td class="t-val"><?= esc($sym) ?> <?= esc($fmt($doc->total)) ?></td>
                </tr>
                <tr>
                    <td class="t-lbl">Monto pagado</td>
                    <td class="t-val"><?= esc($sym) ?> <?= esc($fmt($doc->montoPagado)) ?></td>
                </tr>
                <tr class="total-final">
                    <td class="t-lbl">Saldo</td>
                    <td class="t-val"><?= esc($sym) ?> <?= esc($fmt($doc->saldo)) ?></td>
                </tr>
            </table>
        </div>
        <div class="pay-method">
            <strong>Forma de pago:</strong> <?= esc($doc->formaPagoEtiqueta) ?>
        </div>
    </td></tr></table>

    <div class="foot">
        <?= esc($footerNote) ?>
    </div>
</body>
</html>
