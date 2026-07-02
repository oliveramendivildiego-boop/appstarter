<?php
/**
 * Barra fija para depuración del HTML del reporte (vista previa tipo PDF).
 * Enlaces relativos: respetan el host y la ruta actual (localhost vs laboratorio.local).
 *
 * @var int $registro_id
 */
$rid = (int) ($registro_id ?? 0);
?>
<style>
.report-html-debug-strip {
    position: sticky;
    top: 0;
    z-index: 99999;
    background: #1e293b;
    color: #f8fafc;
    font-family: system-ui, sans-serif;
    font-size: 13px;
    padding: 8px 12px;
    border-bottom: 2px solid #f59e0b;
    box-shadow: 0 2px 8px rgba(0,0,0,.25);
}
.report-html-debug-strip a {
    color: #93c5fd;
    text-decoration: none;
    margin-right: 12px;
}
.report-html-debug-strip a:hover {
    text-decoration: underline;
}
.report-html-debug-strip .badge {
    background: #f59e0b;
    color: #1e293b;
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 4px;
    margin-right: 10px;
}
</style>
<div class="report-html-debug-strip" role="navigation" aria-label="Depuración HTML reporte">
    <span class="badge">Vista HTML → PDF</span>
    <strong>Registro #<?= $rid ?></strong>
    <span style="margin:0 10px;opacity:.5">|</span>
    <a href="?inspector=1">Inspector código/CSS</a>
    <a href="registers/viewreport/<?= $rid ?>">Ver PDF</a>
    <a href="?purge=1">Regenerar</a>
    <a href="?source=raw">HTML sin adaptar mPDF</a>
</div>
