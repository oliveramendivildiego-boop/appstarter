<?php
/**
 * Panel de informe de maquetación para impresión directa (navegador).
 *
 * @var int   $registro_id
 * @var bool  $layout_report_mode
 * @var array $result_template_bindings
 */
$rid = (int) ($registro_id ?? 0);
$layoutReportMode = ! empty($layout_report_mode);
$bindings = is_array($result_template_bindings ?? null) ? $result_template_bindings : [];
$printBinding = is_array($bindings['print'] ?? null) ? $bindings['print'] : [];
$pdfBinding = is_array($bindings['pdf'] ?? null) ? $bindings['pdf'] : [];
$printTplId = (int) ($printBinding['resolved_template_id'] ?? 0);
$pdfTplId = (int) ($pdfBinding['resolved_template_id'] ?? 0);
$sameTemplate = $printTplId > 0 && $printTplId === $pdfTplId;
$printUsesPdfFallback = ! empty($printBinding['used_pdf_fallback']);
?>
<div id="report-print-layout-report" class="report-print-layout-report no-print" aria-live="polite">
    <div class="report-print-layout-report-header">
        <h2 class="report-print-layout-report-title">Informe de maquetación — impresión directa</h2>
        <div class="report-print-layout-report-actions">
            <button type="button" class="report-print-btn-secondary" id="btn_layout_report_refresh">Actualizar medidas</button>
            <button type="button" class="report-print-btn-primary" id="btn_layout_report_print">Imprimir informe</button>
            <?php if ($rid > 0): ?>
            <a href="<?= site_url('registers/printreport/' . $rid) ?>" class="report-print-btn-secondary">Ir a impresión normal</a>
            <?php endif; ?>
        </div>
    </div>
    <p class="report-print-layout-report-note">
        Medidas tomadas en el navegador sobre el documento cargado (96 DPI CSS).
        Los márgenes aplicados provienen de la <strong>plantilla para imprimir (navegador)</strong> en Configuración → Sistema, no de la plantilla del PDF.
        <?php if ($layoutReportMode): ?>
        Modo informe: no se abrirá el diálogo de impresión automáticamente.
        <?php endif; ?>
    </p>
    <?php if ($printUsesPdfFallback): ?>
    <div class="alert alert-warning py-2 small mb-3">
        No hay plantilla de impresión asignada en Sistema; se está usando la plantilla del PDF como respaldo.
    </div>
    <?php elseif ($sameTemplate): ?>
    <div class="alert alert-info py-2 small mb-3">
        La plantilla de impresión y la del PDF apuntan al mismo diseño (ID <?= esc((string) $printTplId) ?>).
    </div>
    <?php endif; ?>
    <table class="report-print-layout-report-table">
        <thead>
            <tr>
                <th scope="col">Concepto</th>
                <th scope="col">Valor</th>
                <th scope="col">Detalle</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Plantilla impresión (activa)</td>
                <td id="lr_print_template_label">—</td>
                <td id="lr_print_template_detail">Config: <code>print_result_template_id</code></td>
            </tr>
            <tr>
                <td>Plantilla PDF (referencia)</td>
                <td id="lr_pdf_template_label">—</td>
                <td id="lr_pdf_template_detail">Config: <code>pdf_result_template_id</code> — no se usa en esta vista</td>
            </tr>
            <tr>
                <td>Tamaño hoja configurado</td>
                <td id="lr_sheet_size">—</td>
                <td id="lr_sheet_size_detail">—</td>
            </tr>
            <tr>
                <td>Margen superior (aplicado)</td>
                <td id="lr_margin_top">—</td>
                <td id="lr_margin_top_detail">Plantilla impresión (<code>margins_mm.top</code>)</td>
            </tr>
            <tr>
                <td>Margen inferior (aplicado)</td>
                <td id="lr_margin_bottom">—</td>
                <td id="lr_margin_bottom_detail">Plantilla impresión (<code>margins_mm.bottom</code>)</td>
            </tr>
            <tr>
                <td>Margen izquierdo (aplicado)</td>
                <td id="lr_margin_left">—</td>
                <td id="lr_margin_left_detail">Plantilla impresión (<code>margins_mm.left</code>)</td>
            </tr>
            <tr>
                <td>Margen derecho (aplicado)</td>
                <td id="lr_margin_right">—</td>
                <td id="lr_margin_right_detail">Plantilla impresión (<code>margins_mm.right</code>)</td>
            </tr>
            <tr>
                <td>Altura header</td>
                <td id="lr_header_height">—</td>
                <td id="lr_header_height_detail">Bloques antes del primer grupo de resultados (solo página 1)</td>
            </tr>
            <tr>
                <td>Altura footer</td>
                <td id="lr_footer_height">—</td>
                <td id="lr_footer_height_detail">Bloque pie fijo (<code>.pdf-ft-block</code>)</td>
            </tr>
            <tr>
                <td>Contenido hoja 1 (resultados)</td>
                <td id="lr_first_page_content">—</td>
                <td id="lr_first_page_content_detail">Área imprimible − header (solo página 1)</td>
            </tr>
            <tr>
                <td>Contenido hojas siguientes</td>
                <td id="lr_next_page_content">—</td>
                <td id="lr_next_page_content_detail">Área imprimible por hoja (sin header)</td>
            </tr>
            <tr>
                <td>Altura total renderizada</td>
                <td id="lr_total_height">—</td>
                <td id="lr_total_height_detail">Altura del flujo (<code>.pdf-main-stack</code>)</td>
            </tr>
        </tbody>
    </table>
    <h3 class="report-print-layout-report-subtitle">Cálculos derivados</h3>
    <table class="report-print-layout-report-table report-print-layout-report-table-secondary">
        <tbody>
            <tr>
                <td>Margen @page inferior efectivo</td>
                <td id="lr_page_margin_bottom">—</td>
                <td>Igual a margen inferior de plantilla (<code>margins_mm.bottom</code>)</td>
            </tr>
            <tr>
                <td>Reserva de pie (calculada)</td>
                <td id="lr_footer_reserve">—</td>
                <td>Altura medida del pie + 4 mm de colchón</td>
            </tr>
            <tr>
                <td>Altura zona de resultados</td>
                <td id="lr_results_height">—</td>
                <td>Desde primer grupo de prueba hasta el pie en el DOM</td>
            </tr>
            <tr>
                <td>Páginas estimadas</td>
                <td id="lr_pages_estimated">—</td>
                <td id="lr_pages_estimated_detail">Hoja 1 + resto con alturas diferenciadas</td>
            </tr>
            <tr>
                <td>Medición</td>
                <td id="lr_measured_at" colspan="2">—</td>
            </tr>
        </tbody>
    </table>
</div>
<style>
.report-print-layout-report {
    margin: 0 0 20px 0;
    padding: 16px 18px;
    background: #f8f9fa;
    border: 1px solid #ced4da;
    border-radius: 8px;
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
    font-size: 14px;
    color: #212529;
}
.report-print-layout-report-header {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}
.report-print-layout-report-title {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
}
.report-print-layout-report-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.report-print-layout-report-note {
    margin: 0 0 14px 0;
    color: #495057;
    font-size: 0.9rem;
}
.report-print-layout-report-subtitle {
    margin: 18px 0 8px 0;
    font-size: 1rem;
    font-weight: 600;
}
.report-print-layout-report-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
}
.report-print-layout-report-table th,
.report-print-layout-report-table td {
    border: 1px solid #dee2e6;
    padding: 8px 10px;
    text-align: left;
    vertical-align: top;
}
.report-print-layout-report-table th {
    background: #e9ecef;
    font-weight: 600;
}
.report-print-layout-report-table td:first-child {
    width: 28%;
    font-weight: 500;
}
.report-print-layout-report-table td:nth-child(2) {
    width: 18%;
    font-family: Consolas, "Courier New", monospace;
    white-space: nowrap;
}
.report-print-layout-report-table-secondary td:first-child {
    width: 28%;
}
@media print {
    .report-print-layout-report {
        display: block !important;
        margin: 0;
        border: none;
        background: #fff;
    }
    .report-print-layout-report-actions,
    .report-print-layout-report-note {
        display: none !important;
    }
    .pdf-main-stack,
    .report-print-toolbar,
    .print-pagination-fixed,
    .pdf-watermark-layer {
        display: none !important;
    }
}
</style>
