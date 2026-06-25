<?php
declare(strict_types=1);

/** @var string $configTab */
$tabHelp = [
    'general' => [
        'title' => 'Configuración general',
        'text'  => 'Defina qué secciones lleva el PDF, en qué orden aparecen, y opciones de página (márgenes, saltos y marca de agua). Use las otras pestañas para personalizar cada zona.',
    ],
    'header' => [
        'title' => 'Encabezado del laboratorio',
        'text'  => 'Ubique logo, datos del laboratorio y código QR. Arrastre los campos a la cuadrícula o use los botones para añadir elementos. La vista previa a la derecha muestra cómo quedará.',
    ],
    'patient_doctor' => [
        'title' => 'Datos del paciente y médico',
        'text'  => 'Organice nombre, edad, médico, fechas y número de orden. Puede cambiar las etiquetas («Paciente:», etc.) en la tabla de estilos más arriba.',
    ],
    'results' => [
        'title' => 'Tabla de resultados',
        'text'  => 'Use la vista previa a la derecha: cambia al instante al modificar colores (sección 1), tipografía (2), títulos de sección (4) o matriz de referencia (5).',
    ],
    'notes' => [
        'title' => 'Notas del resultado',
        'text'  => 'La vista previa muestra el bloque de notas. Ajuste los cuatro colores principales (título y contenido) y verá el cambio sin guardar.',
    ],
    'lab_firmas' => [
        'title' => 'Validación y firmas',
        'text'  => 'Elija si las firmas van debajo de cada área o al final. Luego ordene validador, sello, firma y datos del aprobador en la cuadrícula.',
    ],
    'footer' => [
        'title' => 'Pie de página',
        'text'  => 'Textos legales, paginación y datos al pie del documento. Recuerde activar el bloque «Pie de página» en la pestaña General.',
    ],
];
$currentHelp = $tabHelp[$configTab] ?? $tabHelp['general'];
?>
<div class="card shadow-sm mb-3 pdf-tpl-guide-card" id="pdf_tpl_guide_card">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
            <div>
                <h5 class="mb-2"><i class="fa-solid fa-circle-info text-primary me-1"></i> ¿Cómo funciona el diseñador?</h5>
                <p class="small text-muted mb-2">Personalice el PDF de resultados sin conocimientos técnicos. Los cambios se aplican al guardar.</p>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn_dismiss_pdf_guide" title="Ocultar esta guía">
                <i class="fa-solid fa-xmark"></i> Ocultar
            </button>
        </div>
        <ol class="pdf-tpl-guide-steps small">
            <li><strong>Elija una pestaña</strong> (Encabezado, Paciente, Resultados…) según la zona que quiera modificar.</li>
            <li><strong>Arrastre campos</strong> desde la paleta hacia la cuadrícula, o use <em>Añadir elemento</em> en General.</li>
            <li><strong>Revise la vista previa</strong> que aparece junto a cada cuadrícula.</li>
            <li><strong>Guarde la plantilla</strong> con el botón azul (arriba o abajo de la página).</li>
        </ol>
        <p class="small text-muted mb-0 mt-2">
            <i class="fa-solid fa-lightbulb text-warning me-1"></i>
            ¿Necesita más control? Active <strong>Opciones avanzadas</strong> en la cuadrícula para ver la lista detallada de cada elemento.
        </p>
    </div>
</div>

<div class="pdf-tpl-tab-context" id="pdf_tpl_tab_context" role="status" aria-live="polite">
    <strong id="pdf_tpl_tab_context_title"><?= esc($currentHelp['title']) ?></strong>
    <span class="d-block small text-muted mt-1" id="pdf_tpl_tab_context_text"><?= esc($currentHelp['text']) ?></span>
</div>

<script>
window._pdfTplTabHelp = <?= json_encode($tabHelp, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>
