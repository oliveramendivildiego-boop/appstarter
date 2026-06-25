<?php
/**
 * Alerta en viewreport cuando hay análisis pendientes de notificar entrega.
 *
 * @var list<array<string, mixed>> $delivery_pending_rows
 */
$rows = $delivery_pending_rows ?? [];
if ($rows === []) {
    return;
}
$nombres = [];
foreach ($rows as $row) {
    $nombre = trim((string) ($row['analisis_nombre'] ?? ''));
    if ($nombre === '') {
        $nombre = 'Análisis #' . (int) ($row['prianacategoria_id'] ?? 0);
    }
    $nombres[] = $nombre;
}
$nombres = array_values(array_unique($nombres));
?>
<div class="alert registro-delivery-pending-alert border-0 shadow-sm d-flex flex-wrap align-items-center gap-2 mb-3" role="alert">
    <div class="flex-grow-1">
        <div class="fw-semibold">
            <i class="fa-solid fa-bell me-1"></i>
            <?= count($rows) === 1 ? 'Este análisis está listo para notificar entrega' : 'Hay análisis listos para notificar entrega' ?>
        </div>
        <div class="small mt-1"><?= esc(implode(' · ', $nombres)) ?></div>
        <div class="small text-muted mt-1">Informe al médico o paciente y pulse <strong>Notificar</strong> (junto a Factura/Recibo) para confirmar la entrega.</div>
    </div>
</div>
