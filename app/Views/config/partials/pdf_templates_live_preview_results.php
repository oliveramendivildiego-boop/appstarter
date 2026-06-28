<?php
declare(strict_types=1);
?>
<div class="pdf-tpl-live-preview-sticky mb-4 mb-lg-0" id="pdf_results_live_preview_wrap">
    <div class="pdf-preview-sheet border rounded shadow-sm bg-white">
        <div class="pdf-preview-sheet-bar small text-white bg-info px-2 py-1 d-flex justify-content-between align-items-center">
            <span><i class="fa-solid fa-eye me-1"></i> Vista previa en tiempo real</span>
            <span class="badge bg-white text-info">Resultados</span>
        </div>
        <div id="pdf_results_live_preview_scope" class="pdf-tpl-live-preview-scope p-2">
            <div class="report-pdf-grupo-prueba report-pdf-grupo-prueba-first">
                <div id="pdf_preview_area_separator" class="report-segment-title pdf-card-header report-pdf-grupo-area-separator" style="display:none;">HEMATOLOGÍA</div>
                <div class="report-pdf-grupo-cabecera" id="pdf_preview_grupo_cabecera">
                    <div class="group-title report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--title" id="pdf_preview_grupo_title">HEMOGRAMA + PLAQUETAS</div>
                    <div class="report-tipo-muestra report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--tipo" id="pdf_preview_tipo_muestra">Tipo de muestra: Sangre EDTA</div>
                    <div class="report-metodo-prueba report-pdf-grupo-cabecera-line report-pdf-grupo-cabecera-line--metodo report-pdf-grupo-cabecera-line--last" id="pdf_preview_metodo">Método: Impedancia</div>
                </div>
                <div class="report-segment-table-wrap">
            <div class="report-segment-title pdf-card-header report-pdf-section-row-title">1RA MUESTRA — EXAMEN MACROSCÓPICO</div>
                    <table class="results results-cols-4 results-cols-grid mb-2" width="100%" cellspacing="0" cellpadding="0" style="table-layout:fixed;width:100%;">
                        <colgroup><col width="34%" style="width:34%;"><col width="18%" style="width:18%;"><col width="28%" style="width:28%;"><col width="20%" style="width:20%;"></colgroup>
                        <thead>
                            <tr>
                                <th class="results-col-analisis">ANÁLISIS</th>
                                <th class="results-col-resultado">RESULTADO</th>
                                <th class="results-col-rango">RANGO REF.</th>
                                <th class="results-col-interpretacion">INTERPRETACIÓN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="results-col-analisis">Glucosa en ayunas</td>
                                <td class="results-col-resultado">95 mg/dL</td>
                                <td class="results-col-rango">70 – 110</td>
                                <td class="results-col-interpretacion">Normal</td>
                            </tr>
                            <tr>
                                <td class="results-col-analisis">Hemoglobina</td>
                                <td class="results-col-resultado out-range">11.2 g/dL</td>
                                <td class="results-col-rango">12 – 16</td>
                                <td class="results-col-interpretacion report-interpretacion-bajo">Bajo</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="report-refs-matrix-wrap mt-2">
                <table class="results report-refs-matrix mb-0" width="100%" cellspacing="0" cellpadding="0">
                    <thead>
                        <tr>
                            <th class="matrix-col-population">Grupo</th>
                            <th class="matrix-col-parameter">Parámetro</th>
                            <th class="matrix-col-sex">Sexo</th>
                            <th class="matrix-col-reference">Referencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="matrix-col-population">Adultos</td>
                            <td class="matrix-col-parameter">Hemoglobina</td>
                            <td class="matrix-col-sex">F</td>
                            <td class="matrix-col-reference">12 – 16 g/dL</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <p class="small text-muted mt-2 mb-0">
        <i class="fa-solid fa-palette me-1"></i>
        Los colores y tipografías de la tabla, títulos de sección y matriz de referencia se actualizan al modificar los controles de la izquierda.
    </p>
    <ul class="small text-muted mb-0 ps-3 mt-1">
        <li><strong>Sección 1:</strong> encabezado y filas de la tabla principal.</li>
        <li><strong>Sección 4:</strong> título del área (<code>report-pdf-grupo-area-separator</code>, p. ej. HEMATOLOGÍA): fondo, fuente y bordes.</li>
        <li><strong>Sección 5:</strong> fila separadora de sección (<code>report-pdf-section-row-title</code>): fondo, fuente y bordes.</li>
        <li><strong>Sección 6:</strong> matriz poblacional al pie del ejemplo.</li>
        <li><strong>Sección 8:</strong> nombre del análisis (<code>.group-title</code>, p. ej. Hemograma completo).</li>
        <li><strong>Secciones 7 y 9:</strong> cabecera de grupo y espaciado de tipo/método.</li>
    </ul>
</div>
<style id="pdf_results_live_preview_rules"></style>
