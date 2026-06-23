<?php
// Panel de diagnóstico eliminado: el modo de impresión directa ya no muestra
// el informe de maquetación en la vista de impresión. Mantener este archivo
// vacío para evitar roturas donde se incluía como partial.
?>
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
.report-print-layout-report-tips {
    margin: 0;
    padding-left: 1.2rem;
}
.report-print-layout-report-tips li + li {
    margin-top: 6px;
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
