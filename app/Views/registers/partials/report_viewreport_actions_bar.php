<?php
/**
 * Barra de acciones del preview viewreport (arriba y abajo del reporte).
 *
 * @var int    $ridPdf
 * @var bool   $compOk
 * @var string $lblComp
 * @var bool   $envelope_print_available
 * @var bool   $delivery_show_notify_button
 * @var bool   $delivery_pending_for_registro
 * @var bool   $sticky Si true, la barra queda fija al hacer scroll (solo arriba).
 */
$ridPdf = (int) ($ridPdf ?? 0);
$compOk = ! empty($compOk);
$lblComp = (string) ($lblComp ?? 'Recibo');
$sticky = ! empty($sticky);
$barClass = 'viewreport-actions-bar d-flex flex-wrap gap-2 justify-content-center align-items-center';
if ($sticky) {
    $barClass .= ' viewreport-actions-bar--sticky';
} else {
    $barClass .= ' viewreport-actions-bar--bottom';
}
?>
<div class="<?= esc($barClass, 'attr') ?>">
    <button type="button" class="btn btn-primary js-viewreport-save">Guardar</button>
    <a href="<?= site_url('registers/printreport/' . $ridPdf) ?>" class="btn btn-outline-primary js-viewreport-print">Imprimir</a>
    <?php if (! empty($envelope_print_available)): ?>
    <a href="<?= site_url('registers/printEnvelope/' . $ridPdf) ?>"
       class="btn btn-outline-secondary js-viewreport-envelope"
       title="Imprimir sobre con la plantilla de impresión en Configuración → Sobres">
        <i class="fa-solid fa-envelope me-1"></i> Imprimir sobre
    </a>
    <?php endif; ?>
    <a href="<?= site_url('registers/pdf/' . $ridPdf) ?>" class="btn btn-success" target="_blank">
        <i class="fa-solid fa-file-pdf me-1"></i> Descargar
    </a>
    <a href="<?= site_url('registers/comprobantePdf/' . $ridPdf) ?>"
       class="btn btn-outline-dark"
       target="_blank"
       title="<?= $compOk ? 'Descargar comprobante de pago' : 'Si la orden no está saldada, se mostrará un aviso al intentar descargar' ?>">
        <i class="fa-solid fa-file-invoice-dollar me-1"></i> <?= esc($lblComp) ?>
    </a>
    <?php if (! empty($delivery_show_notify_button)): ?>
    <button type="button" class="btn text-white js-viewreport-notify-delivery" style="background-color:#FF7218;border-color:#FF7218;" data-registro-id="<?= $ridPdf ?>" title="Confirmar que informó la entrega de resultados al médico o paciente">
        <i class="fa-solid fa-bell me-1"></i> Notificar
    </button>
    <?php endif; ?>
</div>
