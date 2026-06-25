<?php
declare(strict_types=1);

/** @var bool $can_manage_tenants */
?>
<div class="card shadow-sm mb-2 config-hub-guide" id="config_hub_guide">
    <div class="card-body py-2 px-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="me-auto">
                <span class="fw-semibold"><i class="fa-solid fa-sliders text-primary me-1"></i> Configuración</span>
                <span class="text-muted small ms-1 d-none d-md-inline">— use el filtro y las pestañas, o el buscador junto a ellas</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" id="btn_dismiss_config_hub_guide" title="Ocultar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="config-quick-links config-quick-links-compact mt-2">
            <a href="#" class="config-quick-link" data-config-tab="sistema"><i class="fa-solid fa-gear"></i><span>Sistema</span></a>
            <a href="<?= site_url('config/pdf-templates') ?>" class="config-quick-link"><i class="fa-solid fa-file-pdf"></i><span>PDF</span></a>
            <a href="#" class="config-quick-link" data-config-tab="estilo"><i class="fa-solid fa-palette"></i><span>Apariencia</span></a>
            <a href="#" class="config-quick-link" data-config-tab="opciones"><i class="fa-solid fa-list-check"></i><span>Resultados</span></a>
            <a href="#" class="config-quick-link" data-config-tab="lab_validacion"><i class="fa-solid fa-signature"></i><span>Firmas</span></a>
            <a href="#" class="config-quick-link" data-config-tab="whatsapp"><i class="fa-brands fa-whatsapp"></i><span>WhatsApp</span></a>
        </div>
    </div>
</div>
<datalist id="config_tab_finder_list"></datalist>
<script>
window._configTabIndex = <?= json_encode([
    ['id' => 'sistema', 'label' => 'Configuración del sistema', 'group' => 'general', 'keywords' => 'sistema logo moneda papel recepción inventario'],
    ['id' => 'institucion_descuentos', 'label' => 'Descuentos por institución', 'group' => 'general', 'keywords' => 'descuentos descuento paciente institución'],
    ['id' => 'comprobante', 'label' => 'Estilo comprobante', 'group' => 'impresion', 'keywords' => 'comprobante recibo factura pago'],
    ['id' => 'estilo', 'label' => 'Apariencia', 'group' => 'impresion', 'keywords' => 'apariencia estilo tema color fuente menú'],
    ['id' => 'sin', 'label' => 'Facturación SIN', 'group' => 'integraciones', 'keywords' => 'sin factura bolivia impuestos'],
    ['id' => 'poblacion', 'label' => 'Grupos de población', 'group' => 'general', 'keywords' => 'población edad referencia rango'],
    ['id' => 'lab_validacion', 'label' => 'Validación del laboratorio', 'group' => 'general', 'keywords' => 'validación firmas firma sello aprobador'],
    ['id' => 'metodos_prueba', 'label' => 'Métodos de prueba', 'group' => 'catalogos', 'keywords' => 'métodos metodos técnica análisis'],
    ['id' => 'sobres', 'label' => 'Sobres', 'group' => 'impresion', 'keywords' => 'sobres sobre envelope etiqueta'],
    ['id' => 'sesiones', 'label' => 'Sesiones activas', 'group' => 'admin', 'keywords' => 'sesiones usuarios conectados'],
    ['id' => 'tipos_muestra', 'label' => 'Tipos de muestra', 'group' => 'catalogos', 'keywords' => 'muestras muestra sangre suero orina'],
    ['id' => 'opciones', 'label' => 'Tipos de resultado', 'group' => 'catalogos', 'keywords' => 'resultados opción select lista valor'],
    ['id' => 'leyendas_cultivo', 'label' => 'Leyendas cultivo', 'group' => 'catalogos', 'keywords' => 'cultivos cultivo bacteria hongo'],
    ['id' => 'ficha_clinica', 'label' => 'Ficha clínica', 'group' => 'catalogos', 'keywords' => 'ficha matriz clínica'],
    ['id' => 'whatsapp', 'label' => 'WhatsApp', 'group' => 'integraciones', 'keywords' => 'mensaje meta twilio'],
    ['id' => 'tenants', 'label' => 'Tenants', 'group' => 'admin', 'keywords' => 'multi tenant', 'adminOnly' => true],
    ['id' => 'tenant_subscriptions', 'label' => 'Pagos suscripciones', 'group' => 'admin', 'keywords' => 'pago plan', 'adminOnly' => true],
    ['id' => 'tenant_home_broadcast', 'label' => 'Aviso dashboard', 'group' => 'admin', 'keywords' => 'aviso inicio', 'adminOnly' => true],
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>
